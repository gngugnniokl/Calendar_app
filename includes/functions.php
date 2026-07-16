<?php

declare(strict_types=1);

require_once 'timeline_calendar.php';

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
    $stmt = mysqli_prepare($conn, "SELECT * FROM calendar_events WHERE week = ? ORDER BY e.event_date ASC");
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
 * Wo_ExportInternshipCalendarExcel() — downloads the current calendar view
 * as an Excel-compatible spreadsheet (.xlsx) using PHP's ZipArchive when available.
 *
 * @param mysqli $conn
 * @param array{week?: int|string, search?: string, day?: string, from?: string, to?: string} $filters
 * @return void
 */
function Wo_ExportInternshipCalendarExcel(mysqli $conn, array $filters = []): void
{
    $events = Wo_GetInternshipCalendarEvents($conn, $filters);
    $rows = [];
    $rows[] = ['Week', 'Day', 'Date', 'Title', 'Description', 'Success Criteria', 'Traps'];

    foreach ($events as $event) {
        $rows[] = [
            isset($event['week']) ? intval($event['week']) : '',
            isset($event['day']) ? (string) $event['day'] : '',
            isset($event['event_date']) ? (string) $event['event_date'] : '',
            isset($event['title']) ? (string) $event['title'] : '',
            isset($event['description']) ? (string) $event['description'] : '',
            isset($event['success_criteria']) ? (string) $event['success_criteria'] : '',
            isset($event['traps']) ? (string) $event['traps'] : '',
        ];
    }

    $filename = 'internship-calendar-export.xlsx';
    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $sheetXml .= '<sheetData>';

    foreach ($rows as $rowIndex => $rowValues) {
        $sheetXml .= '<row r="' . ($rowIndex + 1) . '">';
        foreach ($rowValues as $colIndex => $value) {
            $column = chr(65 + $colIndex);
            $cellRef = $column . ($rowIndex + 1);
            $cellStyle = $rowIndex === 0 ? '1' : '0';
            $safeValue = str_replace(["\r\n", "\n", "\r"], "\n", (string) $value);
            $safeValue = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $safeValue);
            $sheetXml .= '<c r="' . $cellRef . '" s="' . $cellStyle . '" t="inlineStr"><is><t xml:space="preserve">' . $safeValue . '</t></is></c>';
        }
        $sheetXml .= '</row>';
    }

    $sheetXml .= '</sheetData></worksheet>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Calendar" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><b/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

    if (class_exists('ZipArchive')) {
        $tempFile = tempnam(sys_get_temp_dir(), 'calendar-export-');
        if ($tempFile !== false) {
            $zip = new ZipArchive();
            if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $zip->addFromString('[Content_Types].xml', $contentTypes);
                $zip->addFromString('_rels/.rels', $rootRels);
                $zip->addFromString('xl/workbook.xml', $workbook);
                $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
                $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
                $zip->addFromString('xl/styles.xml', $styles);
                $zip->close();

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                readfile($tempFile);
                unlink($tempFile);
                exit;
            }
        }
    }

    $csvHandle = fopen('php://temp', 'r+');
    fputcsv($csvHandle, ['Week', 'Day', 'Date', 'Title', 'Description', 'Success Criteria', 'Traps']);
    foreach ($rows as $index => $row) {
        if ($index === 0) {
            continue;
        }
        fputcsv($csvHandle, $row);
    }
    rewind($csvHandle);
    $csvContent = stream_get_contents($csvHandle);
    fclose($csvHandle);

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $csvContent;
    exit;
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
    $day    = isset($filters['day']) ? trim((string) $filters['day']) : '';
    $from   = isset($filters['from']) ? trim((string) $filters['from']) : '';
    $to     = isset($filters['to']) ? trim((string) $filters['to']) : '';

    global $wo;
    $user_id = !empty($wo['user']['user_id']) ? (int)$wo['user']['user_id'] : 0;
    
    // Select everything from calendar_events, but correctly alias 'completed' against completions tracking mapper
    $sql    = "SELECT e.*, IF(c.id IS NOT NULL, 1, 0) AS completed FROM calendar_events e 
               LEFT JOIN calendar_event_completions c ON e.id = c.event_id AND c.user_id = ? 
               WHERE (e.user_id = ? OR e.user_id IS NULL)";
    $types  = "ii";
    $params = [$user_id, $user_id];

    if (!empty($week)) {
        $sql .= " AND e.week = ?";
        $types .= "i";
        $params[] = $week;
    }

    if ($search !== '') {
        $sql .= " AND e.title LIKE ?";
        $types .= "s";
        $params[] = "%" . $search . "%";
    }

    if ($day !== '') {
        $sql .= " AND e.day = ?";
        $types .= "s";
        $params[] = $day;
    }

    if ($from !== '') {
        $sql .= " AND e.event_date >= ?";
        $types .= "s";
        $params[] = $from;
    }

    if ($to !== '') {
        $sql .= " AND e.event_date <= ?";
        $types .= "s";
        $params[] = $to;
    }

    $sql .= " ORDER BY e.event_date ASC";

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
    global $wo;
    $user_id = !empty($wo['user']['user_id']) ? (int)$wo['user']['user_id'] : 0;
    
    $stmt = mysqli_prepare($conn, "SELECT e.*, IF(c.id IS NOT NULL, 1, 0) AS completed 
                                  FROM calendar_events e 
                                  LEFT JOIN calendar_event_completions c ON e.id = c.event_id AND c.user_id = ? 
                                  WHERE (e.user_id = ? OR e.user_id IS NULL) 
                                  ORDER BY e.week ASC, e.event_date ASC");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

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

    $completed = array_filter($events, function (array $e): bool {
        return !empty($e['completed']);
    });
    $upcoming = array_filter($events, function (array $e): bool {
        return empty($e['completed']);
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
    global $wo;
    $user_id = !empty($wo['user']['user_id']) ? (int)$wo['user']['user_id'] : 0;
    
    $stmt = mysqli_prepare($conn, "SELECT e.*, IF(c.id IS NOT NULL, 1, 0) AS completed 
                                  FROM calendar_events e 
                                  LEFT JOIN calendar_event_completions c ON e.id = c.event_id AND c.user_id = ? 
                                  WHERE e.id = ? AND (e.user_id = ? OR e.user_id IS NULL) LIMIT 1");
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $id, $user_id);
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

/**
 * Mock Wo_LoadPage to render themes/wondertag templates for testing
 */
function Wo_LoadPage($page_url) {
    global $wo;
    
    // Automatically map all $wo array keys to local variables for Developer C's templates
    if (is_array($wo)) {
        // Alias calendar_events to days to match the template expectations
        if (isset($wo['calendar_events']) && !isset($wo['days'])) {
            $wo['days'] = $wo['calendar_events'];
        }
        // Map week_bounds to min_week and max_week
        if (isset($wo['week_bounds'])) {
            $wo['min_week'] = $wo['week_bounds']['min'] ?? 1;
            $wo['max_week'] = $wo['week_bounds']['max'] ?? 1;
        }
        extract($wo);
    }

    $path = __DIR__ . '/../themes/wondertag/layout/' . $page_url . '.phtml';
    if (file_exists($path)) {
        ob_start();
        include $path;
        return ob_get_clean();
    }
    return "Template not found: " . $path;
}
/**
 * Wo_GetInternshipCalendarWeeks() — all events grouped by week number,
 * used for the overview/home page. Keyed array: [week_num => [events]].
 *
 * @param mysqli $conn
 * @return array<int, array<int, array<string, mixed>>>
 */
function Wo_GetInternshipCalendarWeeks(mysqli $conn): array
{
    global $wo;
    $user_id = !empty($wo['user']['user_id']) ? (int)$wo['user']['user_id'] : 0;
    
    $stmt = mysqli_prepare($conn, "SELECT e.*, IF(c.id IS NOT NULL, 1, 0) AS completed 
                                  FROM calendar_events e 
                                  LEFT JOIN calendar_event_completions c ON e.id = c.event_id AND c.user_id = ? 
                                  WHERE (e.user_id = ? OR e.user_id IS NULL) 
                                  ORDER BY e.week ASC, e.event_date ASC");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $weeks = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $weeks[$row['week']][] = $row;
        }
    }

    return $weeks;
}

/**
 * Wo_GetTimelineUser() — looks up a user by username from the
 * Wo_Users table. Returns null if not found.
 * Uses a prepared statement to avoid injection.
 *
 * @param mysqli $conn
 * @param string $username
 * @return array<string, mixed>|null
 */
function Wo_GetTimelineUser(mysqli $conn, string $username): ?array
{
    $query = "
        SELECT 
            user_id, 
            username, 
            CONCAT(first_name, ' ', last_name) AS name, 
            about, 
            avatar, 
            CASE 
                WHEN admin = '1' THEN 'admin'
                WHEN admin = '2' THEN 'mentor'
                ELSE 'intern'
            END AS role
        FROM Wo_Users 
        WHERE username = ? 
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row ?: null;
}

/* =====================================================================
   Authentication Functions
   ===================================================================== */

function Wo_Secure(mysqli $conn, string $string): string
{
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8'));
}

function Wo_LoadConfig(mysqli $conn): array
{
    $config = [];
    try {
        $result = mysqli_query($conn, "SELECT name, value FROM Wo_Config");
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $config[$row['name']] = $row['value'];
            }
        }
    } catch (\Exception $e) {
        // Table may not exist yet during initial setup
    }
    return $config;
}

