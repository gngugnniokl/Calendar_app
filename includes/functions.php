<?php

declare(strict_types=1);

/**
 * Reusable helper functions shared across every page.
 * Each one is commented with the core PHP builtin(s) it relies on,
 * so it's easy to lift straight into your "functions used" writeup.
 *
 * Task A2: type hints + return types added to every function, input
 * validation hardened, get_current_week() moved to a prepared
 * statement, and new query helpers added so page controllers
 * (sources/*.php) no longer run SQL directly.
 */

/**
 * clean() — sanitizes user input before it touches the DB or the page.
 * Uses: trim(), htmlspecialchars()
 *
 * @param string $value
 * @return string
 */
function clean(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * format_date() — turns a MySQL DATE (YYYY-MM-DD) into "Mon, Jan 5 2026".
 * Uses: date(), strtotime()
 *
 * @param string $mysql_date
 * @param string $format
 * @return string
 */
function format_date(string $mysql_date, string $format = 'D, M j Y'): string
{
    if (trim($mysql_date) === '') {
        return '';
    }
    return date($format, strtotime($mysql_date));
}

/**
 * is_today() — true if the given DATE matches today's date.
 * Uses: date(), strtotime()
 *
 * @param string $mysql_date
 * @return bool
 */
function is_today(string $mysql_date): bool
{
    return date('Y-m-d', strtotime($mysql_date)) === date('Y-m-d');
}

/**
 * get_current_week() — figures out which week number "today" falls in,
 * based on the earliest and latest event_date stored in the DB.
 * Uses: mysqli_prepare(), mysqli_stmt_get_result()
 *
 * @param mysqli $conn
 * @return int
 */
function get_current_week(mysqli $conn): int
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT week FROM calendar_events
         ORDER BY ABS(DATEDIFF(event_date, CURDATE())) ASC LIMIT 1"
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval($row['week']);
    }

    return 1;
}

/**
 * truncate() — shortens long descriptions for card previews.
 * Uses: strlen(), substr()
 *
 * @param string $text
 * @param int $length
 * @return string
 */
function truncate(string $text, int $length = 90): string
{
    if ($length < 0) {
        $length = 0;
    }

    $text = trim($text);

    if ($length === 0) {
        return '';
    }

    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length) . '…';
}

/**
 * active_page() — returns "active" if $page matches the current script,
 * used to highlight the right nav link.
 * Uses: basename(), $_SERVER
 *
 * @param string $page
 * @return string
 */
function active_page(string $page): string
{
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}

/**
 * day_badge_class() — maps a day name to a CSS class, so Monday/Wednesday/
 * Friday etc. can each get a subtle accent color in the UI.
 * Uses: strtolower(), in_array()
 *
 * @param string $day
 * @return string
 */
function day_badge_class(string $day): string
{
    $valid = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $lower = strtolower($day);
    return in_array($lower, $valid, true) ? 'day-' . $lower : 'day-other';
}

/**
 * redirect() — small wrapper around header() for post-action redirects,
 * e.g. after inserting/updating/deleting a row.
 * Uses: header(), exit()
 *
 * @param string $location
 * @return void
 */
function redirect(string $location): void
{
    header('Location: ' . $location);
    exit();
}

/**
 * flash_message() / get_flash_message() — one-request-lifetime status
 * messages ("Event added.") stored in the session.
 * Uses: session_start() [called in header.php], isset(), unset()
 *
 * @param string $message
 * @param string $type
 * @return void
 */
