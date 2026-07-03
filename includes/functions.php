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
function Wo_GetCurrentInternshipWeek($conn) {
    return get_current_week($conn); // keep your existing logic, just relocated here
}

function Wo_GetInternshipCalendarEventsByWeek($conn, $week) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM calendar_events WHERE week = ? ORDER BY event_date ASC");
    mysqli_stmt_bind_param($stmt, "i", $week);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $days = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $days[] = $row;
    }
    return $days;
}

function Wo_GetInternshipCalendarWeekBounds($conn) {
    $range = mysqli_query($conn, "SELECT MIN(week) AS min_week, MAX(week) AS max_week FROM calendar_events");
    $bounds = mysqli_fetch_assoc($range);
    return [
        'min' => intval($bounds['min_week'] ?? 1),
        'max' => intval($bounds['max_week'] ?? 1),
    ];
}

function Wo_GetInternshipCalendarEvents($conn, $filters = []) {
    $week   = isset($filters['week']) ? intval($filters['week']) : null;
    $search = isset($filters['search']) ? trim($filters['search']) : '';

    $sql    = "SELECT * FROM calendar_events WHERE 1=1";
    $types  = "";
    $params = [];

    if (!empty($week)) {
        $sql .= " AND week = ?";
        $types .= "i";
        $params[] = $week;
    }

    if ($search !== '') {
        $sql .= " AND title LIKE ?";
        $types .= "s";
        $params[] = "%" . $search . "%";
    }

    $sql .= " ORDER BY event_date ASC";

    $stmt = mysqli_prepare($conn, $sql);

    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $events = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
    return $events;
}
function Wo_GetInternshipCalendarStats($conn) {
    $sql = "SELECT * FROM calendar_events ORDER BY week ASC, event_date ASC";
    $result = mysqli_query($conn, $sql);

    $events = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
    }

    $today = date('Y-m-d');

    $week_numbers = array_unique(array_map(function ($e) { return intval($e['week']); }, $events));
    $unique_dates = array_unique(array_map(function ($e) { return $e['event_date']; }, $events));

    $completed = array_filter($events, function ($e) use ($today) {
        return $e['event_date'] < $today;
    });
    $upcoming = array_filter($events, function ($e) use ($today) {
        return $e['event_date'] >= $today;
    });

    $current_week   = get_current_week($conn);
    $program_length = 8;
    $progress_pct   = $program_length > 0
        ? min(100, round(($current_week / $program_length) * 100))
        : 0;

    return [
        'events'           => $events,
        'total_weeks'      => count($week_numbers),
        'total_days'       => count($unique_dates),
        'total_events'     => count($events),
        'current_week'     => $current_week,
        'completed'        => array_values($completed),
        'upcoming'         => array_values($upcoming),
        'completed_count'  => count($completed),
        'upcoming_count'   => count($upcoming),
        'program_length'   => $program_length,
        'progress_pct'     => $progress_pct,
    ];
}

function Wo_GetInternshipCalendarEventById($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM calendar_events WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}