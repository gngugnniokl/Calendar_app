<?php
$page_title = 'Dashboard';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$stats = Wo_GetInternshipCalendarStats($conn);

$wo = [];
$wo['page_title']       = $page_title;
$wo['total_weeks']      = $stats['total_weeks'];
$wo['total_days']       = $stats['total_days'];
$wo['total_events']     = $stats['total_events'];
$wo['current_week']     = $stats['current_week'];
$wo['completed_count']  = $stats['completed_count'];
$wo['upcoming_count']   = $stats['upcoming_count'];
$wo['program_length']   = $stats['program_length'];
$wo['progress_pct']     = $stats['progress_pct'];
$wo['recent_completed'] = array_slice(array_reverse($stats['completed']), 0, 5);
$wo['up_next']          = array_slice($stats['upcoming'], 0, 5);

$wo['content'] = Wo_LoadPage('internship_calendar/dashboard');