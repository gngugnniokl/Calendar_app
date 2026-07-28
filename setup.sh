#!/usr/bin/env bash

# ==============================================================================
# Tribbbal Internship Calendar — Developer Setup Script
# ==============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

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
    echo "❌ Cannot connect to MySQL with the provided credentials."
    echo "   Host: $DB_HOST | User: $DB_USER"
    echo "   Ensure MySQL is running and your credentials in .env.local are correct."
    exit 1
fi

echo "✅ MySQL connection successful."

# ==============================================================================
# 4. Database Migration
# ==============================================================================
echo ""
echo "🗄️  Running database migration (sql/internship_calendar.sql)..."
echo "   This will DROP and recreate all tables in '${DB_NAME}'."

read -p "⚠️  Continue? [Y/n]: " CONFIRM
CONFIRM="${CONFIRM:-Y}"
if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
    echo "Migration skipped."
else
    if mysql "${MYSQL_ARGS[@]}" < sql/internship_calendar.sql; then
        echo "✅ Database schema and seed data loaded."
    else
        echo "❌ Database migration failed. Check the output above for errors."
        exit 1
    fi
fi

# ==============================================================================
# 5. Verify Migration
# ==============================================================================
echo ""
echo "🔍 Verifying database tables..."

EXPECTED_TABLES=("Wo_Users" "calendar_events" "calendar_nudges" "Wo_Posts" "Wo_AppsSessions" "Wo_Bad_Login" "Wo_Config")
ALL_OK=1

for TABLE in "${EXPECTED_TABLES[@]}"; do
    COUNT=$(mysql "${MYSQL_ARGS[@]}" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_name='${TABLE}';" 2>/dev/null)
    if [ "$COUNT" = "1" ]; then
        echo "   ✅ $TABLE"
    else
        echo "   ❌ $TABLE — missing!"
        ALL_OK=0
    fi
done

if [ "$ALL_OK" -eq 0 ]; then
    echo ""
    echo "⚠️  Some tables are missing. The migration may not have run or completed."
    echo "   Re-run this script or manually execute: mysql ${DB_USER}@${DB_HOST} < sql/internship_calendar.sql"
fi

# ==============================================================================
# 6. File Permissions
# ==============================================================================
echo ""
echo "📁 Checking file permissions..."

# Ensure PHP files aren't world-writable
WORLD_WRITABLE=$(find . -name "*.php" -perm -o=w 2>/dev/null | head -5)
if [ -n "$WORLD_WRITABLE" ]; then
    echo "⚠️  Some PHP files are world-writable (fixing):"
    find . -name "*.php" -perm -o=w -exec chmod o-w {} \;
    echo "   Removed world-write bit from PHP files."
else
    echo "   ✅ No world-writable PHP files."
fi

# ==============================================================================
# Done
# ==============================================================================
echo ""
echo "--------------------------------------------------------------------------------"
echo "🎉 Setup Complete! You're ready to code."
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