function flash_message(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * @return array{message: string, type: string}|null
 */
function get_flash_message(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Wo_GetCurrentInternshipWeek() — platform-style wrapper kept for the
 * rest of the codebase, delegates to get_current_week().
 */
function Wo_GetCurrentInternshipWeek(mysqli $conn): int
{
    return get_current_week($conn); // keep your existing logic, just relocated here
}

/**
 * Wo_GetInternshipCalendarEventsByWeek() — all events for a single week,
 * ordered by date. Uses a prepared statement (week is user-supplied via
 * $_GET in internship_calendar.php).
 *
 * @param mysqli $conn
 * @param int $week
 * @return array<int, array<string, mixed>>
 */
function Wo_GetInternshipCalendarEventsByWeek(mysqli $conn, int $week): array
{
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

/**
 * Wo_GetInternshipCalendarWeekBounds() — min/max week numbers present
 * in the table, used to bound the week-navigation UI.
 *
 * @param mysqli $conn
 * @return array{min: int, max: int}
 */
function Wo_GetInternshipCalendarWeekBounds(mysqli $conn): array
{
    $range = mysqli_query($conn, "SELECT MIN(week) AS min_week, MAX(week) AS max_week FROM calendar_events");
    $bounds = mysqli_fetch_assoc($range);
    return [
        'min' => intval($bounds['min_week'] ?? 1),
        'max' => intval($bounds['max_week'] ?? 1),
    ];
}

/**
 * Wo_GetInternshipCalendarEvents() — filterable event list (by week
 * and/or a title search). Both filters are optional and both go
 * through a prepared statement.
 *
 * @param mysqli $conn
 * @param array{week?: int|string, search?: string} $filters
 * @return array<int, array<string, mixed>>
 */
function Wo_GetInternshipCalendarEvents(mysqli $conn, array $filters = []): array
{
    $week   = isset($filters['week']) ? intval($filters['week']) : null;
    $search = isset($filters['search']) ? trim((string) $filters['search']) : '';

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

/**
 * Wo_GetInternshipCalendarStats() — pulls every event once and derives
 * all the dashboard numbers (weeks, days, completed/upcoming, progress)
 * from that single pass, instead of running a separate query per stat.
 *
 * @param mysqli $conn
 * @return array<string, mixed>
 */
function Wo_GetInternshipCalendarStats(mysqli $conn): array
{
    $sql = "SELECT * FROM calendar_events ORDER BY week ASC, event_date ASC";
    $result = mysqli_query($conn, $sql);

    $events = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
    }

    $today = date('Y-m-d');

    $week_numbers = array_unique(array_map(function (array $e): int {
        return intval($e['week']);
    }, $events));
    $unique_dates = array_unique(array_map(function (array $e): string {
        return $e['event_date'];
    }, $events));

    $completed = array_filter($events, function (array $e) use ($today): bool {
        return $e['event_date'] < $today;
    });
    $upcoming = array_filter($events, function (array $e) use ($today): bool {
        return $e['event_date'] >= $today;
    });

    $current_week   = get_current_week($conn);
    $program_length = 8;
    $progress_pct   = $program_length > 0
        ? (int) min(100, round(($current_week / $program_length) * 100))
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

/**
 * Wo_GetInternshipCalendarEventById() — single event lookup for the
 * event-detail page. Prepared statement, returns null if not found.
 *
 * @param mysqli $conn
 * @param int $id
 * @return array<string, mixed>|null
 */
function Wo_GetInternshipCalendarEventById(mysqli $conn, int $id): ?array
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM calendar_events WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row ?: null;
}

/**
 * --- Contract-name wrappers -------------------------------------------
 * The refactor plan's shared contract (Task A2 note) names these
 * helpers get_event_by_id(), get_events_by_week(), get_all_events(),
 * and get_week_bounds(). They're kept as thin wrappers around the
 * Wo_-prefixed implementations above so both naming conventions work
 * and Dev B/C can code against either the plan doc or the live app.
 */

/**
 * get_event_by_id() — see Wo_GetInternshipCalendarEventById().
 */
function get_event_by_id(mysqli $conn, int $id): ?array
{
    return Wo_GetInternshipCalendarEventById($conn, $id);
}

/**
 * get_events_by_week() — see Wo_GetInternshipCalendarEventsByWeek().
 */
function get_events_by_week(mysqli $conn, int $week): array
{
    return Wo_GetInternshipCalendarEventsByWeek($conn, $week);
}

/**
 * get_all_events() — every event, unfiltered. See
 * Wo_GetInternshipCalendarEvents().
 */
function get_all_events(mysqli $conn): array
{
    return Wo_GetInternshipCalendarEvents($conn, []);
}

/**
 * get_week_bounds() — same data as Wo_GetInternshipCalendarWeekBounds()
 * but with the min_week/max_week keys used in the plan's variable
 * contract and in sources/internship_calendar.php.
 *
 * @return array{min_week: int, max_week: int}
 */
function get_week_bounds(mysqli $conn): array
{
    $bounds = Wo_GetInternshipCalendarWeekBounds($conn);
    return [
        'min_week' => $bounds['min'],
        'max_week' => $bounds['max'],
    ];
}
function Wo_GetInternshipCalendarWeeks($conn) {
    $sql = "SELECT * FROM calendar_events ORDER BY week ASC, event_date ASC";
    $result = mysqli_query($conn, $sql);

    $weeks = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $weeks[$row['week']][] = $row;
        }
    }

    return $weeks;
}