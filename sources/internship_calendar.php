<?php
$page_title = 'Calendar';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Get filters from URL
$week   = isset($_GET['week']) ? intval($_GET['week']) : Wo_GetCurrentInternshipWeek($conn);
if ($week < 1) $week = 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch data via functions
$wo = [];
$wo['page_title']     = $page_title;
$wo['week']           = $week;
$wo['search']         = $search;
$wo['calendar_events'] = Wo_GetInternshipCalendarEvents($conn, [
    'week'   => $week,
    'search' => $search,
]);
$wo['week_bounds'] = Wo_GetInternshipCalendarWeekBounds($conn);

// Render page — platform's own layout flow wraps this
$wo['content'] = Wo_LoadPage('internship_calendar/content');