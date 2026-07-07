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

// Check if any specific view or filters are requested
$has_filters = !empty($_GET['week']) || !empty($_GET['search']) || !empty($_GET['day']) || !empty($_GET['from']) || !empty($_GET['to']);

$is_all_weeks = empty($_GET['week']);
$week   = !$is_all_weeks ? intval($_GET['week']) : Wo_GetCurrentInternshipWeek($conn);
if ($week < 1) {
    $week = 1;
}
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$day    = isset($_GET['day']) ? trim((string) $_GET['day']) : '';
$from   = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
$to     = isset($_GET['to']) ? trim((string) $_GET['to']) : '';

$has_active_search_filters = ($search !== '' || $day !== '' || $from !== '' || $to !== '');

if (!empty($_GET['export']) && $_GET['export'] === 'excel') {
    $export_filters = [];
    if (!$is_all_weeks || $has_active_search_filters) {
        $export_filters['week'] = $is_all_weeks ? null : $week;
        $export_filters['search'] = $search;
        $export_filters['day'] = $day;
        $export_filters['from'] = $from;
        $export_filters['to'] = $to;
    }

    Wo_ExportInternshipCalendarExcel($conn, $export_filters);
}

if (!$has_filters) {
    // Show Home Page
    $wo = [];
    $wo['page_title']   = $page_title;
    $wo['weeks']        = Wo_GetInternshipCalendarWeeks($conn);
    $wo['current_week'] = Wo_GetCurrentInternshipWeek($conn);
    
    $wo['content'] = Wo_LoadPage('internship_calendar/home');
} else {
    // Fetch data via functions
    $wo = [];
    $wo['page_title']      = $page_title;
    $wo['week']            = $week;
    $wo['is_all_weeks']    = $is_all_weeks;
    $wo['has_active_search_filters'] = $has_active_search_filters;
    $wo['search']          = $search;
    $wo['day']             = $day;
    $wo['from']            = $from;
    $wo['to']              = $to;
    $wo['calendar_events'] = Wo_GetInternshipCalendarEvents($conn, [
        'week'   => ($is_all_weeks ? null : $week), // Allow "All weeks" if search/filters are active
        'search' => $search,
        'day'    => $day,
        'from'   => $from,
        'to'     => $to,
    ]);
    $wo['week_bounds'] = Wo_GetInternshipCalendarWeekBounds($conn);

    // Render page — platform's own layout flow wraps this
    $wo['content'] = Wo_LoadPage('internship_calendar/content');
}