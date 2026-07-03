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
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($wo['page_title'] ?? 'Calendar') . '</title>
    <!-- Include fonts requested by the CSS variables -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;600&family=Inter:wght@400;500;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/themes/wondertag/css/internship-calendar.css?v=' . time() . '">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="?link1=internship_calendar" class="brand">
                <span class="brand-mark"></span>
                <span class="brand-text">Tribbbal Internship</span>
            </a>
            <nav class="main-nav">
                <a href="?link1=internship_calendar">Calendar</a>
                <a href="?link1=internship_calendar_dashboard">Dashboard</a>
            </nav>
        </div>
    </header>

    <main class="page-wrap">
        ' . ($wo['content'] ?? '') . '
    </main>
';

include __DIR__ . '/themes/wondertag/layout/internship_calendar/includes/event-modal.phtml';

echo '
    <script src="/themes/wondertag/javascript/internship-calendar.js?v=' . time() . '"></script>
</body>
</html>';
