#!/usr/bin/env bash

# ==============================================================================
# Tribbbal Internship Calendar — Developer Setup Script
# ==============================================================================

echo "📅 Setting up Tribbbal Internship Calendar Development Environment..."
echo "--------------------------------------------------------------------------------"

# 1. Check Dependencies
command -v php >/dev/null 2>&1 || { echo >&2 "❌ PHP is required but not installed. Aborting."; exit 1; }
command -v mysql >/dev/null 2>&1 || { echo >&2 "❌ MySQL is required but not installed. Aborting."; exit 1; }

echo "✅ Dependencies satisfied (PHP and MySQL found)."

# 2. Environment Variables
if [ ! -f ".env.local" ]; then
    echo "📄 Creating .env.local from .env.example..."
    cp .env.example .env.local
    echo "✅ .env.local created."
    echo "⚠️  Note: Please ensure the DB_USER and DB_PASS in .env.local match your local MySQL."
else
    echo "✅ .env.local already exists."
fi

# 3. Database Migration
echo ""
echo "🗄️  Setting up the database (internship_calendar)..."
echo "This will execute sql/internship_calendar.sql which drops existing tables to refresh the schema."

# Ask the user if they want to run it with their local root user
echo "⚠️  Note: If your local root user has no password, just press 'Enter' at the prompt."
read -p "Press [Enter] to run the SQL migration: "

mysql -u root -p < sql/internship_calendar.sql

if [ $? -eq 0 ]; then
    echo "✅ Database schema and seed data loaded successfully!"
else
    echo "❌ Database migration failed."
    echo "Please ensure MySQL is running, check your root password, and try again."
    exit 1
fi

echo "--------------------------------------------------------------------------------"
echo "🎉 Setup Complete! You're ready to code."
echo ""
echo "🚀 Start the local server by running:"
echo "   php -S localhost:6060"
echo ""
echo "📱 Then visit: http://localhost:6060"
echo ""
echo "Test Accounts:"
echo "- intern1@example.com (password123)"
echo "- mentor@example.com  (password123)"
echo "- admin@example.com   (password123)"
echo "=============================================================================="