function Wo_IsLogged(mysqli $conn): bool
{
    if (!empty($_SESSION['user_id'])) {
        $uid = Wo_GetUserFromSessionID($conn, $_SESSION['user_id']);
        if ($uid !== false) {
            return true;
        }
    }
    if (!empty($_COOKIE['user_id'])) {
        $uid = Wo_GetUserFromSessionID($conn, $_COOKIE['user_id']);
        if ($uid !== false) {
            return true;
        }
    }
    return false;
}

function Wo_GetUserFromSessionID(mysqli $conn, string $session_id): int|false
{
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM Wo_AppsSessions WHERE session_id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, "s", $session_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row ? (int) $row['user_id'] : false;
}

function Wo_Login(mysqli $conn, string $username, string $password): bool
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM Wo_Users WHERE (username = ? OR email = ?) AND active = 1 AND banned = 0 LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    if (!$user) {
        return false;
    }
    return password_verify($password, $user['password']);
}

function Wo_CreateLoginSession(mysqli $conn, int $user_id): string
{
    $hash = sha1((string) random_int(100000000, 999999999)) . md5(microtime()) . random_int(10000000, 99999999);
    // Delete any existing session with same hash
    $stmt = mysqli_prepare($conn, "DELETE FROM Wo_AppsSessions WHERE session_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $hash);
    mysqli_stmt_execute($stmt);
    // Insert new session
    $time = time();
    $platform = 'web';
    $stmt = mysqli_prepare($conn, "INSERT INTO Wo_AppsSessions (user_id, session_id, platform, time) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issi", $user_id, $hash, $platform, $time);
    mysqli_stmt_execute($stmt);
    return $hash;
}

function Wo_UserData(mysqli $conn, int $user_id): ?array
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM Wo_Users WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    if (!$row) {
        return null;
    }
    $row['name'] = $row['first_name'] . ' ' . $row['last_name'];
    $row['role'] = match ($row['admin']) {
        '1' => 'admin',
        '2' => 'mentor',
        default => 'intern',
    };
    return $row;
}

