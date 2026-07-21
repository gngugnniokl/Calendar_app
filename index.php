<?php
// Serve static files directly if using PHP's built-in server
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (php_sapi_name() === 'cli-server' && $path !== '/' && $path !== '/index.php' && is_file(__DIR__ . $path)) {
    return false;
}

// Bootstrap
require_once __DIR__ . '/assets/init.php';

// Determine page
$link1 = $_GET['link1'] ?? '';

// Public pages (no auth required)
$public_pages = ['welcome', 'register', 'forgot_password', 'reset_password', 'logout'];

// Protected pages (auth required)
$protected_pages = [
    'internship_calendar',
    'internship_calendar_dashboard',
    'internship_calendar_event',
    'internship_calendar_home',
    'timeline',
    'nudges',
    'notifications',
];

$all_pages = array_merge($public_pages, $protected_pages);

// Default routing
if ($link1 === '') {
    $link1 = $wo['loggedin'] ? 'timeline' : 'welcome';
}

// Auth gate: redirect unauthenticated users from protected pages
if (in_array($link1, $protected_pages) && !$wo['loggedin']) {
    header('Location: ?link1=welcome');
    exit();
}

// Redirect logged-in users away from auth pages
if (in_array($link1, ['welcome', 'register']) && $wo['loggedin']) {
    header('Location: ?link1=timeline');
    exit();
}

// Route to page controller
if (in_array($link1, $all_pages)) {
    require_once __DIR__ . "/sources/{$link1}.php";
} else {
    // 404
    $wo['page_title'] = 'Not Found';
    $wo['content'] = '<section class="page-intro"><h1>Page not found</h1><p>The page you requested does not exist.</p><a href="?link1=internship_calendar" class="btn btn-primary">Go Home</a></section>';
}

// Output the container layout
include __DIR__ . '/themes/wondertag/layout/container.phtml';
