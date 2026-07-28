# Leaderboard Module – 6-Developer Implementation Plan

**Project:** triBBBal Social Media Platform (Leaderboard Module)
**Objective:** Split the Leaderboard feature implementation across 6 developers with zero blockers and exact file/route isolation.

---

## 1. Database & Infrastructure Engineer (Backend Data Layer)

**Scope:** Database schema, stored procedures, scheduled rank recalculation, and mock data seeding.

- **Specific Files to Edit / Create:**
  - `sql/leaderboard-schema.sql` (Move/run from `Lotir project/documents/leaderboard-implementation/docs/`)
  - `Lotir project/documents/leaderboard-implementation/dummy_users.sql` (Run for mock data)
  - `Lotir project/documents/leaderboard-implementation/dummy_tokens.sql` (Run for mock data)
  - Server `crontab` file (External to repo).
- **Routes & Endpoints Managed:** None.
- **Key Responsibilities:**
  - Create `leaderboard_tokens`, `token_transactions`, and `leaderboard_config` tables.
  - Execute the `RecalculateRanks()` stored procedure.
  - Configure the CRON job: `*/5 * * * * mysql -u root -p tribbbal -e "CALL RecalculateRanks();"`

---

## 2. Backend API Developer (Backend App Layer)

**Scope:** Constructing the JSON API endpoints that serve leaderboard data to the frontend, incorporating pagination, search, filters, and caching.

- **Specific Files to Edit / Create:**
  - `xhr/leaderboard.php` (New file for AJAX/API requests, fitting the existing `xhr/` structure).
  - `includes/functions.php` (If global utility functions are needed for token calculation).
- **Routes & Endpoints Managed:**
  - `GET /xhr/leaderboard.php?action=top7` (or `/api/leaderboard/top7`)
  - `GET /xhr/leaderboard.php?action=rankings` (or `/api/leaderboard/rankings`)
  - `GET /xhr/leaderboard.php?action=user&id={id}` (or `/api/leaderboard/user/{id}`)
- **Key Responsibilities:**
  - Implement Redis/Transient caching on the `top7` feed.
  - Write the complex SQL select queries for the data payload.
  - Provide hardcoded JSON mock responses on Day 1 for Developer 5 to use.

---

## 3. Frontend UI Developer A (Featured Top 7 View)

**Scope:** HTML and CSS exclusively for the visual layout of the main Top 7 hero screen.

- **Specific Files to Edit / Create:**
  - `themes/wondertag/layout/leaderboard/content.phtml` (New file: Main view layout).
  - `themes/wondertag/layout/leaderboard/top7.phtml` (New file: Top 7 component).
  - `themes/wondertag/css/leaderboard.css` (Targeting only `.hero-banner`, `.podium`, `.rules-card` classes).
- **Routes & Endpoints Managed:** None (UI only).
- **Key Responsibilities:**
  - Build the Hero Banner (Gradient & 3D Trophy).
  - Build the Business Rules section (3-column layout).
  - Build the Top 3 Podium (Gold, Silver, Bronze badges/borders).
  - Build the 4-7 rank standard cards.

---

## 4. Frontend UI Developer B (Full Rankings View)

**Scope:** HTML and CSS exclusively for the paginated table and filtering user interface.

- **Specific Files to Edit / Create:**
  - `themes/wondertag/layout/leaderboard/full_rankings.phtml` (New file: List view component).
  - `themes/wondertag/css/leaderboard.css` (Targeting only `.rankings-table`, `.search-bar`, `.filter-pills`, `.highlight-row` classes).
- **Routes & Endpoints Managed:** None (UI only).
- **Key Responsibilities:**
  - Build the Data Table (Alternating row shades, responsive overflow).
  - Build the time-filter pills (All Time, This Month, etc.) and search input.
  - Style the "Your Rank" highlight CSS (Red border/glow).
  - Build the Pagination UI controls.

---

## 5. Frontend Interactive Developer (JavaScript Logic)

**Scope:** Connecting the rendered UI (built by Dev 3 & 4) to the JSON data (built by Dev 2) via JavaScript.

- **Specific Files to Edit / Create:**
  - `themes/wondertag/javascript/leaderboard.js` (New file).