function Wo_RegisterUser(mysqli $conn, array $data): int|false
{
    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
        return false;
    }
    // Check username uniqueness
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM Wo_Users WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $data['username']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($result)) {
        return false;
    }
    // Check email uniqueness
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM Wo_Users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $data['email']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($result)) {
        return false;
    }
    // Hash password and insert
    $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $stmt = mysqli_prepare($conn, "INSERT INTO Wo_Users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $data['username'], $data['email'], $hashed, $first_name, $last_name);
    if (mysqli_stmt_execute($stmt)) {
        return (int) mysqli_insert_id($conn);
    }
    return false;
}

function Wo_IsAdmin(): bool
{
    global $wo;
    return ($wo['loggedin'] ?? false) && (($wo['user']['admin'] ?? '0') === '1');
}

function Wo_IsModerator(): bool
{
    global $wo;
    return ($wo['loggedin'] ?? false) && (($wo['user']['admin'] ?? '0') === '2');
}

function Wo_UserExists(mysqli $conn, string $username): bool
{
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM Wo_Users WHERE username = ? OR email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return (bool) mysqli_fetch_assoc($result);
}

function Wo_LastSeen(mysqli $conn, int $user_id): void
{
    $now = time();
    $stmt = mysqli_prepare($conn, "UPDATE Wo_Users SET lastseen = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $now, $user_id);
    mysqli_stmt_execute($stmt);
}

function Wo_ResetPassword(mysqli $conn, int $user_id, string $new_password): bool
{
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "UPDATE Wo_Users SET password = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
    return mysqli_stmt_execute($stmt);
}

function Wo_UserIdForLogin(mysqli $conn, string $username): int|false
{
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM Wo_Users WHERE (username = ? OR email = ?) AND active = 1 LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row ? (int) $row['user_id'] : false;
}

function Wo_SetLoginWithSession(mysqli $conn, string $email): void
{
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM Wo_Users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $session = Wo_CreateLoginSession($conn, (int) $row['user_id']);
        $_SESSION['user_id'] = $session;
    }
}

function Wo_ValidateCsrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'];
}

function WoCanLogin(mysqli $conn): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $window = time() - 900; // 15 minutes
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM Wo_Bad_Login WHERE ip = ? AND time > ?");
    if (!$stmt) {
        return true;
    }
    mysqli_stmt_bind_param($stmt, "si", $ip, $window);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return ($row['cnt'] ?? 0) < 5;
}

