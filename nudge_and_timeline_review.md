# Project Review: Timeline and Nudge Feature

This document evaluates the current progress on the Timeline Integration and Nudge Features based on the project requirements.

## ✅ What You Are Doing Right

1.  **Database Migration:** You successfully added the `user_id` column to the `calendar_events` table and created the `calendar_nudges` table in `sql/internship_calendar.sql`.
2.  **Timeline Helper Logic:** You correctly created `includes/timeline_calendar.php` and implemented `Wo_GetUserTimelineEvents()`. The logic for the bucketing of the events and the single SQL query is strong.
3.  **Source Data Fetching:** You correctly called `Wo_GetUserTimelineEvents` in `sources/timeline.php` to assign data to `$wo['timeline_events']`.
4.  **Profile Page UI Components:** 
    *   You successfully modified `themes/wondertag/layout/timeline/content.phtml` to insert the `.tl-section` right under the navigation.
    *   You efficiently abstracted the Nudge button into `nudge-button.phtml` and included it in the correct location for viewing another user's profile.

## ❌ What You Are Doing Wrong

1.  **Function Reference Error:** You forgot to require your new `includes/timeline_calendar.php` file inside `includes/functions.php`. Right now, your profile page will throw an "Undefined function `Wo_GetUserTimelineEvents()`" error because the code in `sources/timeline.php` doesn't know where to look for that function.
2.  **Missing Global CSS:** You haven't added the CSS classes for both the Timeline Strip (`.tl-strip`, `.tl-progress`, etc.) and the Nudge feature into `themes/wondertag/css/internship-calendar.css`. Without this, your UI will just look like broken unformatted text.

## 🚀 What You Should Be Doing Next

To wrap up these features, here are the immediate next steps you should take:

### 1. Fix the Timeline Bug
*   Open `includes/functions.php` and add `require_once 'timeline_calendar.php';` near the top so the timeline script can find your function.

### 2. Implement the Missing Nudge Backend
*   **Database Handlers:** Open `includes/functions.php` and add the three nudge functions using prepared statements:
    *   `Wo_SendNudge($conn, $sender, $receiver)`
    *   `Wo_NudgeBack($conn, $nudge_id, $me, $original_sender)`
    *   `Wo_GetNudgesForUser($conn, $user_id)`
*   **AJAX Endpoint:** Create a new file called `xhr/nudge.php` to process the `send_nudge` and `nudge_back` POST requests and handle the database insertions.
*   **Inbox Controller:** Create `sources/nudges.php` to fetch a user's nudges and pass them to the layout.

### 3. Build the Nudge Inbox UI
*   Create a new template file at `themes/wondertag/layout/nudges/content.phtml`.
*   Build the list UI using the provided HTML structure, including the `nudgeBack()` inline JavaScript.

### 4. Wire the Routing
*   **`.htaccess`:** Add `RewriteRule ^nudges(/?|)$  index.php?link1=nudges [QSA]`
*   **`index.php`:** Add `'nudges'` into the dispatcher array so the server routes to your new source file.

### 5. Finalize the Styling
*   Open `themes/wondertag/css/internship-calendar.css` and paste the styles provided in the instructions for both sections (14. Calendar Timeline Strip and 15. Nudge Inbox).