- **Routes & Endpoints Managed:** Consumes endpoints from Developer 2.
- **Key Responsibilities:**
  - Write `fetch()` calls to `xhr/leaderboard.php`.
  - Implement real-time debounce for the search input.
  - Handle DOM manipulation (injecting table rows, swapping user avatars, appending badges).
  - Manage view switching (Top 7 vs. Full Rankings screen toggling).

---

## 6. Core Platform Integrator (Glue & Global App Context)

**Scope:** Integrating the isolated feature safely into the global platform ecosystem, main routing, and global navigations.

- **Specific Files to Edit / Create:**
  - `index.php` (To register the new `/leaderboard` static route).
  - `themes/wondertag/layout/container.phtml` (To add sidebar navigation and sticky footer).
  - `leaderboard.php` (New file at root level to act as the page controller, similar to `timeline.php`).
- **Routes & Endpoints Managed:**
  - `GET /leaderboard` (The main page route viewed by users).
- **Key Responsibilities:**
  - Update `index.php` routing to resolve `/leaderboard` to `leaderboard.php`.
  - Add the gold trophy Leaderboard icon to the main left sidebar (`container.phtml`).
  - Inject the PHP Session ID: `<script>window.TRIBBBAL_USER_ID = <?= $_SESSION['user_id']; ?>;</script>` globally.
  - Build the sticky progress-bar footer logic that persists across the platform for logged-in users.

---

## � Execution Order & Merge Conflict Prevention

Below is the **strict coding order** each developer must follow. Since all devs work independently, this sequence ensures no two people edit the same file at the same time.

---

### Phase 1 — Foundation (Can Start Simultaneously)

| Priority | Developer                            | Reason                                                                                                                                                                                                |
| -------- | ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 🥇 1st   | **Dev 1 – Database Engineer**        | Zero file overlap with anyone. Only touches `sql/` files and external crontab. Everyone else needs the DB tables to exist before meaningful integration testing.                                      |
| 🥇 1st   | **Dev 6 – Core Platform Integrator** | Touches `index.php` and `container.phtml` — files no other dev touches. Creates the routing skeleton (`leaderboard.php`) that all other work plugs into. Must merge first so the page actually loads. |

> **Why parallel:** Dev 1 only edits `sql/` files. Dev 6 only edits `index.php`, `container.phtml`, and creates `leaderboard.php`. Zero file overlap = zero merge conflict.

---

### Phase 2 — Backend + First Frontend (Start After Phase 1 Merges)

| Priority | Developer                                   | Reason                                                                                                                                                                                                        |
| -------- | ------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 🥈 2nd   | **Dev 2 – Backend API Developer**           | Creates `xhr/leaderboard.php` (new file, no conflict). May append to `includes/functions.php` — this is safe because Dev 6 does NOT touch this file. Must be merged before Dev 5 can do real API integration. |
| 🥈 2nd   | **Dev 3 – Frontend UI Developer A (Top 7)** | Creates new `.phtml` files AND **creates** `themes/wondertag/css/leaderboard.css`. Since this file doesn't exist yet, Dev 3 must be the one to create it first. Dev 4 appends to it later.                    |

> **Why parallel:** Dev 2 works in `xhr/` and `includes/`. Dev 3 works in `themes/wondertag/layout/leaderboard/` and `themes/wondertag/css/`. Zero file overlap = zero merge conflict.

---

### Phase 3 — Second Frontend (Start After Dev 3 Merges)

| Priority | Developer                                           | Reason                                                                                                                                                                                           |
| -------- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 🥉 3rd   | **Dev 4 – Frontend UI Developer B (Full Rankings)** | Creates `full_rankings.phtml` (new file, safe). **Appends** CSS to `leaderboard.css` — MUST wait for Dev 3's merge so the file exists and there's no concurrent write conflict on the same file. |

> **Critical rule:** Dev 4 writes CSS **only at the bottom** of `leaderboard.css` under a clear comment block:
>
> ```css
> /* ═══════════════════════════════════════════
>    FULL RANKINGS VIEW — Dev 4 Styles Below
>    ═══════════════════════════════════════════ */
> ```

---

### Phase 4 — JavaScript Glue (Start After Dev 2, 3, 4 All Merge)

