#!/usr/bin/env bash

# ==============================================================================
# Tribbbal Internship Calendar — Developer Setup Script
# ==============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

SETUP_LOG="/tmp/tribbbal_setup.log"
: > "$SETUP_LOG"

log_info() {
    echo "ℹ️  $*"
    printf '[INFO] %s\n' "$*" >> "$SETUP_LOG"
}

log_ok() {
    echo "✅ $*"
    printf '[OK] %s\n' "$*" >> "$SETUP_LOG"
}

log_warn() {
    echo "⚠️  $*"
    printf '[WARN] %s\n' "$*" >> "$SETUP_LOG"
}

log_error() {
    echo "❌ $*" >&2
    printf '[ERROR] %s\n' "$*" >> "$SETUP_LOG"
}

run_sql_file() {
    local sql_file="$1"
    local label="$2"

    if [ ! -f "$sql_file" ]; then
        log_error "$label missing: $sql_file"
        exit 1
    fi

    log_info "Importing $label from $sql_file"
    if mysql "${MYSQL_ARGS[@]}" < "$sql_file" >>"$SETUP_LOG" 2>&1; then
        log_ok "$label imported successfully"
    else
        log_error "$label import failed. See $SETUP_LOG for details."
        exit 1
    fi
}

echo "📅 Setting up Tribbbal Internship Calendar Development Environment..."
echo "--------------------------------------------------------------------------------"

# ==============================================================================
# 1. Check Dependencies
# ==============================================================================
MISSING=0

command -v php >/dev/null 2>&1 || { echo >&2 "❌ PHP is required but not installed."; MISSING=1; }
command -v mysql >/dev/null 2>&1 || { echo >&2 "❌ MySQL client is required but not installed."; MISSING=1; }

if [ "$MISSING" -eq 1 ]; then
    echo ""
    echo "Install missing dependencies and re-run this script."
    exit 1
fi

# Check required PHP extensions
php -r 'exit(extension_loaded("mysqli") ? 0 : 1);' || {
    echo >&2 "❌ PHP mysqli extension is required but not enabled."
    echo >&2 "   On macOS: brew install php && brew services restart php"
    echo >&2 "   On Ubuntu: sudo apt install php-mysql"
    exit 1
}

php -m 2>/dev/null | grep -qi "^mbstring$" || {
    echo >&2 "⚠️  PHP mbstring extension is recommended but not enabled (non-fatal)."
}

echo "✅ Dependencies satisfied (PHP, MySQL, mysqli)."

# ==============================================================================
# 2. Environment Variables (.env.local)
# ==============================================================================
echo ""
if [ ! -f ".env.local" ]; then
    echo "📄 Creating .env.local — we need your local MySQL credentials."
    echo ""

    read -p "DB host [localhost]: " INPUT_HOST
    DB_HOST="${INPUT_HOST:-localhost}"

    read -p "DB user [root]: " INPUT_USER
    DB_USER="${INPUT_USER:-root}"

    read -sp "DB password (leave blank for none): " INPUT_PASS
    echo ""
    DB_PASS="${INPUT_PASS:-}"

    read -p "DB name [internship_calendar]: " INPUT_NAME
    DB_NAME="${INPUT_NAME:-internship_calendar}"

    # Write .env.local from template, substituting credentials
    sed \
        -e "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|" \
        -e "s|^DB_USER=.*|DB_USER=${DB_USER}|" \
        -e "s|^DB_PASS=.*|DB_PASS=${DB_PASS}|" \
        -e "s|^DB_NAME=.*|DB_NAME=${DB_NAME}|" \
        .env.example > .env.local

    echo "✅ .env.local created with your credentials."
else
    echo "✅ .env.local already exists — reading credentials from it."
    # Parse existing .env.local
    DB_HOST=$(grep -E '^DB_HOST=' .env.local | cut -d'=' -f2- | tr -d "'\"" || echo "localhost")
    DB_USER=$(grep -E '^DB_USER=' .env.local | cut -d'=' -f2- | tr -d "'\"" || echo "root")
    DB_PASS=$(grep -E '^DB_PASS=' .env.local | cut -d'=' -f2- | tr -d "'\"" || echo "")
    DB_NAME=$(grep -E '^DB_NAME=' .env.local | cut -d'=' -f2- | tr -d "'\"" || echo "internship_calendar")
fi

# ==============================================================================
# 3. Test MySQL Connection
# ==============================================================================
echo ""
echo "🔌 Testing MySQL connection..."