function WoAddBadLoginLog(mysqli $conn): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $now = time();
    $stmt = mysqli_prepare($conn, "INSERT INTO Wo_Bad_Login (ip, time) VALUES (?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $ip, $now);
        mysqli_stmt_execute($stmt);
    }
}

function Wo_DeleteBadLogins(mysqli $conn): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = mysqli_prepare($conn, "DELETE FROM Wo_Bad_Login WHERE ip = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $ip);
        mysqli_stmt_execute($stmt);
    }
}
/**
 * Wo_SendNudge() — inserts a nudge if one doesn't already exist
 * from this sender to this receiver.
 * Uses: mysqli_prepare()
 */
function Wo_SendNudge(mysqli $conn, int $sender, int $receiver): bool {
    // Check if a nudge already exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM calendar_nudges WHERE sender_id = ? AND receiver_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $sender, $receiver);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        return false; // Nudge already sent
    }
    mysqli_stmt_close($stmt);

    // Insert new nudge
    $stmt = mysqli_prepare($conn, "INSERT INTO calendar_nudges (sender_id, receiver_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'ii', $sender, $receiver);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Wo_NudgeBack() — deletes the inbound nudge, inserts a new one
 * in the opposite direction.
 * Uses: mysqli_prepare(), mysqli_begin_transaction()
 */
function Wo_NudgeBack(mysqli $conn, int $nudge_id, int $me, int $original_sender): bool {
    mysqli_begin_transaction($conn);
    try {
        // Delete inbound nudge
        $stmtDel = mysqli_prepare($conn, "DELETE FROM calendar_nudges WHERE id = ? AND receiver_id = ?");
        mysqli_stmt_bind_param($stmtDel, 'ii', $nudge_id, $me);
        mysqli_stmt_execute($stmtDel);
        $deleted_rows = mysqli_stmt_affected_rows($stmtDel);
        mysqli_stmt_close($stmtDel);

        // If the nudge didn't exist (already deleted/nudged back), don't insert a duplicate.
        if ($deleted_rows === 0) {
            mysqli_rollback($conn);
            return false; 
        }

        // Send nudge back - check if a back-nudge already exists to be extra safe
        $stmtCheck = mysqli_prepare($conn, "SELECT id FROM calendar_nudges WHERE sender_id = ? AND receiver_id = ?");
        mysqli_stmt_bind_param($stmtCheck, 'ii', $me, $original_sender);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);
        $exists = mysqli_stmt_num_rows($stmtCheck) > 0;
        mysqli_stmt_close($stmtCheck);

        if (!$exists) {
            $stmtIns = mysqli_prepare($conn, "INSERT INTO calendar_nudges (sender_id, receiver_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmtIns, 'ii', $me, $original_sender);
            mysqli_stmt_execute($stmtIns);
            mysqli_stmt_close($stmtIns);
        }
        
        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

/**
 * Wo_GetNudgesForUser() — returns all nudges received by a user,
 * joined with sender's name/avatar/username.
 * Uses: mysqli_prepare()
 */
function Wo_GetNudgesForUser(mysqli $conn, int $user_id): array {
    $stmt = mysqli_prepare($conn, 
        "SELECT n.id, n.sender_id, n.created_at, u.username, CONCAT(u.first_name, ' ', u.last_name) AS name, u.avatar 
         FROM calendar_nudges n
         JOIN Wo_Users u ON n.sender_id = u.user_id
         WHERE n.receiver_id = ?
         ORDER BY n.created_at DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $nudges = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $nudges[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $nudges;
}

/**
 * Wo_GetUserPosts() — fetches social posts for a user
 *
 * @param mysqli $conn
 * @param int $user_id
 * @return array
 */
function Wo_GetUserPosts(mysqli $conn, int $user_id): array {
    $stmt = mysqli_prepare($conn, 
        "SELECT p.id, p.postText, p.time, u.username, CONCAT(u.first_name, ' ', u.last_name) AS name, u.avatar 
         FROM Wo_Posts p
         JOIN Wo_Users u ON p.user_id = u.user_id
         WHERE p.user_id = ? AND p.active = 1
         ORDER BY p.time DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $posts = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $posts[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $posts;
}

/**
 * Wo_GetRandomUsers() - Fetch random active users for sidebar suggestions
 */
function Wo_GetRandomUsers(mysqli $conn, int $current_user_id, int $limit = 5): array {
    $stmt = mysqli_prepare($conn, "SELECT user_id, username, CONCAT(first_name, ' ', last_name) as name, avatar FROM Wo_Users WHERE active = '1' AND user_id != ? ORDER BY RAND() LIMIT ?");
    mysqli_stmt_bind_param($stmt, 'ii', $current_user_id, $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $users = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            // Provide fallback name if empty
            if (trim($row['name']) === '') {
                $row['name'] = $row['username'];
            }
            $users[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $users;
}
