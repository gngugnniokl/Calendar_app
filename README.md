# Tribbbal Internship Calendar

## Quick Start

```bash
# 1. Run the automated setup script
# This will check dependencies, setup .env.local, and run DB migrations
./setup.sh

# 2. Start the dev server
php -S localhost:6060
```

## Test Credentials

| Username | Email               | Password      | Role   |
|----------|---------------------|---------------|--------|
| intern1  | intern1@example.com | password123   | Intern |
| mentor   | mentor@example.com  | password123   | Mentor |
| admin    | admin@example.com   | password123   | Admin  |

## Auth Routes

| URL | Access | Description |
|-----|--------|-------------|
| `?link1=welcome` | Public | Login page |
| `?link1=register` | Public | Registration |
| `?link1=forgot_password` | Public | Password reset |
| `?link1=logout` | Public | Destroy session |
| `?link1=internship_calendar` | Protected | Calendar home |
| `?link1=internship_calendar_dashboard` | Protected | Dashboard |
| `?link1=timeline&u={username}` | Protected | User profile |
