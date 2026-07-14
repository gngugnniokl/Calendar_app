# Task: Timeline Calendar Integration

> **Goal:** Add a personal calendar timeline section to each user's profile page (`?link1=timeline&u=username`) so visitors can see that user's schedule progress at a glance — modelled after how tribbbal.com's timeline resolves a username, loads user-specific data, and renders it inside the existing social-profile layout.

---

## Context — How the Profile Page Is Structured

The timeline page already exists at `themes/wondertag/layout/timeline/content.phtml`. It uses a **4-column grid layout** (`.timeline-layout`):

```
┌──────┬─────────────────────────────┬────────────┬──────┐
│ Left │         Main Feed           │   Right    │ Chat │
│ Nav  │   (cover, profile, posts)   │  Sidebar   │ Bar  │
│ 70px │         1fr                 │   310px    │ 70px │
└──────┴─────────────────────────────┴────────────┴──────┘
```

Inside `<main class="timeline-main">`, from top to bottom:

1. **Cover zone** (`.tag_cover_bg`) — blurred background + sharp cover image
2. **Name bar** (`.tag_page_name_hdr`) — avatar, name, @username, role badge, action buttons, tab navigation
3. **Content filters** (`.content-filters`) — pill-shaped filter buttons
4. **Post create teaser** — "What's on your royal mind?" input placeholder
5. **Feed** (`.timeline-feed`) — list of `.post-card` articles

Your job is to insert a **Calendar Timeline Strip** section between the tab navigation and the content filters — so it appears as the first content block below the profile header.

---

## What to Build

### A "Calendar Timeline Strip" — a vertical event list showing the user's schedule

```
┌─ WEEK PROGRESS BAR ──────────────────────────────────────────────┐
│  Week 1  ████████░░  80%  (4/5 days done)                        │
└──────────────────────────────────────────────────────────────────-┘

┌─ TIMELINE STRIP ─────────────────────────────────────────────────┐
│                                                                   │
│  ✓  ┊  Day 1 — Welcome                            Mon, Jul 6    │
│     ┊  Setup Environment                                         │
│     ┊                                                             │
│  ✓  ┊  Day 2 — PHP Basics                         Tue, Jul 7    │
│     ┊  Variables, Arrays, Loops                                   │
│     ┊                                                             │
│  ✓  ┊  Day 3 — MySQL Basics                       Wed, Jul 8    │
│     ┊  SELECT, INSERT, UPDATE, DELETE                             │
│     ┊                                                             │
│  ●  ┊  Day 4 — Advanced PHP              ★ TODAY  Thu, Jul 9    │
│     ┊  Functions, Classes, Namespaces                             │
│     ┊                                                             │
│  ○  ┊  Day 5 — Mini Project                       Fri, Jul 10   │
│     ┊  Build a CRUD app                                           │
│                                                                   │
└──────────────────────────────────────────────────────────────────-┘
```

---

## Detailed Component Breakdown

### Component 1: Week Progress Bar (`.tl-progress`)

A single card at the top of the section. Sits inside its own `.post-card`-style container to stay consistent with the rest of the feed.

**HTML structure:**

```html
<div class="tl-progress">
    <div class="tl-progress-label">
        <span class="tl-progress-week">Week 1</span>
        <span class="tl-progress-stat">4 / 5 days done</span>
    </div>
    <div class="tl-progress-bar">
        <div class="tl-progress-fill" style="width: 80%"></div>
    </div>
</div>
```

**Styling rules** (add to `internship-calendar.css`, reuse design tokens):

| Property | Value | Notes |
|----------|-------|-------|
| `.tl-progress` | `background: var(--surface); border: 1px solid var(--hairline); border-radius: var(--radius); padding: 16px 20px; box-shadow: var(--shadow-card); margin-bottom: 16px;` | Same card treatment as `.post-card` |
| `.tl-progress-label` | `display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;` | |
| `.tl-progress-week` | `font-family: var(--font-display); font-weight: 600; font-size: 16px; color: var(--ink);` | Matches `h3` feel |
| `.tl-progress-stat` | `font-family: var(--font-mono); font-size: 12px; color: var(--muted);` | Echoes `.week-count` |
| `.tl-progress-bar` | `height: 6px; background: var(--hairline); border-radius: 3px; overflow: hidden;` | Thin, subtle |
| `.tl-progress-fill` | `height: 100%; background: var(--pine); border-radius: 3px; transition: width 0.4s ease;` | Uses the primary accent `#e40d0e` |

---