MYSQL_ARGS=(-h "$DB_HOST" -u "$DB_USER")
if [ -n "$DB_PASS" ]; then
    MYSQL_ARGS+=(-p"$DB_PASS")
fi

if ! mysql "${MYSQL_ARGS[@]}" -e "SELECT 1;" >/dev/null 2>&1; then
    log_error "Cannot connect to MySQL with the provided credentials."
    log_error "Host: $DB_HOST | User: $DB_USER"
    log_error "Ensure MySQL is running and your credentials in .env.local are correct."
    exit 1
fi

log_ok "MySQL connection successful"

# ==============================================================================
# 4. Database Migration
# ==============================================================================
echo ""
log_info "Running database migration (sql/internship_calendar.sql)"
log_info "This will DROP and recreate all tables in '${DB_NAME}'."

read -p "⚠️  Continue? [Y/n]: " CONFIRM
CONFIRM="${CONFIRM:-Y}"
if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
    log_warn "Migration skipped"
else
    run_sql_file "sql/internship_calendar.sql" "Core database schema"

    log_info "Importing leaderboard module database assets in order"
    run_sql_file "sql/leaderboard_schema.sql" "Leaderboard schema"
    run_sql_file "to-ir-project/documents/leaderboard-implementation/dummy_users.sql" "Leaderboard dummy users"
    run_sql_file "to-ir-project/documents/leaderboard-implementation/dummy_tokens.sql" "Leaderboard dummy tokens"
fi

# ==============================================================================
# 5. Verify Migration
# ==============================================================================
echo ""
log_info "Verifying database tables"

EXPECTED_TABLES=("Wo_Users" "calendar_events" "calendar_nudges" "Wo_Posts" "Wo_AppsSessions" "Wo_Bad_Login" "Wo_Config")
ALL_OK=1

for TABLE in "${EXPECTED_TABLES[@]}"; do
    COUNT=$(mysql "${MYSQL_ARGS[@]}" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_name='${TABLE}';" 2>/dev/null)
    if [ "$COUNT" = "1" ]; then
        log_ok "$TABLE"
    else
        log_error "$TABLE missing"
        ALL_OK=0
    fi
done

if [ "$ALL_OK" -eq 0 ]; then
    echo ""
    log_warn "Some base tables are missing. The migration may not have run or completed."
    log_warn "Re-run this script or manually execute: mysql ${DB_USER}@${DB_HOST} < sql/internship_calendar.sql"
fi

LEADERBOARD_TABLES=("leaderboard_config" "leaderboard_tokens" "token_transactions")
for TABLE in "${LEADERBOARD_TABLES[@]}"; do
    COUNT=$(mysql "${MYSQL_ARGS[@]}" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_name='${TABLE}';" 2>/dev/null)
    if [ "$COUNT" = "1" ]; then
        log_ok "$TABLE"
    else
        log_error "$TABLE missing"
        ALL_OK=0
    fi
done

PROC_COUNT=$(mysql "${MYSQL_ARGS[@]}" -N -e "SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema='${DB_NAME}' AND routine_name='recalculateRanks' AND routine_type='PROCEDURE';" 2>/dev/null || echo "0")
if [ "$PROC_COUNT" = "1" ]; then
    log_ok "stored procedure recalculateRanks"
else
    log_error "stored procedure recalculateRanks missing"
    ALL_OK=0
fi

# ==============================================================================
# 6. File Permissions
# ==============================================================================
echo ""
log_info "Checking file permissions"

# Ensure PHP files aren't world-writable
WORLD_WRITABLE=$(find . -name "*.php" -perm -o=w 2>/dev/null | head -5)
if [ -n "$WORLD_WRITABLE" ]; then
    log_warn "Some PHP files are world-writable (fixing)"
    find . -name "*.php" -perm -o=w -exec chmod o-w {} \;
    log_ok "Removed world-write bit from PHP files"
else
    log_ok "No world-writable PHP files"
fi

# ==============================================================================
# Done
# ==============================================================================
echo ""
echo "--------------------------------------------------------------------------------"
echo "🎉 Setup Complete! You're ready to code."
echo "   Setup log: $SETUP_LOG"
echo ""
echo "🚀 Start the local server:"
echo "   php -S localhost:6060"
echo ""
echo "📱 Then visit: http://localhost:6060"
echo ""
echo "Test Accounts:"
echo "  intern1@example.com / password123 (Intern)"
echo "  mentor@example.com  / password123  (Mentor)"
echo "  admin@example.com   / password123  (Admin)"
echo "=============================================================================="
