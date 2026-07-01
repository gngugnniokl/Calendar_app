<?php
/**
 * Reusable helper functions shared across every page.
 * Each one is commented with the core PHP builtin(s) it relies on,
 * so it's easy to lift straight into your "functions used" writeup.
 */

/**
 * clean() — sanitizes user input before it touches the DB or the page.
 * Uses: trim(), htmlspecialchars()
 */
function clean($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * format_date() — turns a MySQL DATE (YYYY-MM-DD) into "Mon, Jan 5 2026".
 * Uses: date(), strtotime()
 */
function format_date($mysql_date, $format = 'D, M j Y') {
    if (empty($mysql_date)) return '';
    return date($format, strtotime($mysql_date));
}

/**
 * is_today() — true if the given DATE matches today's date.
 * Uses: date(), strtotime()
 */
function is_today($mysql_date) {
    return date('Y-m-d', strtotime($mysql_date)) === date('Y-m-d');
}

/**
 * get_current_week() — figures out which week number "today" falls in,
 * based on the earliest and latest event_date stored in the DB.
 * Uses: mysqli_query(), mysqli_fetch_assoc(), intval()
 */
function get_current_week($conn) {
    $sql = "SELECT week FROM calendar_events
            ORDER BY ABS(DATEDIFF(event_date, CURDATE())) ASC LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval($row['week']);
    }
    return 1;
}

/**
 * truncate() — shortens long descriptions for card previews.
 * Uses: strlen(), substr()
 */
function truncate($text, $length = 90) {
    $text = trim($text);
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '…';
}

/**
 * active_page() — returns "active" if $page matches the current script,
 * used to highlight the right nav link.
 * Uses: basename(), $_SERVER
 */
function active_page($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}

/**
 * day_badge_class() — maps a day name to a CSS class, so Monday/Wednesday/
 * Friday etc. can each get a subtle accent color in the UI.
 * Uses: strtolower(), in_array()
 */
function day_badge_class($day) {
    $valid = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $lower = strtolower($day);
    return in_array($lower, $valid) ? 'day-' . $lower : 'day-other';
}

/**
 * redirect() — small wrapper around header() for post-action redirects,
 * e.g. after inserting/updating/deleting a row.
 * Uses: header(), exit()
 */
function redirect($location) {
    header("Location: " . $location);
    exit();
}

/**
 * flash_message() / get_flash_message() — one-request-lifetime status
 * messages ("Event added.") stored in the session.
 * Uses: session_start() [called in header.php], isset(), unset()
 */
function flash_message($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}