### Component 2: Timeline Strip (`.tl-strip`)

A vertical list of events connected by a continuous left-edge line. Each event is a `.tl-strip-item`.

**HTML structure:**

```html
<div class="tl-strip">
    <!-- Completed event -->
    <div class="tl-strip-item tl-completed">
        <div class="tl-strip-dot">
            <i class="fa-solid fa-check"></i>
        </div>
        <div class="tl-strip-body">
            <div class="tl-strip-head">
                <h4>Day 1 — Welcome</h4>
                <span class="tl-strip-date">Mon, Jul 6</span>
            </div>
            <p>Setup Environment</p>
        </div>
    </div>

    <!-- Today's event -->
    <div class="tl-strip-item tl-today">
        <div class="tl-strip-dot">
            <i class="fa-solid fa-circle"></i>
        </div>
        <div class="tl-strip-body">
            <div class="tl-strip-head">
                <h4>Day 4 — Advanced PHP</h4>
                <span class="tl-strip-date">Thu, Jul 9</span>
            </div>
            <p>Functions, Classes, Namespaces</p>
            <span class="badge-live">Today</span>
        </div>
    </div>

    <!-- Upcoming event -->
    <div class="tl-strip-item tl-upcoming">
        <div class="tl-strip-dot">
            <i class="fa-regular fa-circle"></i>
        </div>
        <div class="tl-strip-body">
            <div class="tl-strip-head">
                <h4>Day 5 — Mini Project</h4>
                <span class="tl-strip-date">Fri, Jul 10</span>
            </div>
            <p>Build a CRUD app</p>
        </div>
    </div>
</div>
```

**Styling rules:**

| Selector | Value | Notes |
|----------|-------|-------|
| `.tl-strip` | `position: relative; padding-left: 28px; margin: 0 0 24px;` | Creates space for the vertical line + dots |
| `.tl-strip::before` | `content: ''; position: absolute; left: 9px; top: 0; bottom: 0; width: 2px; background: var(--hairline);` | The vertical connecting line |
| `.tl-strip-item` | `position: relative; padding: 12px 0 12px 20px; border-bottom: none;` | No card borders — the line is the visual anchor |
| `.tl-strip-dot` | `position: absolute; left: -28px; top: 14px; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; z-index: 1;` | Sits on top of the vertical line |
| `.tl-strip-head` | `display: flex; justify-content: space-between; align-items: baseline;` | Title left, date right |
| `.tl-strip-head h4` | `font-family: var(--font-display); font-size: 15px; font-weight: 600; margin: 0; color: var(--ink);` | |
| `.tl-strip-date` | `font-family: var(--font-mono); font-size: 11px; color: var(--muted); white-space: nowrap;` | Monospace date, right-aligned |
| `.tl-strip-body p` | `font-size: 13px; color: var(--ink-soft); margin: 4px 0 0;` | |

**State-specific styles:**

| State | Dot style | Text treatment |
|-------|-----------|----------------|
| `.tl-completed .tl-strip-dot` | `background: var(--pine-tint); color: var(--pine);` | `h4` gets `text-decoration: line-through; color: var(--muted);` — reuses the existing `.event-completed` pattern |
| `.tl-today .tl-strip-dot` | `background: var(--brass); color: #fff; box-shadow: 0 0 0 4px var(--brass-tint);` | `h4` stays `color: var(--ink); font-weight: 700;`. Show the existing `.badge-live` next to the description |
| `.tl-upcoming .tl-strip-dot` | `background: var(--surface); border: 2px solid var(--hairline); color: var(--muted);` | Entire `.tl-strip-item` gets `opacity: 0.6;` |

---

### Component 3: Empty State

When the user has zero events, render the same empty-state pattern used in the calendar view:

```html
<div class="tl-strip-empty" style="text-align: center; color: var(--muted); padding: 40px;">
    <i class="fa-solid fa-calendar-xmark" style="font-size: 40px; margin-bottom: 15px; display: block;"></i>
    <p>No schedule yet.</p>
</div>
```

This matches the existing empty state in `timeline/content.phtml`.

---

### Where It Sits in the Template

Inside `themes/wondertag/layout/timeline/content.phtml`, insert the new section **after** the `</nav>` closing tag of `.user-bottom-nav` and **before** the `<!-- Filters -->` comment:

```
        </nav>
    </section>

    <!-- ===== CALENDAR TIMELINE STRIP (NEW) ===== -->
    <section class="tl-section" style="margin: 20px 40px 0 40px;">
        <?php if (!empty($wo['timeline_events'])): ?>
            <!-- Progress bar -->
            ... tl-progress ...
            <!-- Strip -->
            ... tl-strip ...
        <?php else: ?>
            ... tl-strip-empty ...
        <?php endif; ?>
    </section>

    <!-- Filters -->
    <div class="content-filters" ...>
```

The `margin: 20px 40px 0 40px` matches the existing spacing used by `.content-filters` and `.timeline-feed` in the template.

---

## Implementation Steps

### Step 1 — Add a `user_id` column to `calendar_events`

```sql
ALTER TABLE calendar_events ADD COLUMN user_id INT DEFAULT NULL AFTER id;
```

Events with `user_id = NULL` are programme-wide (visible on every user's timeline).

### Step 2 — Create a helper function in `includes/functions.php`

```php
/**
 * Wo_GetUserTimelineEvents() — fetches a user's calendar events
 * split into completed, today, and upcoming.
 *
 * @param mysqli $conn
 * @param int $user_id
 * @return array ['completed' => [...], 'today' => [...], 'upcoming' => [...],
 *                'week' => int, 'total_this_week' => int, 'done_this_week' => int]
 */
function Wo_GetUserTimelineEvents(mysqli $conn, int $user_id): array
{
    // Query events WHERE user_id = ? OR user_id IS NULL
    // ORDER BY event_date ASC
    // Loop once, bucket by comparing event_date to date('Y-m-d')
    // Cap upcoming to 5 rows
    // Count completed vs total for the current week number
}
```

One loop. Prefer one prepared statement for the main query (week stats may use a second prepared statement).

### Step 3 — Update `sources/timeline.php`

After the existing `$wo['user_events']` line, add:

```php
$wo['timeline_events'] = Wo_GetUserTimelineEvents($conn, (int) $profile['user_id']);
```

### Step 4 — Edit the timeline template

File: `themes/wondertag/layout/timeline/content.phtml`

Insert the Calendar Timeline Strip section between the tab nav and the content filters, using the HTML structure described above.

### Step 5 — Add CSS

File: `themes/wondertag/css/internship-calendar.css`

Add the `.tl-progress`, `.tl-strip`, `.tl-strip-item`, `.tl-strip-dot`, etc. rules under a new comment block:

```css
/* ---------------------------------------------------------------------
   14. Calendar Timeline Strip (profile page)
   --------------------------------------------------------------------- */
```

Follow the token values listed in the component tables above. Do not introduce new CSS variables — use the existing `:root` tokens (`--pine`, `--brass`, `--surface`, `--hairline`, `--radius`, `--shadow-card`, etc.).

Add a mobile breakpoint inside the existing `@media (max-width: 600px)` block:

```css
.tl-section { margin: 16px 16px 0 !important; }
.tl-strip { padding-left: 24px; }
.tl-strip-dot { left: -24px; }
```

---

## Files You'll Touch

| File | Change |
|------|--------|
| `sql/internship_calendar.sql` | Add `user_id` column to `calendar_events` |
| `includes/functions.php` | Add `Wo_GetUserTimelineEvents()` |
| `sources/timeline.php` | Call new function, pass result to `$wo['timeline_events']` |
| `themes/wondertag/layout/timeline/content.phtml` | Insert `.tl-section` block between tab nav and filters |
| `themes/wondertag/css/internship-calendar.css` | Add section 14 CSS rules |

---

## Constraints

- **No JavaScript** — pure server-rendered HTML/CSS
- **No external libraries** — use what's already in the project
- Reuse `format_date()`, `is_today()`, and `clean()` from `functions.php`
- Reuse `.badge-live` for the "Today" indicator (already exists in the CSS)
- Keep the SQL to **one prepared statement** in the new function
- Limit upcoming events to **5 rows**
- Use only existing design tokens from `:root` — no new colours or fonts

---

## Acceptance Criteria

1. Visiting `?link1=timeline&u=intern1` shows the profile **and** the calendar timeline strip below the tab nav
2. Completed events have a red-tinted check dot and strikethrough title
3. Today's event has a teal dot with a glow ring and the `.badge-live` tag
4. Upcoming events are faded at 60% opacity with an outlined dot
5. The progress bar fills proportionally and uses the `--pine` accent colour
6. Programme-wide events (`user_id IS NULL`) appear on every user's timeline
7. Zero events shows the empty state with the calendar-xmark icon
8. The layout doesn't break on mobile (≤600px)

---

## Bonus (Optional)

- Add a `?link1=timeline&u=intern1&type=events` sub-route that shows the full expanded calendar view (all weeks, not just the strip)
- Make the progress bar link to `?link1=internship_calendar&week=X`

---
---

# Part 2: Nudge System

> **Goal:** Add a "Nudge" interaction (modelled after tribbbal.com's Poke system) so mentors can nudge interns about incomplete tasks, and interns can nudge mentors for review — with a dedicated inbox page and an AJAX-powered button on user profiles.

---

## Context — How tribbbal.com's Poke Works

On the main platform, the Poke system has three moving parts:

1. **Send poke** — `xhr/poke.php` receives `received_user_id` + `send_user_id`, inserts a row into `Wo_Pokes`, fires `Wo_RegisterNotification()`, returns `{ status: 200 }`
2. **Poke inbox** — `sources/poke.php` queries `Wo_Pokes WHERE received_user_id = ?`, loads each sender's user data, renders a grid of avatar cards with a "Poke Back" button
3. **Poke back** — the same XHR endpoint, but also passes a `poke_id` — the handler deletes the old poke first, then inserts a new one going the opposite direction

The entire feature is ~30 lines of PHP backend + one XHR file + two small templates. We rename it **Nudge** to fit the internship context.

---

## What to Build

### A Nudge button on the profile + a Nudge inbox page

**On the timeline profile page** (`?link1=timeline&u=intern1`):
- A "Nudge" button in the action buttons row (`.wow_user_page_btns`)
- Only visible when viewing **someone else's** profile (not your own)
- Clicking it sends an AJAX request and changes the button text to "Nudged ✓"

**A new Nudge inbox page** (`?link1=nudges`):
- Shows a list of users who have nudged you
- Each entry has the sender's avatar, name, time, and a "Nudge Back" button
- Nudge Back deletes the inbound nudge and sends one back to the original sender

---

## Database

### `calendar_nudges` table

```sql
CREATE TABLE calendar_nudges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES Wo_Users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES Wo_Users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Add this to `sql/internship_calendar.sql`.

---

## Detailed Component Breakdown

### Component 1: Nudge Button (on timeline profile)

Sits inside the existing `.wow_user_page_btns` div in `themes/wondertag/layout/timeline/content.phtml`, next to the Follow/Message buttons.

**HTML:**

```html
<!-- Only show when viewing another user's profile -->
<button class="btn btn-ghost nudge-btn" id="nudge-btn"
        onclick="sendNudge(<?php echo $profile['user_id']; ?>)">
    <i class="fa-solid fa-hand-point-right"></i> Nudge
</button>
```

**Styling rules:**

| Selector | Value | Notes |
|----------|-------|-------|
| `.nudge-btn` | Same as existing `.btn.btn-ghost` | Inherits the ghost button style already on the page |
| `.nudge-btn.nudged` | `color: var(--pine); border-color: var(--pine-tint); background: var(--pine-tint); pointer-events: none;` | Disabled after sending |
| `.nudge-btn i` | `margin-right: 4px;` | Spacing between icon and text |

**Where in the template** — insert after the Follow button span:

```html
                <span class="user-follow-button group-join-btn">
                    <button class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Follow</button>
                </span>

                <!-- Nudge Button (NEW) — only for other users -->
                <?php if ($wo['user']['user_id'] !== $profile['user_id']): ?>
                    <button class="btn btn-ghost nudge-btn" id="nudge-btn"
                            onclick="sendNudge(<?php echo (int)$profile['user_id']; ?>)">
                        <i class="fa-solid fa-hand-point-right"></i> Nudge
                    </button>
                <?php endif; ?>

                <button class="btn btn-ghost" title="Settings"><i class="fa-solid fa-ellipsis"></i></button>
```

---

### Component 2: Nudge JavaScript (inline, bottom of template)

A small inline `<script>` block at the bottom of `timeline/content.phtml`. This is the **only JS** in the feature — kept minimal.

```html
<script>
function sendNudge(receiverId) {
    var btn = document.getElementById('nudge-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Nudged';
    btn.classList.add('nudged');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '?link1=_&f=nudge', true);  // routes to xhr/ via requests.php
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('s=send_nudge&receiver_id=' + receiverId);
}
</script>
```

No external libraries. Plain `XMLHttpRequest`. Fire-and-forget (no response handling needed beyond the UI toggle).

---

### Component 3: XHR Handler (`xhr/nudge.php`)

New file. Handles two sub-actions: `send_nudge` and `nudge_back`.

**Routing:** Add `'nudge' => 'nudge'` to whichever dispatcher maps `$_GET['f']` to XHR files (check how `xhr/timeline.php` and `xhr/internship_calendar.php` are routed — either via `requests.php` or directly).

**PHP structure:**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../assets/init.php';
header('Content-Type: application/json');

if (!Wo_IsLogged($conn)) {
    echo json_encode(['success' => false, 'message' => 'Auth required.']);
    exit();
}

$s = isset($_POST['s']) ? trim((string)$_POST['s']) : '';
$me = (int)$_SESSION['user_id'];

switch ($s) {

    case 'send_nudge':
        $receiver = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
        if ($receiver < 1 || $receiver === $me) {
            echo json_encode(['success' => false, 'message' => 'Invalid user.']);
            exit();
        }
        // Prevent duplicate nudge (one active nudge per sender→receiver pair)
        // INSERT … if no existing row
        Wo_SendNudge($conn, $me, $receiver);
        echo json_encode(['success' => true]);
        exit();

    case 'nudge_back':
        $nudge_id = isset($_POST['nudge_id']) ? (int)$_POST['nudge_id'] : 0;
        $sender   = isset($_POST['sender_id']) ? (int)$_POST['sender_id'] : 0;
        if ($nudge_id < 1 || $sender < 1) {
            echo json_encode(['success' => false, 'message' => 'Invalid nudge.']);
            exit();
        }
        // Delete the inbound nudge, then insert a new one going back
        Wo_NudgeBack($conn, $nudge_id, $me, $sender);
        echo json_encode(['success' => true]);
        exit();

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit();
}
```

---

### Component 4: Helper Functions (`includes/functions.php`)

Add three functions:

```php
/**
 * Wo_SendNudge() — inserts a nudge if one doesn't already exist
 * from this sender to this receiver.
 * Uses: mysqli_prepare()
 */
function Wo_SendNudge(mysqli $conn, int $sender, int $receiver): bool { }

/**
 * Wo_NudgeBack() — deletes the inbound nudge, inserts a new one
 * in the opposite direction.
 * Uses: mysqli_prepare(), mysqli_begin_transaction()
 */
function Wo_NudgeBack(mysqli $conn, int $nudge_id, int $me, int $original_sender): bool { }

/**
 * Wo_GetNudgesForUser() — returns all nudges received by a user,
 * joined with sender's name/avatar/username.
 * Uses: mysqli_prepare()
 */
function Wo_GetNudgesForUser(mysqli $conn, int $user_id): array { }
```

All queries use **prepared statements**. `Wo_NudgeBack` wraps the delete+insert in a transaction.

---

### Component 5: Nudge Inbox Page

**Source controller** — `sources/nudges.php`:

```php
<?php
declare(strict_types=1);

$wo['page_title'] = 'Nudges';
$wo['nudges'] = Wo_GetNudgesForUser($conn, (int)$_SESSION['user_id']);
$wo['content'] = Wo_LoadPage('nudges/content');
```

**Route** — add to `.htaccess`:

```apache
RewriteRule ^nudges(/?|)$  index.php?link1=nudges [QSA]
```

And register `'nudges'` in the page dispatcher array inside `index.php`.

---

### Component 6: Nudge Inbox Template

File: `themes/wondertag/layout/nudges/content.phtml`

Uses the same card-based layout as the rest of the app. Each nudge is a row inside a `.post-card`-style container.

**HTML structure:**

```html
<section class="page-intro">
    <h1 class="hero-title">Nudges</h1>
</section>

<?php if (empty($wo['nudges'])): ?>
    <div style="text-align: center; color: var(--muted); padding: 60px 20px;">
        <i class="fa-solid fa-hand-point-right" style="font-size: 40px; margin-bottom: 15px; display: block;"></i>
        <p>No nudges yet. When someone nudges you, it'll show up here.</p>
    </div>
<?php else: ?>
    <div class="nudge-list">
        <?php foreach ($wo['nudges'] as $nudge): ?>
            <div class="nudge-item" id="nudge-<?php echo (int)$nudge['id']; ?>">
                <div class="nudge-avatar">
                    <img src="<?php echo clean($nudge['avatar']); ?>" alt="">
                </div>
                <div class="nudge-info">
                    <a href="?link1=timeline&u=<?php echo clean($nudge['username']); ?>" class="nudge-name">
                        <?php echo clean($nudge['name']); ?>
                    </a>
                    <span class="nudge-time"><?php echo format_date($nudge['created_at'], 'M j, g:ia'); ?></span>
                </div>
                <button class="btn btn-ghost nudge-back-btn"
                        onclick="nudgeBack(<?php echo (int)$nudge['id']; ?>, <?php echo (int)$nudge['sender_id']; ?>, this)">
                    <i class="fa-solid fa-hand-point-left"></i> Nudge Back
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    function nudgeBack(nudgeId, senderId, btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Sent';
        btn.classList.add('nudged');

        var row = document.getElementById('nudge-' + nudgeId);
        row.style.opacity = '0.5';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '?link1=_&f=nudge', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('s=nudge_back&nudge_id=' + nudgeId + '&sender_id=' + senderId);
    }
    </script>
<?php endif; ?>
```

---

### Component 7: Nudge Inbox CSS

Add to `internship-calendar.css` under section 14 (or new section 15):

```css
/* ---------------------------------------------------------------------
   15. Nudge Inbox
   --------------------------------------------------------------------- */
```

**Styling rules:**

| Selector | Value | Notes |
|----------|-------|-------|
| `.nudge-list` | `display: flex; flex-direction: column; gap: 0;` | Stacked rows |
| `.nudge-item` | `display: flex; align-items: center; gap: 14px; padding: 16px 20px; background: var(--surface); border: 1px solid var(--hairline); border-radius: var(--radius); margin-bottom: 10px; box-shadow: var(--shadow-card); transition: opacity 0.3s ease;` | Same card feel as `.post-card` |
| `.nudge-avatar` | `width: 44px; height: 44px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: var(--hairline);` | Matches `.post-avatar` |
| `.nudge-avatar img` | `width: 100%; height: 100%; object-fit: cover;` | |
| `.nudge-info` | `flex: 1; display: flex; flex-direction: column;` | |
| `.nudge-name` | `font-weight: 700; font-size: 15px; color: var(--ink);` | Matches `.post-author` |
| `.nudge-name:hover` | `color: var(--pine);` | |
| `.nudge-time` | `font-size: 12px; color: var(--muted); font-family: var(--font-mono);` | Matches `.post-time` |
| `.nudge-back-btn` | Inherits `.btn.btn-ghost` | |
| `.nudge-back-btn.nudged` | `color: var(--pine); border-color: var(--pine-tint); background: var(--pine-tint); pointer-events: none;` | Same disabled state as profile nudge button |

**Mobile (`@media (max-width: 600px)`):**

```css
.nudge-item { padding: 12px 16px; }
.nudge-back-btn { font-size: 12px; padding: 6px 10px; }
```

---

## Files You'll Touch (Part 2)

| File | Change |
|------|--------|
| `sql/internship_calendar.sql` | Add `calendar_nudges` table |
| `includes/functions.php` | Add `Wo_SendNudge()`, `Wo_NudgeBack()`, `Wo_GetNudgesForUser()` |
| `xhr/nudge.php` | **New file** — handles `send_nudge` and `nudge_back` |
| `sources/nudges.php` | **New file** — page controller for nudge inbox |
| `themes/wondertag/layout/nudges/content.phtml` | **New file** — nudge inbox template |
| `themes/wondertag/layout/timeline/content.phtml` | Add nudge button to `.wow_user_page_btns` |
| `themes/wondertag/css/internship-calendar.css` | Add section 15 nudge styles |
| `.htaccess` | Add `nudges` rewrite rule |
| `index.php` | Register `nudges` in the page dispatcher |

---

## Constraints

- Duplicate prevention — only one active nudge per sender→receiver direction at a time
- You cannot nudge yourself — check `$receiver !== $me`
- All queries use **prepared statements** (no raw `$_POST` in SQL)
- `Wo_NudgeBack` must use a **transaction** (delete + insert atomically)
- No external JS libraries — plain `XMLHttpRequest` only
- Reuse existing design tokens — no new CSS variables

---

## Acceptance Criteria

1. Viewing another user's profile shows a "Nudge" button that sends an AJAX request and toggles to "Nudged ✓"
2. The Nudge button does **not** appear on your own profile
3. Visiting `?link1=nudges` shows all inbound nudges with sender avatar, name, and timestamp
4. "Nudge Back" deletes the inbound nudge, sends one in return, and fades the row
5. Sending a nudge twice to the same person does not create duplicate rows
6. All SQL uses prepared statements (no injection vectors)
7. The nudge inbox empty state shows a hand icon + message
8. Mobile layout (≤600px) doesn't break
