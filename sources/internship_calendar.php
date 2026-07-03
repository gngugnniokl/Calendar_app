<?php

declare(strict_types=1);

/**
 * Task A3: this file only ever does one thing — resolve the current
 * request's week/search filters, load data through functions.php, and
 * hand off to the content.phtml template (Dev C). No SQL and no HTML
 * live here.
 */

$page_title = 'Calendar';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Get filters from URL
$week   = isset($_GET['week']) ? intval($_GET['week']) : Wo_GetCurrentInternshipWeek($conn);
if ($week < 1) {
    $week = 1;
}
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$day    = isset($_GET['day']) ? trim((string) $_GET['day']) : '';
$from   = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
$to     = isset($_GET['to']) ? trim((string) $_GET['to']) : '';

// Fetch data via functions
$wo = [];
$wo['page_title']      = $page_title;
$wo['week']            = $week;
$wo['search']          = $search;
$wo['calendar_events'] = Wo_GetInternshipCalendarEvents($conn, [
    'week'   => $week,
    'search' => $search,
    'day'    => $day,
    'from'   => $from,
    'to'     => $to,
]);
$wo['week_bounds'] = Wo_GetInternshipCalendarWeekBounds($conn);

// Render page — platform's own layout flow wraps this
$wo['content'] = Wo_LoadPage('internship_calendar/content');