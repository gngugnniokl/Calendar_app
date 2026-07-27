# Leaderboard Module – 6-Developer Implementation Plan

**Project:** triBBBal Social Media Platform (Leaderboard Module)
**Objective:** Split the Leaderboard feature implementation across 6 developers with zero blockers and exact file/route isolation.

---

## 1. Database & Infrastructure Engineer (Backend Data Layer)

**Scope:** Database schema, stored procedures, scheduled rank recalculation, and mock data seeding.

*   **Specific Files to Edit / Create:**
    *   `sql/leaderboard-schema.sql` (Move/run from `Lotir project/documents/leaderboard-implementation/docs/`)
    *   `Lotir project/documents/leaderboard-implementation/dummy_users.sql` (Run for mock data)
    *   `Lotir project/documents/leaderboard-implementation/dummy_tokens.sql` (Run for mock data)
    *   Server `crontab` file (External to repo).
*   **Routes & Endpoints Managed:** None.
*   **Key Responsibilities:**
    *   Create `leaderboard_tokens`, `token_transactions`, and `leaderboard_config` tables.
    *   Execute the `RecalculateRanks()` stored procedure.
    *   Configure the CRON job: `*/5 * * * * mysql -u root -p tribbbal -e "CALL RecalculateRanks();"`

---

## 2. Backend API Developer (Backend App Layer)

**Scope:** Constructing the JSON API endpoints that serve leaderboard data to the frontend, incorporating pagination, search, filters, and caching.

*   **Specific Files to Edit / Create:**
    *   `xhr/leaderboard.php` (New file for AJAX/API requests, fitting the existing `xhr/` structure).
    *   `includes/functions.php` (If global utility functions are needed for token calculation).
*   **Routes & Endpoints Managed:**
    *   `GET /xhr/leaderboard.php?action=top7` (or `/api/leaderboard/top7`)
    *   `GET /xhr/leaderboard.php?action=rankings` (or `/api/leaderboard/rankings`)
    *   `GET /xhr/leaderboard.php?action=user&id={id}` (or `/api/leaderboard/user/{id}`)
*   **Key Responsibilities:**
    *   Implement Redis/Transient caching on the `top7` feed.
    *   Write the complex SQL select queries for the data payload.
    *   Provide hardcoded JSON mock responses on Day 1 for Developer 5 to use.

---

## 3. Frontend UI Developer A (Featured Top 7 View)

**Scope:** HTML and CSS exclusively for the visual layout of the main Top 7 hero screen.

*   **Specific Files to Edit / Create:**
    *   `themes/wondertag/layout/leaderboard/content.phtml` (New file: Main view layout).
    *   `themes/wondertag/layout/leaderboard/top7.phtml` (New file: Top 7 component).
    *   `themes/wondertag/css/leaderboard.css` (Targeting only `.hero-banner`, `.podium`, `.rules-card` classes).
*   **Routes & Endpoints Managed:** None (UI only).
*   **Key Responsibilities:**
    *   Build the Hero Banner (Gradient & 3D Trophy).
    *   Build the Business Rules section (3-column layout).
    *   Build the Top 3 Podium (Gold, Silver, Bronze badges/borders).
    *   Build the 4-7 rank standard cards.

---

## 4. Frontend UI Developer B (Full Rankings View)

**Scope:** HTML and CSS exclusively for the paginated table and filtering user interface.

*   **Specific Files to Edit / Create:**
    *   `themes/wondertag/layout/leaderboard/full_rankings.phtml` (New file: List view component).
    *   `themes/wondertag/css/leaderboard.css` (Targeting only `.rankings-table`, `.search-bar`, `.filter-pills`, `.highlight-row` classes).
*   **Routes & Endpoints Managed:** None (UI only).
*   **Key Responsibilities:**
    *   Build the Data Table (Alternating row shades, responsive overflow).
    *   Build the time-filter pills (All Time, This Month, etc.) and search input.
    *   Style the "Your Rank" highlight CSS (Red border/glow).
    *   Build the Pagination UI controls.

---

## 5. Frontend Interactive Developer (JavaScript Logic)

**Scope:** Connecting the rendered UI (built by Dev 3 & 4) to the JSON data (built by Dev 2) via JavaScript.

*   **Specific Files to Edit / Create:**
    *   `themes/wondertag/javascript/leaderboard.js` (New file).
*   **Routes & Endpoints Managed:** Consumes endpoints from Developer 2.
*   **Key Responsibilities:**
    *   Write `fetch()` calls to `xhr/leaderboard.php`.
    *   Implement real-time debounce for the search input.
    *   Handle DOM manipulation (injecting table rows, swapping user avatars, appending badges).
    *   Manage view switching (Top 7 vs. Full Rankings screen toggling).

---

## 6. Core Platform Integrator (Glue & Global App Context)

**Scope:** Integrating the isolated feature safely into the global platform ecosystem, main routing, and global navigations.

*   **Specific Files to Edit / Create:**
    *   `index.php` (To register the new `/leaderboard` static route).
    *   `themes/wondertag/layout/container.phtml` (To add sidebar navigation and sticky footer).
    *   `leaderboard.php` (New file at root level to act as the page controller, similar to `timeline.php`).
*   **Routes & Endpoints Managed:**
    *   `GET /leaderboard` (The main page route viewed by users).
*   **Key Responsibilities:**
    *   Update `index.php` routing to resolve `/leaderboard` to `leaderboard.php`.
    *   Add the gold trophy Leaderboard icon to the main left sidebar (`container.phtml`).
    *   Inject the PHP Session ID: `<script>window.TRIBBBAL_USER_ID = <?= $_SESSION['user_id']; ?>;</script>` globally.
    *   Build the sticky progress-bar footer logic that persists across the platform for logged-in users.

---

## 🚀 Coordination Checklist (Day 1, 15-Minute Sync)

To ensure zero blocks, the team must agree on the following before coding:

1.  **API Data Contracts:** Dev 2 and Dev 5 must agree on the exact JSON schema that will be returned (e.g. `{"rank": 1, "tokens": 1500}`).
2.  **CSS Class/ID Targets:** Dev 3 & 4 must give Dev 5 the exact HTML `id`s they are building (e.g., `#podium-1`, `#rankings-tbody`) so Dev 5 can write JavaScript targeting those structures immediately.
3.  **Global Routing Validation:** Dev 6 confirms the path for `xhr/leaderboard.php` so Dev 2 drops their code in the right place.