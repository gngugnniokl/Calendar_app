# Leaderboard Feature Extraction — Full Implementation Plan

**Project:** Standalone Leaderboard Application  
**Source:** `Calendar_app/` (triBBBal Internship Platform)  
**Target:** `Leaderboard/` (Standalone, Production-Ready)  
**Date:** 2026-07-31  
**Status:** Planning Phase

---

## Table of Contents

1. [Dependency Audit](#1-dependency-audit)
2. [Architecture Report](#2-architecture-report)
3. [Final Folder Structure](#3-final-folder-structure)
4. [File Migration Checklist](#4-file-migration-checklist)
5. [Backend Extraction](#5-backend-extraction)
6. [Frontend Extraction](#6-frontend-extraction)
7. [SQL Extraction](#7-sql-extraction)
8. [Configuration Extraction](#8-configuration-extraction)
9. [Authentication Layer](#9-authentication-layer)
10. [AJAX Endpoint Audit](#10-ajax-endpoint-audit)
11. [Assets Audit](#11-assets-audit)
12. [Cron & Background Jobs](#12-cron--background-jobs)
13. [Production Improvements](#13-production-improvements)
14. [Testing & Verification Checklist](#14-testing--verification-checklist)

---

## 1. Dependency Audit

### 1.1 Direct Leaderboard Files

| File | Type | Lines | Status |
|------|------|-------|--------|
| `sources/leaderboard.php` | Controller | 27 | ✅ Keep |
| `xhr/leaderboard.php` | AJAX API | ~130 | ✅ Keep |
| `themes/wondertag/layout/leaderboard/content.phtml` | Template | ~55 | ✅ Keep |
| `themes/wondertag/layout/leaderboard/top7.phtml` | Template | ~150 | ✅ Keep |
| `themes/wondertag/layout/leaderboard/full_rankings.phtml` | Template | ~90 | ✅ Keep |
| `themes/wondertag/css/leaderboard.css` | Styles | 1367 | ✅ Keep |
| `themes/wondertag/javascript/leaderboard.js` | JavaScript | ~590 | ✅ Keep |
| `sql/leaderboard_schema.sql` | Database | ~290 | ✅ Keep |
| `server/cron/leaderboard-ranks.cron` | Automation | 2 | ✅ Keep |

### 1.2 Shared Infrastructure Dependencies (Required)

| File | Type | What Leaderboard Uses | Status |
|------|------|----------------------|--------|
| `index.php` | Router | Route registration + auth gate + container include | ♻ Refactor (strip non-leaderboard routes) |
| `assets/init.php` | Bootstrap | Session, DB, $wo global, auth check, CSRF | ♻ Refactor (keep core, remove calendar refs) |
| `config/database.php` | DB Connection | `get_db_connection()` + `.env.local` loading | ✅ Keep as-is |
| `includes/functions.php` | Functions | 12 functions used (see §1.3) | ♻ Refactor (extract only needed functions) |
| `themes/wondertag/layout/container.phtml` | Layout | HTML shell, nav, sidebar, script/CSS includes | ♻ Refactor (strip calendar, nudges, timeline) |
| `themes/wondertag/layout/auth/login.phtml` | Template | Login form for auth | ✅ Keep |
| `themes/wondertag/css/internship-calendar.css` | Styles | CSS variables (`:root` design tokens only) | ♻ Refactor → extract tokens into `base.css` |
| `xhr/auth.php` | AJAX | Login/register handlers | ✅ Keep |
| `sources/welcome.php` | Controller | Login page | ✅ Keep |
| `sources/logout.php` | Controller | Session destroy | ✅ Keep |

### 1.3 PHP Function Dependency Trace

Starting from `xhr/leaderboard.php` and `sources/leaderboard.php`, here is the full call chain:

```
Leaderboard-Specific Functions:
├── Wo_GetLeaderboardData(mysqli, array): array          ← Core ranking query
├── Wo_GetLeaderboardTotalCount(mysqli, string, array): int ← Pagination count
└── Wo_GetUserRank(mysqli, int): array                   ← User's personal rank

Authentication & Infrastructure Functions:
├── Wo_IsLogged(mysqli): bool                            ← Auth check
│   └── Wo_GetUserFromSessionID(mysqli, string): int|false
├── Wo_LoadConfig(mysqli): array                         ← Site config from DB
├── Wo_UserData(mysqli, int): ?array                     ← Full user object
├── Wo_LoadPage(string): string                          ← Template renderer
├── Wo_LastSeen(mysqli, int): void                       ← Activity tracker
├── Wo_Login(mysqli, string, string): bool               ← Auth
├── Wo_CreateLoginSession(mysqli, int): string           ← Session creation
├── Wo_ValidateCsrf(): bool                              ← CSRF protection
├── Wo_RegisterUser(mysqli, array): int|false            ← Registration
└── clean(string): string                                ← XSS sanitization

NOT Required (Calendar/Nudge/Timeline specific):
├── ❌ Wo_GetCurrentInternshipWeek()
├── ❌ Wo_GetInternshipCalendarEventsByWeek()
├── ❌ Wo_GetInternshipCalendarWeekBounds()
├── ❌ Wo_ExportInternshipCalendarExcel()
├── ❌ Wo_GetInternshipCalendarEvents()
├── ❌ Wo_GetInternshipCalendarStats()
├── ❌ Wo_GetInternshipCalendarEventById()
├── ❌ Wo_GetInternshipCalendarWeeks()
├── ❌ Wo_SendNudge()
├── ❌ Wo_NudgeBack()
├── ❌ Wo_GetNudgesForUser()
├── ❌ Wo_GetTimelineUser()
├── ❌ get_current_week()
├── ❌ format_date()
├── ❌ is_today()
├── ❌ truncate()
├── ❌ require_once 'timeline_calendar.php'
```

### 1.4 Database Table Dependencies

| Table | Purpose | Status |
|-------|---------|--------|
| `Wo_Users` | User accounts (core identity) | ✅ Keep (minimal columns) |
| `Wo_AppsSessions` | Session-based auth | ✅ Keep |
| `Wo_Config` | Site configuration KV store | ✅ Keep |
| `leaderboard_tokens` | Token balances & rank cache | ✅ Keep |
| `token_transactions` | Token ledger | ✅ Keep |
| `leaderboard_config` | Leaderboard settings | ✅ Keep |
| `calendar_events` | Calendar data | ❌ Remove |
| `calendar_nudges` | Nudges system | ❌ Remove |
| `Wo_Posts` | Timeline posts | ❌ Remove |
| `Wo_Bad_Login` | Brute force protection | ✅ Keep (security) |

### 1.5 CSS Variable Dependencies

`leaderboard.css` uses 94 CSS variable references. All are defined in the `:root` block of `internship-calendar.css`. Required tokens:

```css
/* Colors */
--paper, --surface, --ink, --ink-soft, --muted, --hairline
--pine, --pine-dark, --pine-tint
--gold (defined inline in leaderboard.css itself)

/* Typography */
--font-display: "Fraunces", serif
--font-body: "Inter", sans-serif
--font-mono: "JetBrains Mono", monospace

/* Layout */
--radius: 10px
--shadow-card: box-shadow definition

/* Dark Mode */
[data-theme="dark"] overrides for all above
```

### 1.6 External CDN Dependencies

| Resource | URL | Purpose |
|----------|-----|---------|
| Google Fonts | `fonts.googleapis.com` | Fraunces, Inter, JetBrains Mono |
| Font Awesome 6.4.0 | `cdnjs.cloudflare.com` | Icons (trophy, coins, charts, arrows) |

### 1.7 JavaScript Dependencies

`leaderboard.js` is **fully self-contained** (IIFE pattern, no external library imports). It depends only on:
- `window.TRIBBBAL_USER_ID` — set by `container.phtml` in a `<script>` tag
- `fetch()` — native browser API
- DOM elements rendered by the `.phtml` templates

No jQuery, no framework, no module bundler required.

---

## 2. Architecture Report

### 2.1 Current Architecture (Source)

```
Request → index.php → assets/init.php (bootstrap)
                    → sources/leaderboard.php (controller)
                    → container.phtml (layout shell)
                    → leaderboard/content.phtml → top7.phtml / full_rankings.phtml

AJAX   → xhr/leaderboard.php → assets/init.php → functions.php
                              → JSON response
```

### 2.2 Target Architecture (Standalone)

Same pattern, but stripped of all non-leaderboard concerns:

```
Request → index.php → assets/init.php (bootstrap)
                    → sources/leaderboard.php (controller)
                    → OR sources/welcome.php (login)
                    → OR sources/logout.php
                    → container.phtml (leaderboard-only shell)
                    → leaderboard/content.phtml → top7.phtml / full_rankings.phtml

AJAX   → xhr/leaderboard.php → assets/init.php → includes/functions.php
                              → JSON response

Auth   → xhr/auth.php → assets/init.php → JSON (login/register)
```

### 2.3 Dependency Classification Summary

| Classification | Count | Description |
|---|---|---|
| ✅ Keep | 16 files | Direct leaderboard + auth + config |
| ♻ Refactor | 5 files | Shared files that need calendar/timeline code stripped |
| ❌ Remove | 25+ files | Calendar, nudges, timeline, notifications |

---

## 3. Final Folder Structure

```
Leaderboard/
│
├── .env.local.example          ← Template for DB credentials
├── .env.local                  ← (gitignored) Actual credentials
├── .gitignore
├── index.php                   ← Router (leaderboard + auth pages only)
├── README.md                   ← Setup & run instructions
├── setup.sh                    ← One-command setup script
│
├── assets/
│   └── init.php                ← Bootstrap (session, DB, $wo, CSRF)
│
├── config/
│   └── database.php            ← get_db_connection() with .env.local support
│
├── includes/
│   └── functions.php           ← Only leaderboard + auth helpers
│
├── sources/
│   ├── leaderboard.php         ← Leaderboard page controller
│   ├── welcome.php             ← Login page controller
│   └── logout.php              ← Logout controller
│
├── sql/
│   ├── schema.sql              ← Full standalone schema (users + leaderboard)
│   └── seed_data.sql           ← Demo users + token transactions
│
├── server/
│   └── cron/
│       └── leaderboard-ranks.cron  ← Cron definition
│
├── themes/
│   └── wondertag/
│       ├── css/
│       │   ├── base.css        ← Design tokens + global styles (extracted from internship-calendar.css)
│       │   └── leaderboard.css ← Leaderboard-specific styles
│       ├── javascript/
│       │   └── leaderboard.js  ← All client-side logic
│       └── layout/
│           ├── container.phtml ← Simplified shell (header + sidebar for leaderboard only)
│           ├── auth/
│           │   └── login.phtml ← Login form
│           └── leaderboard/
│               ├── content.phtml
│               ├── top7.phtml
│               └── full_rankings.phtml
│
├── upload/
│   └── photos/
│       └── d-avatar.jpg        ← Default avatar placeholder
│
├── xhr/
│   ├── auth.php                ← Login/register AJAX
│   └── leaderboard.php         ← Leaderboard data API
│
└── docs/
    └── api-reference.md        ← Endpoint documentation
```

**Directory Purposes:**

| Directory | Purpose |
|-----------|---------|
| `assets/` | Application bootstrap (runs on every request) |
| `config/` | Database connection setup |
| `includes/` | Shared PHP helper functions |
| `sources/` | Page controllers (one per route) |
| `sql/` | Database schema and seed files |
| `server/cron/` | Scheduled task definitions |
| `themes/wondertag/css/` | Stylesheets |
| `themes/wondertag/javascript/` | Client-side scripts |
| `themes/wondertag/layout/` | HTML templates (.phtml) |
| `upload/photos/` | User-uploaded & default avatars |
| `xhr/` | AJAX/API endpoints |
| `docs/` | Documentation |

---

## 4. File Migration Checklist

### 4.1 Direct Copy (No Changes)

| Original | New Location | Notes |
|----------|-------------|-------|
| `config/database.php` | `config/database.php` | No changes needed |
| `sources/leaderboard.php` | `sources/leaderboard.php` | No changes needed |
| `themes/wondertag/layout/leaderboard/content.phtml` | `themes/wondertag/layout/leaderboard/content.phtml` | No changes needed |
| `themes/wondertag/layout/leaderboard/top7.phtml` | `themes/wondertag/layout/leaderboard/top7.phtml` | No changes needed |
| `themes/wondertag/layout/leaderboard/full_rankings.phtml` | `themes/wondertag/layout/leaderboard/full_rankings.phtml` | No changes needed |
| `themes/wondertag/css/leaderboard.css` | `themes/wondertag/css/leaderboard.css` | No changes needed |
| `themes/wondertag/javascript/leaderboard.js` | `themes/wondertag/javascript/leaderboard.js` | No changes needed |
| `server/cron/leaderboard-ranks.cron` | `server/cron/leaderboard-ranks.cron` | No changes needed |

### 4.2 Refactored Files

#### `index.php`

```
Original: Calendar_app/index.php
New:      Leaderboard/index.php

Changes:
  • Remove all internship_calendar routes
  • Remove timeline, nudges, notifications routes  
  • Keep: leaderboard, welcome, logout
  • Change default logged-in redirect from 'timeline' to 'leaderboard'
  • Change 404 fallback link from 'internship_calendar' to 'leaderboard'

Dependencies Removed:
  • internship_calendar_*, timeline, nudges, notifications

Dependencies Added:
  • None
```

#### `assets/init.php`

```
Original: Calendar_app/assets/init.php
New:      Leaderboard/assets/init.php

Changes:
  • None required — file is already clean of calendar references
  • Keep: session start, DB include, functions include, $wo build, auth check, CSRF

Dependencies Removed:
  • None (already generic)

Dependencies Added:
  • None
```

#### `includes/functions.php`

```
Original: Calendar_app/includes/functions.php (1200+ lines)
New:      Leaderboard/includes/functions.php (~250 lines)

Changes:
  • Remove `require_once 'timeline_calendar.php'` at top
  • Remove ALL calendar functions (Wo_GetCurrentInternshipWeek, Wo_GetInternshipCalendar*, etc.)
  • Remove ALL nudge functions (Wo_SendNudge, Wo_NudgeBack, Wo_GetNudgesForUser)
  • Remove Wo_GetTimelineUser()
  • Remove format_date(), is_today(), get_current_week(), truncate()
  • Keep ONLY:
    - clean()
    - Wo_LoadPage()
    - Wo_LoadConfig()
    - Wo_IsLogged()
    - Wo_GetUserFromSessionID()
    - Wo_Login()
    - Wo_CreateLoginSession()
    - Wo_UserData()
    - Wo_RegisterUser()
    - Wo_IsAdmin()
    - Wo_IsModerator()
    - Wo_UserExists()
    - Wo_LastSeen()
    - Wo_ResetPassword()
    - Wo_UserIdForLogin()
    - Wo_SetLoginWithSession()
    - Wo_ValidateCsrf()
    - Wo_Secure()
    - Wo_GetLeaderboardData()
    - Wo_GetLeaderboardTotalCount()
    - Wo_GetUserRank()

Dependencies Removed:
  • timeline_calendar.php (entire file)
  • All calendar_events queries
  • All calendar_nudges queries
  • All Wo_Posts queries

Dependencies Added:
  • None
```

#### `themes/wondertag/layout/container.phtml`

```
Original: Calendar_app/themes/wondertag/layout/container.phtml (~200 lines)
New:      Leaderboard/themes/wondertag/layout/container.phtml

Changes:
  • Remove: internship-calendar.css link
  • Add: base.css link (extracted design tokens)
  • Remove: Calendar sidebar link
  • Remove: Dashboard sidebar link
  • Remove: Timeline sidebar link
  • Remove: Nudges sidebar link
  • Keep: Leaderboard sidebar link (make it the only/primary nav item)
  • Remove: internship-calendar.js script include
  • Remove: include of 'internship_calendar/includes/event-modal.phtml'
  • Keep: Profile dropdown with leaderboard stats (tokens, rank, progress bar)
  • Keep: Dark mode toggle
  • Keep: Logout link
  • Change brand link from '?link1=internship_calendar_home' to '?link1=leaderboard'
  • Change brand text from 'Tribbbal Internship' to 'triBBBal Leaderboard'

Dependencies Removed:
  • internship-calendar.css
  • internship-calendar.js
  • event-modal.phtml

Dependencies Added:
  • base.css (new file with design tokens)
```

#### `themes/wondertag/css/internship-calendar.css` → `themes/wondertag/css/base.css`

```
Original: Calendar_app/themes/wondertag/css/internship-calendar.css (full file)
New:      Leaderboard/themes/wondertag/css/base.css (~120 lines)

Changes:
  • Extract ONLY:
    - :root { ... } block (lines 21-45) — all design tokens
    - [data-theme="dark"] { ... } block (lines 48-68) — dark mode tokens
    - Dark mode icon toggle classes (line 77-80)
    - Body background pattern (lines 72-76)
    - Base button styles (.btn, .btn-primary, .btn-ghost, .btn-sm)
    - Site header styles (.site-header, .header-inner)
    - Sidebar styles (.global-sidebar, .nav-icons, .nav-icon)
    - Auth page styles (.auth-page, .auth-card, .auth-form, .auth-error, etc.)
  • Remove ALL:
    - Calendar grid styles
    - Timeline styles
    - Event detail styles
    - Dashboard chart styles
    - Search/filter styles (kept in leaderboard.css)
    - Anything referencing .calendar-*, .event-*, .week-*, .timeline-*

Dependencies Removed:
  • Every calendar, timeline, nudge, and notification-specific CSS class

Dependencies Added:
  • None
```

#### `xhr/auth.php`

```
Original: Calendar_app/xhr/auth.php
New:      Leaderboard/xhr/auth.php

Changes:
  • No changes needed — already self-contained
  • Handles: login, register
  • Uses: Wo_ValidateCsrf(), Wo_Login(), Wo_UserIdForLogin(), Wo_CreateLoginSession(), Wo_RegisterUser()

Dependencies Removed:
  • None

Dependencies Added:
  • None
```

#### `xhr/leaderboard.php`

```
Original: Calendar_app/xhr/leaderboard.php
New:      Leaderboard/xhr/leaderboard.php

Changes:
  • No changes needed
  • All 3 actions (top7, rankings, user) are self-contained
  • Uses: Wo_IsLogged(), Wo_GetLeaderboardData(), Wo_GetLeaderboardTotalCount(), Wo_GetUserRank()

Dependencies Removed:
  • None

Dependencies Added:
  • None
```

---

## 5. Backend Extraction

### 5.1 Functions to Extract (Final List)

Extract these functions IN ORDER (some depend on others):

```php
// === Utility ===
clean(string): string

// === Template Engine ===
Wo_LoadPage(string): string

// === Configuration ===
Wo_LoadConfig(mysqli): array
Wo_Secure(mysqli, string): string

// === Authentication ===
Wo_IsLogged(mysqli): bool
Wo_GetUserFromSessionID(mysqli, string): int|false
Wo_Login(mysqli, string, string): bool
Wo_CreateLoginSession(mysqli, int): string
Wo_UserData(mysqli, int): ?array
Wo_RegisterUser(mysqli, array): int|false
Wo_UserIdForLogin(mysqli, string): int|false
Wo_SetLoginWithSession(mysqli, string): void
Wo_ValidateCsrf(): bool
Wo_LastSeen(mysqli, int): void
Wo_ResetPassword(mysqli, int, string): bool
Wo_UserExists(mysqli, string): bool
Wo_IsAdmin(): bool
Wo_IsModerator(): bool

// === Leaderboard Core ===
Wo_GetLeaderboardData(mysqli, array): array
Wo_GetLeaderboardTotalCount(mysqli, string, array): int
Wo_GetUserRank(mysqli, int): array
```

### 5.2 Bootstrap Chain

```
index.php
  └── require assets/init.php
        ├── ini_set (security headers)
        ├── session_start()
        ├── require config/database.php → $conn
        ├── require includes/functions.php
        ├── $wo['config'] = Wo_LoadConfig($conn)
        ├── $wo['loggedin'] = Wo_IsLogged($conn)
        ├── if logged in: load user data
        └── CSRF token generation
```

No changes needed to this chain.

---

## 6. Frontend Extraction

### 6.1 CSS Architecture

**New file: `base.css`** (extracted from `internship-calendar.css`)

Contains:
- All `:root` CSS custom properties (both light and dark mode)
- Body background styling
- `.site-header` and `.header-inner`
- `.global-sidebar`, `.nav-icons`, `.nav-icon`, `.bg-gold`
- `.btn`, `.btn-primary`, `.btn-ghost`, `.btn-sm`
- `.auth-page`, `.auth-card`, `.auth-form`, `.auth-error`, `.form-group`, `.input-icon-wrap`
- `.page-wrap`, `.app-layout`, `.app-layout-guest`
- Dark mode icon toggle (`.theme-icon-light`, `.theme-icon-dark`)

**Kept as-is: `leaderboard.css`** (1367 lines)

Already self-contained. No changes. Contains:
- `.hero-banner` (podium hero)
- `.podium`, `.podium__place`, `.podium__avatar`, `.podium__crown`
- `.rank-cards`, `.rank-card`
- `.full-rankings-container`, `.rankings-table`
- `.filter-pills`, `.pill`
- `.multiselect-wrapper`, `.multiselect-dropdown`
- `.pagination-wrapper`, `.page-btn`, `.page-num`
- `.leaderboard-footer-strip`
- `.quick-filters`

### 6.2 JavaScript

**Kept as-is: `leaderboard.js`** (~590 lines)

Self-contained IIFE. No external deps. Handles:
- View switching (Top 7 ↔ Full Rankings)
- Fetch API calls to `xhr/leaderboard.php`
- DOM rendering (podium, table, pagination)
- Search with debounce
- Time filter pills
- User type multiselect
- Sticky progress bar
- URL history state management
- Fallback to empty data gracefully

Config embedded in JS:
```javascript
CONFIG = {
  apiBase: "xhr/leaderboard.php",        // Primary
  fallbackApiBase: "/api/leaderboard",    // Fallback (unused in standalone)
  currentUserId: window.TRIBBBAL_USER_ID,
  itemsPerPage: 15,
  debounceDelay: 300,
  kingGoalTokens: 100000,
}
```

### 6.3 Template Hierarchy

```
container.phtml
├── <head>
│   ├── Google Fonts (CDN)
│   ├── Font Awesome 6.4 (CDN)
│   ├── base.css
│   ├── leaderboard.css
│   └── <script> window.TRIBBBAL_USER_ID = ...
├── <body>
│   ├── <header> (brand + profile dropdown with rank/tokens)
│   ├── <aside> (sidebar with single Leaderboard icon)
│   ├── <main> → $wo['content']
│   │   └── leaderboard/content.phtml
│   │       ├── leaderboard/top7.phtml
│   │       └── leaderboard/full_rankings.phtml
│   └── <script> leaderboard.js
└── Profile dropdown JS (inline)
```

### 6.4 Font Awesome Icons Used

```
fa-solid fa-trophy         ← Hero banner, sidebar
fa-solid fa-coins          ← Dropdown stats
fa-solid fa-chart-simple   ← Dropdown rank
fa-solid fa-crown          ← Podium
fa-solid fa-arrow-left     ← Back button
fa-solid fa-magnifying-glass ← Search
fa-solid fa-chevron-down   ← Dropdown arrow
fa-solid fa-chevron-left   ← Pagination prev
fa-solid fa-chevron-right  ← Pagination next
fa-solid fa-arrow-trend-up ← Footer strip
fa-solid fa-moon           ← Dark mode toggle
fa-solid fa-sun            ← Dark mode toggle
fa-solid fa-arrow-right-from-bracket ← Logout
fa-solid fa-user           ← Login form
fa-solid fa-lock           ← Login form
fa-solid fa-eye            ← Password toggle
fa-solid fa-spinner        ← Loading states
```

---

## 7. SQL Extraction

### 7.1 Standalone Schema (`sql/schema.sql`)

The new schema combines the minimal user tables with the full leaderboard system:

```sql
-- Tables needed (in creation order):
1. Wo_Users           (stripped to essential columns)
2. Wo_AppsSessions    (for auth)
3. Wo_Config          (for site_url, etc.)
4. Wo_Bad_Login       (brute force protection)
5. leaderboard_config (leaderboard settings)
6. leaderboard_tokens (rank cache per user)
7. token_transactions (full ledger)

-- Triggers:
8. trg_token_transactions_before_insert  (sets balance_after)
9. trg_token_transactions_after_insert   (updates leaderboard_tokens)

-- Stored Procedures:
10. recalculateRanks()                    (full rank rebuild)
```

### 7.2 Tables to REMOVE from Schema

```
❌ calendar_events
❌ calendar_event_completions
❌ calendar_nudges
❌ Wo_Posts
```

### 7.3 `Wo_Users` Minimal Columns (for standalone)

```sql
CREATE TABLE Wo_Users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(32) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  password VARCHAR(255) NOT NULL DEFAULT '',
  first_name VARCHAR(60) NOT NULL DEFAULT '',
  last_name VARCHAR(32) NOT NULL DEFAULT '',
  avatar VARCHAR(100) NOT NULL DEFAULT 'upload/photos/d-avatar.jpg',
  cover VARCHAR(100) NOT NULL DEFAULT 'upload/photos/d-cover.jpg',
  about TEXT,
  admin ENUM('0','1','2','3') NOT NULL DEFAULT '0',
  active INT DEFAULT 1,
  lastseen INT DEFAULT 0,
  banned TINYINT DEFAULT 0,
  joined INT DEFAULT 0,
  profile_color VARCHAR(7) DEFAULT '',
  UNIQUE KEY idx_username (username),
  UNIQUE KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Removed columns (not needed for leaderboard):
- `tribbbalGroup`, `Race`, `ip_address`, `banned_reason`, `status`
- `email_code`, `time_code_sent`, `phone_number`, `sms_code`
- `verified`, `two_factor`, `two_factor_verified`, `two_factor_hash`, `two_factor_method`
- `last_login_data`, `start_up`, `language`, `showlastseen`, `registered`

### 7.4 Seed Data (`sql/seed_data.sql`)

- 3 default users (intern, mentor, admin) — same as current
- Adapt from `to-ir-project/documents/leaderboard-implementation/dummy_tokens.sql`
- Add 7-10 dummy users with varied token balances for visual testing
- Run `CALL recalculateRanks()` at end

---

## 8. Configuration Extraction

### 8.1 Environment Variables (`.env.local`)

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=tribbbal_leaderboard
```

### 8.2 `Wo_Config` Table Entries (Required)

```sql
INSERT INTO Wo_Config (name, value) VALUES
('site_url', 'http://localhost:8080'),
('user_registration', '1'),
('maintenance_mode', '0');
```

Only `site_url` is actively used by the leaderboard (for avatar URL construction in `xhr/leaderboard.php`).

### 8.3 `leaderboard_config` Table Entries

Already defined in `leaderboard_schema.sql`. Key values:
- `king_queen_threshold_tokens`: 100000
- `reward_population_cap`: 1000000
- `recalc_batch_size`: 5000
- `top_rank_cache_limit`: 7

---

## 9. Authentication Layer

### 9.1 Minimum Auth Requirements

The leaderboard needs:
- `$wo['loggedin']` — boolean, is user authenticated
- `$wo['user']['user_id']` — integer, for "My Rank" and highlight row
- `$wo['user']['username']` — for profile display
- `$wo['user']['avatar']` — for profile dropdown
- `$wo['user']['first_name']` + `$wo['user']['last_name']` — for display name

### 9.2 Auth Flow (Preserved)

```
1. User visits ?link1=leaderboard
2. index.php checks: is 'leaderboard' in $protected_pages? YES
3. Is $wo['loggedin']? If NO → redirect to ?link1=welcome
4. If YES → require sources/leaderboard.php → render

Login:
1. User on ?link1=welcome sees login form
2. JS submits to xhr/auth.php?s=login
3. Server validates, creates session, returns success
4. JS redirects to ?link1=leaderboard
```

### 9.3 Auth Pages Included

| Route | Controller | Template | Purpose |
|-------|-----------|----------|---------|
| `?link1=welcome` | `sources/welcome.php` | `auth/login.phtml` | Login |
| `?link1=logout` | `sources/logout.php` | — (redirect) | Destroy session |

Registration can optionally be included or removed for production (controlled by `Wo_Config['user_registration']`).

---

## 10. AJAX Endpoint Audit

### 10.1 `xhr/leaderboard.php`

#### Action: `top7`

```
Method: GET
URL: xhr/leaderboard.php?action=top7&account_types=0,1,2
Auth: Required (Wo_IsLogged check)

Parameters:
  - action: "top7" (required)
  - account_types: comma-separated "0,1,2" (optional, default all)

Response (success):
{
  "success": true,
  "data": {
    "podium": [
      { "user_id": 1, "username": "...", "name": "...", "avatar": "...",
        "points": 105000, "tokens": 105000, "rank": 1,
        "status": "KING", "trend": "neutral" }
    ],
    "others": [ ... same shape, ranks 4-7 ... ]
  }
}

Backend calls:
  - Wo_GetLeaderboardData($conn, ['limit' => 7, 'account_types' => [...]])
```

#### Action: `rankings`

```
Method: GET
URL: xhr/leaderboard.php?action=rankings&page=1&limit=15&search=&account_types=0,1,2
Auth: Required

Parameters:
  - action: "rankings" (required)
  - limit: int (default 20)
  - page: int (default 1)
  - search: string (optional, filters by username/name)
  - account_types: comma-separated (optional)

Response (success):
{
  "success": true,
  "data": [ array of user objects ],
  "total": 150,
  "totalPages": 10,
  "pagination": { "limit": 15, "page": 1, "offset": 0 }
}

Backend calls:
  - Wo_GetLeaderboardData($conn, ['limit', 'offset', 'search', 'account_types'])
  - Wo_GetLeaderboardTotalCount($conn, $search, $account_types)
```

#### Action: `user`

```
Method: GET
URL: xhr/leaderboard.php?action=user&id=5
Auth: Required

Parameters:
  - action: "user" (required)
  - (uses $wo['user']['user_id'] from session)

Response:
{
  "success": true,
  "data": {
    "tokens": 25400,
    "rank": 12,
    "progress_pct": 25.4
  }
}

Backend calls:
  - Wo_GetUserRank($conn, $user_id)
```

### 10.2 `xhr/auth.php`

```
Method: POST
URL: xhr/auth.php

Actions (via POST 's' parameter):
  - s=login: { username, password, csrf_token } → session creation
  - s=register: { username, email, password, first_name, last_name, csrf_token } → user creation

Response:
{ "success": true/false, "message": "...", "data": {} }
```

---

## 11. Assets Audit

### 11.1 Images & Media

| Asset | Current Path | Action |
|-------|-------------|--------|
| Default avatar | `upload/photos/d-avatar.jpg` | ✅ Copy (or create a simple SVG placeholder) |
| Default cover | `upload/photos/d-cover.jpg` | ❌ Remove (not displayed in leaderboard) |
| User avatars | `upload/photos/user_*.jpg` | N/A (populated at runtime) |

### 11.2 Fonts (CDN - No Local Files)

All fonts are loaded via Google Fonts CDN. No local font files to copy.

### 11.3 Icons

All icons are Font Awesome 6.4 (CDN). No local icon files.

### 11.4 SVGs / Inline Graphics

None. The podium crown is an emoji (👑) and Font Awesome icon (`fa-crown`).

---

## 12. Cron & Background Jobs

### 12.1 Current Setup

```cron
*/5 * * * * mysql -u root -p'password' -D internship_calendar -e "CALL recalculateRanks();"
```

### 12.2 Standalone Setup

```cron
*/5 * * * * mysql -u $DB_USER -p'$DB_PASS' -D tribbbal_leaderboard -e "CALL recalculateRanks();"
```

### 12.3 Alternative Approaches (Production Recommendations)

**Option A: PHP CLI Script (Recommended)**

Create `server/cron/recalculate_ranks.php`:
```php
<?php
require_once __DIR__ . '/../../config/database.php';
$conn = get_db_connection();
mysqli_query($conn, "CALL recalculateRanks()");
echo date('Y-m-d H:i:s') . " — Ranks recalculated.\n";
```

Cron:
```
*/5 * * * * php /path/to/Leaderboard/server/cron/recalculate_ranks.php >> /var/log/leaderboard-ranks.log 2>&1
```

**Option B: Event-Driven (Advanced)**

After every `INSERT INTO token_transactions`, trigger an immediate single-user rank update. The stored procedure already handles this via the `AFTER INSERT` trigger. The 5-minute cron becomes a "consistency check" rather than the primary mechanism.

**Option C: Queue Worker (At Scale)**

For 100k+ users, move rank recalculation to a job queue (Redis + worker). Out of scope for initial extraction but documented as a future path.

---

## 13. Production Improvements

### 13.1 Performance

| Improvement | Priority | Effort |
|-------------|----------|--------|
| Cache Top 7 results in Redis/APCu for 60s | High | Low |
| Add `LIMIT` to `recalculateRanks()` temp table if >100k users | Medium | Medium |
| Use `COUNT(*)` with covering index instead of full table scan | Medium | Low |
| Add HTTP `Cache-Control` headers to `xhr/leaderboard.php?action=top7` | Medium | Low |

### 13.2 Security Hardening

| Improvement | Priority | Effort |
|-------------|----------|--------|
| Rate-limit `xhr/leaderboard.php` (e.g., 60 req/min per IP) | High | Medium |
| Add `Content-Security-Policy` header | Medium | Low |
| Validate `account_types` to only allow `['0','1','2','3']` | High | Low |
| Add `X-Content-Type-Options: nosniff` | Low | Low |
| Prevent negative-balance exploitation in `token_transactions` | High | Low (CHECK constraint already exists) |

### 13.3 Avatar Optimization

| Improvement | Priority | Effort |
|-------------|----------|--------|
| Generate 48x48 thumbnails on upload for leaderboard cards | Medium | Medium |
| Use `loading="lazy"` on avatar `<img>` in full rankings table | Low | Low |
| Serve default avatar as inline SVG instead of image file | Low | Low |

### 13.4 Error Handling & Logging

| Improvement | Priority | Effort |
|-------------|----------|--------|
| Return proper HTTP status codes from xhr (401, 400, 500) | High | Low |
| Log failed queries to `server/logs/error.log` | Medium | Low |
| Add try/catch in `recalculateRanks()` cron script | Medium | Low |
| Show user-friendly error state in JS when API fails | Medium | Low |

### 13.5 API Response Optimization

| Improvement | Priority | Effort |
|-------------|----------|--------|
| Add `ETag` / `Last-Modified` headers for conditional requests | Low | Medium |
| Compress JSON responses (`ob_start('ob_gzhandler')`) | Medium | Low |
| Strip unnecessary fields from API response (e.g., `admin`, `joined` when not displayed) | Low | Low |

---

## 14. Testing & Verification Checklist

### Phase 1: Setup Verification

- [ ] `schema.sql` runs without errors on a fresh MySQL/MariaDB instance
- [ ] `seed_data.sql` inserts demo users and token transactions
- [ ] `CALL recalculateRanks()` completes and populates `leaderboard_tokens.current_rank`
- [ ] PHP built-in server starts: `php -S localhost:8080`
- [ ] Homepage redirects to `?link1=welcome` (unauthenticated)
- [ ] Login works with demo credentials (e.g., `intern1` / `password`)

### Phase 2: Core Functionality

- [ ] After login, redirects to `?link1=leaderboard`
- [ ] Top 7 podium renders with data from DB
- [ ] "View Full Rankings" switches to table view
- [ ] Table shows paginated rows with correct rank numbers
- [ ] Search filters results by name/username
- [ ] User type filter (Interns/Mentors/Admins) works
- [ ] Time filter pills are wired (even if all show same data initially)
- [ ] "Back to Top 7" button works
- [ ] Profile dropdown shows user tokens and rank
- [ ] Footer progress bar shows percentage toward 100k goal
- [ ] Pagination next/prev/page-number buttons work
- [ ] Current user's row is highlighted in rankings table
- [ ] "My Rank" button scrolls to highlighted row

### Phase 3: Auth & Security

- [ ] Unauthenticated access to `?link1=leaderboard` redirects to login
- [ ] XHR requests without session return `{"success": false, "message": "Authentication required."}`
- [ ] CSRF token is validated on login form submission
- [ ] SQL injection attempt in search field is safely handled (prepared statements)
- [ ] XSS attempt in search field is safely escaped

### Phase 4: Styling & Responsive

- [ ] Light mode renders correctly
- [ ] Dark mode toggle works and persists via localStorage
- [ ] Podium looks correct on desktop (1200px+)
- [ ] Layout is usable on tablet (768px)
- [ ] Layout is usable on mobile (375px)
- [ ] No console errors in browser DevTools

### Phase 5: Automation

- [ ] Cron script (`server/cron/recalculate_ranks.php`) runs manually and updates ranks
- [ ] Adding a `token_transaction` row via SQL updates `leaderboard_tokens` automatically (trigger)
- [ ] `recalculateRanks()` correctly identifies the first user to hit threshold

---

## Execution Order

```
Phase 1: Create folder structure                    [15 min]
Phase 2: Write sql/schema.sql (merged standalone)   [20 min]
Phase 3: Write sql/seed_data.sql                    [10 min]
Phase 4: Extract includes/functions.php             [15 min]
Phase 5: Create config/database.php (copy)          [2 min]
Phase 6: Create assets/init.php (copy)              [2 min]
Phase 7: Create index.php (refactored)              [10 min]
Phase 8: Copy sources/ (leaderboard, welcome, logout) [5 min]
Phase 9: Create themes/wondertag/css/base.css       [20 min]
Phase 10: Copy leaderboard.css                      [2 min]
Phase 11: Copy leaderboard.js                       [2 min]
Phase 12: Refactor container.phtml                  [15 min]
Phase 13: Copy leaderboard templates (3 files)      [2 min]
Phase 14: Copy auth template                        [2 min]
Phase 15: Copy xhr/auth.php + xhr/leaderboard.php   [5 min]
Phase 16: Create cron script                        [5 min]
Phase 17: Write README.md + setup.sh                [10 min]
Phase 18: Write .env.local.example + .gitignore     [5 min]
Phase 19: Test full flow                            [20 min]
```

---

## Summary

The Leaderboard is a **well-isolated feature** with clean boundaries. The extraction is straightforward because:

1. **No shared state** — Leaderboard data lives in its own 3 tables
2. **Self-contained JS** — IIFE with no framework deps
3. **CSS is scoped** — All classes prefixed with `.hero-banner`, `.podium`, `.rank-*`, `.leaderboard-*`
4. **Single API file** — `xhr/leaderboard.php` handles everything
5. **Minimal auth surface** — Only needs user_id, username, and avatar from session

The main work is:
- Stripping calendar/timeline references from `container.phtml` and `functions.php`
- Extracting CSS design tokens into a standalone `base.css`
- Writing a merged `schema.sql` that includes user tables + leaderboard tables

Everything else is direct copy.
