# Authentication Integration Plan — Calendar_app

> **Source:** `docs/tasks/authentication.md` (triBBBal auth architecture)
> **Date:** 2026-07-09
> **Status:** Phases 1–6 COMPLETE. Phase 7 (Security Hardening) in progress.

---

## Implementation Status

| Phase | Description | Status |
|-------|-------------|--------|
| 1 | Database Schema | ✅ Complete |
| 2 | Bootstrap & Core Auth Functions | ✅ Complete |
| 3 | Auth-Aware Routing | ✅ Complete |
| 4 | Auth Page Controllers & Templates | ✅ Complete |
| 5 | XHR Auth Handlers | ✅ Complete |
| 6 | UI Integration | ✅ Complete |
| 7 | Security Hardening | ✅ Partial (CSRF + brute-force done) |

## Credentials (Dev Only)

| Username | Email | Password | Role |
|----------|-------|----------|------|
| intern1 | intern1@example.com | password123 | Intern |
| mentor | mentor@example.com | password123 | Mentor |
| admin | admin@example.com | password123 | Admin |

## Files Created

- `assets/init.php` — Bootstrap chain
- `sources/welcome.php` — Login page controller
- `sources/register.php` — Registration controller
- `sources/logout.php` — Session destruction
- `sources/forgot_password.php` — Password reset initiation
- `sources/reset_password.php` — Password reset completion
- `xhr/auth.php` — Auth XHR handler
- `themes/wondertag/layout/auth/login.phtml` — Login form
- `themes/wondertag/layout/auth/register.phtml` — Register form
- `themes/wondertag/layout/auth/forgot_password.phtml` — Forgot password form
- `themes/wondertag/layout/auth/reset_password.phtml` — Reset password form

## Files Modified

- `sql/internship_calendar.sql` — Extended Wo_Users, added Wo_AppsSessions, Wo_Bad_Login, Wo_Config
- `index.php` — Auth-gated routing via init.php
- `includes/functions.php` — 19 auth functions added
- `themes/wondertag/layout/container.phtml` — Auth-aware nav
- `themes/wondertag/css/internship-calendar.css` — Auth form styles
- `sources/timeline.php` — Removed redundant requires
- `sources/internship_calendar.php` — Same
- `sources/internship_calendar_dashboard.php` — Same
- `xhr/timeline.php` — Auth guard added
- `xhr/internship_calendar.php` — Auth guard added

## Auth Functions Added to `includes/functions.php`

| Function | Purpose |
|----------|---------|
| `Wo_Secure()` | Input sanitization |
| `Wo_LoadConfig()` | Load Wo_Config into array |
| `Wo_IsLogged()` | Check session/cookie auth |
| `Wo_GetUserFromSessionID()` | Token → user_id resolution |
| `Wo_Login()` | Credential verification (bcrypt) |
| `Wo_CreateLoginSession()` | Generate session token |
| `Wo_UserData()` | Full user record + enrichment |
| `Wo_RegisterUser()` | New user creation |
| `Wo_IsAdmin()` | Admin role check |
| `Wo_IsModerator()` | Mentor role check |
| `Wo_UserExists()` | Username/email uniqueness |
| `Wo_LastSeen()` | Update activity timestamp |
| `Wo_ResetPassword()` | Update password hash |
| `Wo_UserIdForLogin()` | Resolve login identifier |
| `Wo_SetLoginWithSession()` | Auto-login post-registration |
| `Wo_ValidateCsrf()` | CSRF token validation |
| `WoCanLogin()` | Brute-force check (5 per 15min) |
| `WoAddBadLoginLog()` | Log failed attempt |
| `Wo_DeleteBadLogins()` | Clear IP's failed attempts |
