# triBBBal Leaderboard Module — Implementation Package

**Project:** triBBBal Social Media Platform
**Module:** Leaderboard
**Requested By:** Lortir Pierre-Louis, triBBBal LLC
**Priority:** High
**Status:** Ready for Integration

---

## Table of Contents

1. [Overview](#1-overview)
2. [Package Contents](#2-package-contents)
3. [UI Mockups](#3-ui-mockups)
4. [Business Rules](#4-business-rules)
5. [Technical Stack](#5-technical-stack)
6. [Database Setup](#6-database-setup)
7. [Backend Integration](#7-backend-integration)
8. [Frontend Integration](#8-frontend-integration)
9. [API Reference](#9-api-reference)
10. [Rank Recalculation (Cron)](#10-rank-recalculation-cron)
11. [Implementation Phases & Time Estimates](#11-implementation-phases--time-estimates)
12. [Acceptance Criteria](#12-acceptance-criteria)
13. [Design System Reference](#13-design-system-reference)

---

## 1. Overview

This package contains the complete frontend and backend implementation for the **triBBBal Leaderboard module** — a gamification feature that ranks platform members by tokens earned through activity, and rewards the top performers with King/Queen status and profit-sharing incentives.

The module consists of two views:

- **Top 7 Featured View** — the main Leaderboard page, showing a hero banner, business rules, and the top 7 ranked members with a gold/silver/bronze podium for the top 3.
- **Full Rankings View** — a paginated, searchable table listing all platform members by rank, with time filters, trend arrows, and a highlighted "Your Rank" row for the logged-in user.

Both views include a **sticky progress bar** at the bottom of the screen showing the logged-in user's current rank, token count, and visual progress toward the 100,000-token King/Queen goal.

The design strictly follows the established triBBBal dark theme design system — identical CSS variables, layout structure, sidebar navigation, and header as the Movies module.

---

## 2. Package Contents

```
leaderboard-implementation/
│
├── README.md                              ← This file
├── leaderboard-work-order.md              ← Developer work order document
├── tribbbal_leaderboard_top7.png          ← Approved mockup: Top 7 view
├── tribbbal_leaderboard_fullrankings.png  ← Approved mockup: Full Rankings view
│
├── frontend/
│   ├── leaderboard.html                   ← Full page markup (both views)
│   ├── leaderboard.css                    ← Complete stylesheet
│   └── leaderboard.js                     ← All interactivity & data logic
│
└── backend/
    ├── LeaderboardAPI.php                 ← PHP API router + service class
    └── leaderboard-schema.sql             ← Database tables, procedure, config
```

---

## 3. UI Mockups

Two approved mockups are included in this package and must be used as the visual reference during implementation.

### Top 7 Featured View (`tribbbal_leaderboard_top7.png`)

The main Leaderboard page includes the following elements from top to bottom:

- **Hero Banner** — dark purple-to-black gradient with a floating, glowing gold 3D trophy. Contains the page title "Leaderboard", subtitle "Top Kings & Queens — Earn tokens, claim your throne", and two CTA buttons: "View My Rank" (red) and "View Full Rankings →" (outlined).
- **Rules Section** — three-column card displaying the business rules (see Section 4).
- **Time Filter Pills** — centered pills: "All Time" (red/active), "This Month", "This Week", "Today". A "View Full Rankings →" text link sits on the right.
- **Podium (Top 3)** — Rank #1 is elevated at center with a gold crown emoji, gold glowing border, and a KING/QUEEN badge. Ranks #2 and #3 flank it with silver and bronze borders respectively. All three show avatar, rank number, token count (in red), and status badge.
- **Lower Ranks Grid (4–7)** — four equal dark cards in a row below the podium.
- **Sticky Progress Bar** — fixed to the bottom: "Your Rank: #4,521 | Tokens: 12,450 | 87,550 tokens to King/Queen status" with a red progress bar.

### Full Rankings View (`tribbbal_leaderboard_fullrankings.png`)

Accessed by clicking "View Full Rankings →". Includes:

- **Page Header** — "← Back to Top 7" button on the left, "Full Rankings" title centered, "Search members..." input on the right.
- **Filter Row** — same time filter pills on the left, total member count ("1,247,832 Members Ranked") on the right.
- **Rankings Table** — columns: Rank | Member (avatar + name) | Tokens | Status | Joined | Trend. Top 3 rows show crown icons and KING/QUEEN badges. Trend column shows green ▲, red ▼, or orange → arrows.
- **"Your Rank" Row** — the logged-in user's row is highlighted with a red border/glow so they can instantly locate themselves.
- **Pagination** — Previous / 1 2 3 ... 847 848 / Next buttons at the bottom.
- **Sticky Progress Bar** — same as the Top 7 view, persists across both views.

---

## 4. Business Rules

The following rules must be accurately reflected in both the UI display and the backend logic.

| Rule | Detail |
|---|---|
| **King/Queen Eligibility** | The first 1,000,000 members to earn 100,000 tokens achieve King or Queen status |
| **Profit Share (General)** | King/Queen status grants a share in a pool for **5% of triBBBal's yearly net profit for life** |
| **First Member Reward** | The absolute first member to reach 100,000 tokens receives **$2,000 USD cash** + **0.5% yearly net profit for 5 years** |
| **Quality Judging** | Profiles are judged on quality and positivity of content using a **90/10 judging scale** |

These values are seeded into the `leaderboard_config` database table and can be updated without code changes.

---

## 5. Technical Stack

| Component | Technology | Notes |
|---|---|---|
| **Frontend** | HTML5, CSS3, Vanilla JavaScript (ES6+) | No external JS frameworks required |
| **Backend API** | PHP 7.4+ | All logic in `LeaderboardAPI.php` |
| **Database** | MySQL / MariaDB | Schema in `leaderboard-schema.sql` |
| **Caching** | Redis (optional) | Highly recommended for `/top7` endpoint |
| **Design System** | CSS Custom Properties | Matches `movies.css` variables exactly |

---

## 6. Database Setup

### Step 1 — Run the Schema Script

Execute the SQL script against your `tribbbal` database. This creates three tables and seeds the business rules config:

```bash
mysql -u tribbbal_user -p tribbbal < backend/leaderboard-schema.sql
```

### Tables Created

**`leaderboard_tokens`** — One row per user. Stores current token balance, rank, status (MEMBER / KING / QUEEN), and trend direction. This is the primary table queried by the API.

| Column | Type | Description |
|---|---|---|
| `user_id` | BIGINT | Foreign key to `users.id` |
| `token_count` | BIGINT UNSIGNED | Current total tokens earned |
| `rank` | BIGINT UNSIGNED | Cached rank position (updated by cron) |
| `status` | ENUM | `MEMBER`, `KING`, or `QUEEN` |
| `trend` | ENUM | `up`, `down`, or `flat` |

**`token_transactions`** — Append-only audit log. Every token earn or spend event is recorded here. Never update or delete rows.

| Column | Type | Description |
|---|---|---|
| `user_id` | BIGINT | The user who earned/spent tokens |
| `amount` | BIGINT | Positive = earned, Negative = spent |
| `balance_after` | BIGINT UNSIGNED | Token balance after this transaction |
| `reason` | VARCHAR(100) | e.g. `post_created`, `daily_login`, `movie_watch` |

**`leaderboard_config`** — Key-value store for all business rule parameters. Update values here to change rules without touching code.

### Step 2 — Verify Tables

```sql
SHOW TABLES LIKE 'leaderboard%';
SELECT * FROM leaderboard_config;
```

---

## 7. Backend Integration

### Step 1 — Update Database Credentials

Open `backend/LeaderboardAPI.php` and update the constants at the top of the file:

```php
define('DB_HOST',  'localhost');        // Your DB host
define('DB_NAME',  'tribbbal');         // Your database name
define('DB_USER',  'tribbbal_user');    // Your DB username
define('DB_PASS',  'your_db_password'); // Your DB password
```

### Step 2 — Place the API File

Copy `LeaderboardAPI.php` into your project's API directory (e.g. `/api/` or `/includes/api/`).

### Step 3 — Add a Route

In your platform's router, add a route that maps all requests to `/api/leaderboard/*` to the `LeaderboardRouter::handle()` method. Example for a simple PHP router:

```php
// In your router / index.php
if (strpos($uri, '/api/leaderboard') === 0) {
    require_once '/path/to/LeaderboardAPI.php';
    exit;
}
```

### Step 4 — Redis (Optional but Recommended)

If your server has the PHP Redis extension installed, caching is enabled automatically. The `/top7` endpoint will be cached for 5 minutes (configurable via the `REDIS_TTL` constant). No additional setup is required.

---

## 8. Frontend Integration

### Step 1 — Place the Files

Copy the three frontend files into your project's views/assets directory:

```
frontend/leaderboard.html  →  /views/leaderboard.html  (or your template system)
frontend/leaderboard.css   →  /assets/css/leaderboard.css
frontend/leaderboard.js    →  /assets/js/leaderboard.js
```

Update the `<link>` and `<script>` paths in `leaderboard.html` to match your directory structure.

### Step 2 — Connect the Logged-In User

Open `leaderboard.js` and set `CONFIG.currentUserId` to the currently logged-in user's ID from your session system. This enables the sticky progress bar and the "YOUR RANK" row highlight to reflect real data.

```javascript
// In leaderboard.js — update this value:
const CONFIG = {
    apiBase:         '/api/leaderboard',
    currentUserId:   null,  // ← Set this: e.g. <?= $_SESSION['user_id'] ?>
    // ...
};
```

If you inject PHP variables into JavaScript, you can do this in your template:

```html
<script>window.TRIBBBAL_USER_ID = <?= (int) $_SESSION['user_id'] ?>;</script>
<script src="/assets/js/leaderboard.js"></script>
```

Then in `leaderboard.js`, change `currentUserId: null` to `currentUserId: window.TRIBBBAL_USER_ID || null`.

### Step 3 — Add a Route

Create a route in your application (e.g. `/leaderboard`) that renders the `leaderboard.html` view.

### Step 4 — Update the Sidebar

In your platform's shared sidebar navigation template, add the Leaderboard icon. The icon uses a gold (`#d97706`) circular background to match the mockup. Mark it `active` when the current page is `/leaderboard`.

### Demo Mode (No Backend Required)

The JavaScript includes a full set of demo data. You can open `leaderboard.html` directly in a browser and the entire UI — both views, search, filters, pagination, and progress bar — will function using the built-in demo data. This allows frontend development and QA to proceed before the backend is connected.

---

## 9. API Reference

All endpoints return JSON. Base path: `/api/leaderboard`

---

### `GET /api/leaderboard/top7`

Returns the top 7 ranked users for the main featured view.

**Query Parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `filter` | string | `all` | Time filter: `all`, `month`, `week`, `today` |

**Example Request**
```
GET /api/leaderboard/top7?filter=all
```

**Example Response**
```json
{
  "data": [
    {
      "rank": 1,
      "name": "KingMaverick",
      "token_count": 168760,
      "status": "KING",
      "avatar": "https://...",
      "joined": "Jan 2020"
    }
  ]
}
```

---

### `GET /api/leaderboard/rankings`

Returns a paginated, searchable list of all ranked members.

**Query Parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `filter` | string | `all` | Time filter: `all`, `month`, `week`, `today` |
| `search` | string | `` | Search by display name |
| `page` | integer | `1` | Page number (1-indexed) |
| `limit` | integer | `15` | Results per page (max 50) |

**Example Request**
```
GET /api/leaderboard/rankings?filter=all&search=king&page=1&limit=15
```

**Example Response**
```json
{
  "data": [ ... ],
  "total": 1247832,
  "totalPages": 83189,
  "currentPage": 1
}
```

---

### `GET /api/leaderboard/user/{id}`

Returns the rank, token count, and progress data for a specific user. Used to power the sticky progress bar.

**Path Parameter**

| Parameter | Type | Description |
|---|---|---|
| `id` | integer | The user's ID from the `users` table |

**Example Request**
```
GET /api/leaderboard/user/4521
```

**Example Response**
```json
{
  "rank": 4521,
  "tokens": 12450,
  "status": "MEMBER",
  "name": "Lortir",
  "avatar": "https://...",
  "progress_pct": 12.45,
  "tokens_remaining": 87550
}
```

---

## 10. Rank Recalculation (Cron)

Ranks are not recalculated on every token transaction (that would be too expensive at scale). Instead, the `RecalculateRanks()` stored procedure is called on a schedule. It uses `ROW_NUMBER()` to assign fresh rank positions and updates the `trend` column (up/down/flat) by comparing old vs new ranks.

Add the following line to your server's crontab to run every 5 minutes:

```bash
crontab -e
```

```
*/5 * * * * mysql -u tribbbal_user -pyour_password tribbbal -e "CALL RecalculateRanks();" >> /var/log/tribbbal_ranks.log 2>&1
```

For higher-traffic environments, consider running this every 1 minute or triggering it via a background job queue instead.

---

## 11. Implementation Phases & Time Estimates

| Phase | Task | Estimated Time |
|---|---|---|
| **1** | Run `leaderboard-schema.sql`, update DB credentials in `LeaderboardAPI.php`, add route | 1–2 hours |
| **2** | Place frontend files, connect `currentUserId` from session, add sidebar icon, add route | 2–3 hours |
| **3** | End-to-end testing: data accuracy, search, pagination, progress bar | 1–2 hours |
| **4** | Cross-browser testing (Chrome, Firefox, Safari) and responsive/mobile QA | 1–2 hours |
| **5** | Set up cron job for `RecalculateRanks()`, deploy to staging, final QA | 1 hour |
| **Total** | | **6–10 hours** |

---

## 12. Acceptance Criteria

The following conditions must all pass before the feature is considered complete and ready for production deployment.

- [ ] The Top 7 view matches `tribbbal_leaderboard_top7.png` — hero banner, rules section, podium with gold/silver/bronze styling, ranks 4–7 grid.
- [ ] The Full Rankings view matches `tribbbal_leaderboard_fullrankings.png` — table layout, search bar, time filter pills, pagination, trend arrows.
- [ ] Rank #1 has a gold crown, gold glowing border, and KING/QUEEN badge. Rank #2 has silver styling. Rank #3 has bronze styling.
- [ ] The sticky progress bar accurately shows the logged-in user's rank, token count, and percentage progress toward 100,000 tokens.
- [ ] The logged-in user's row is highlighted (red border/glow) in the Full Rankings table.
- [ ] The "View Full Rankings →" link and "← Back to Top 7" button correctly switch between views.
- [ ] Search filters the rankings table in real time (with debounce).
- [ ] Time filter pills (All Time / This Month / This Week / Today) correctly filter data in both views.
- [ ] Pagination correctly navigates through pages and shows the correct data.
- [ ] All three API endpoints return correct JSON and handle edge cases (no results, invalid user ID, etc.).
- [ ] The Leaderboard icon appears in the left sidebar navigation with the gold circular background.
- [ ] The design is fully responsive and functional on desktop, tablet, and mobile.
- [ ] The `RecalculateRanks()` cron job is running and rank positions update correctly.

---

## 13. Design System Reference

All styling uses the same CSS custom properties as the Movies module. Do not hardcode color values — always use the variables.

| Variable | Value | Usage |
|---|---|---|
| `--primary-bg` | `#0a0a0a` | Page background |
| `--secondary-bg` | `#1a1a1a` | Cards, table, sidebar |
| `--tertiary-bg` | `#2a2a2a` | Hover states, inputs |
| `--accent-red` | `#e50914` | Active pills, tokens, CTA buttons, progress bar |
| `--text-primary` | `#ffffff` | Headings, names, key values |
| `--text-secondary` | `#b3b3b3` | Labels, secondary text |
| `--border-color` | `#333333` | Card borders, dividers |
| `--gold` | `#d97706` | Rank #1 border, crown, leaderboard icon |
| `--silver` | `#9ca3af` | Rank #2 border |
| `--bronze` | `#b45309` | Rank #3 border |
| `--font-family` | System font stack | All text |

---

*triBBBal LLC — Leaderboard Module Implementation Package*
*Prepared for Lortir Pierre-Louis*