| Priority      | Developer                                  | Reason                                                                                                                                                                                                                                          |
| ------------- | ------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 🏁 4th (Last) | **Dev 5 – Frontend Interactive Developer** | Creates `leaderboard.js` (new file, safe). But **cannot write meaningful code** until: (a) Dev 2's API endpoints exist to fetch from, (b) Dev 3 & 4's HTML element IDs exist to target. This dev goes last to avoid throwaway/placeholder code. |

> **Why last:** Dev 5's entire job is connecting Dev 2's API to Dev 3/4's DOM. Starting earlier means guessing at selectors and endpoint shapes — leading to rework.

---

### Visual Timeline

```
DAY 1          DAY 2          DAY 3          DAY 4
─────────────────────────────────────────────────────
[Dev 1 ████]   merge ✓
[Dev 6 ████]   merge ✓
               [Dev 2 ████]   merge ✓
               [Dev 3 ████]   merge ✓
                              [Dev 4 ████]   merge ✓
                                             [Dev 5 ████] merge ✓
```

---

### Shared File Conflict Matrix

| File                                                | Dev 1 | Dev 2 | Dev 3 | Dev 4 | Dev 5 | Dev 6 |
| --------------------------------------------------- | :---: | :---: | :---: | :---: | :---: | :---: |
| `sql/leaderboard-schema.sql`                        |  ✏️   |       |       |       |       |       |
| `xhr/leaderboard.php`                               |       |  ✏️   |       |       |       |       |
| `includes/functions.php`                            |       |  ✏️   |       |       |       |       |
| `themes/.../css/leaderboard.css`                    |       |       |  ✏️   |  ⚠️   |       |       |
| `themes/.../layout/leaderboard/content.phtml`       |       |       |  ✏️   |       |       |       |
| `themes/.../layout/leaderboard/top7.phtml`          |       |       |  ✏️   |       |       |       |
| `themes/.../layout/leaderboard/full_rankings.phtml` |       |       |       |  ✏️   |       |       |
| `themes/.../javascript/leaderboard.js`              |       |       |       |       |  ✏️   |       |
| `index.php`                                         |       |       |       |       |       |  ✏️   |
| `themes/.../layout/container.phtml`                 |       |       |       |       |       |  ✏️   |
| `leaderboard.php` (root)                            |       |       |       |       |       |  ✏️   |

> ⚠️ = Only conflict risk. Resolved by enforcing Phase 3 ordering (Dev 4 waits for Dev 3's merge).

---

### Merge Order Summary (Strictly Sequential PR Merges)

```
1. Dev 1 → merge to main
2. Dev 6 → merge to main  (can be same time as Dev 1)
3. Dev 2 → merge to main
4. Dev 3 → merge to main  (can be same time as Dev 2)
5. Dev 4 → merge to main  (MUST wait for Dev 3)
6. Dev 5 → merge to main  (MUST wait for Dev 2 + Dev 3 + Dev 4)
```

---

### Rules to Enforce

1. **No dev touches a file outside their column** in the conflict matrix above.
2. **Dev 3 creates `leaderboard.css`; Dev 4 only appends below a separator comment.**
3. **Dev 5 does NOT start coding until Dev 2 provides the final JSON contract** (hardcoded mock is acceptable for Day 1 local work, but PR must target real endpoints).
4. **Every PR must rebase on `main`** before merge — never merge without pulling latest.
5. **Branch naming convention:** `feature/leaderboard-dev{N}-{short-desc}` (e.g., `feature/leaderboard-dev3-top7-ui`).

---

## �🚀 Coordination Checklist (Day 1, 15-Minute Sync)

To ensure zero blocks, the team must agree on the following before coding:

1.  **API Data Contracts:** Dev 2 and Dev 5 must agree on the exact JSON schema that will be returned (e.g. `{"rank": 1, "tokens": 1500}`).
2.  **CSS Class/ID Targets:** Dev 3 & 4 must give Dev 5 the exact HTML `id`s they are building (e.g., `#podium-1`, `#rankings-tbody`) so Dev 5 can write JavaScript targeting those structures immediately.
3.  **Global Routing Validation:** Dev 6 confirms the path for `xhr/leaderboard.php` so Dev 2 drops their code in the right place.
