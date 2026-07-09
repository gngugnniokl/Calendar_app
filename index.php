<?php
// Serve static files directly if using PHP's built-in server
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (php_sapi_name() === 'cli-server' && $path !== '/' && $path !== '/index.php' && is_file(__DIR__ . $path)) {
    return false;
}

// Main entry point for local testing
$link1 = $_GET['link1'] ?? 'internship_calendar';
$valid_pages = [
    'internship_calendar',
    'internship_calendar_dashboard',
    'internship_calendar_event'
];

if (in_array($link1, $valid_pages)) {
    require_once __DIR__ . "/sources/{$link1}.php";
} else {
    require_once __DIR__ . "/sources/internship_calendar.php";
}

// Output the container layout
include __DIR__ . '/themes/wondertag/layout/container.phtml';
