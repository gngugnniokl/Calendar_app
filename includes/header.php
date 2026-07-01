<?php
// session_start() must run before any HTML output — needed for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// require_once() so the DB connection and helpers are never loaded twice
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($page_title) ? clean($page_title) . ' · Tribbbal Internship Calendar' : 'Tribbbal Internship Calendar'; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/internship-calendar/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="/internship-calendar/index.php" class="brand">
            <span class="brand-mark">TC</span>
            <span class="brand-text">Tribbbal <em>Internship Log</em></span>
        </a>
        <nav class="main-nav">
            <a href="/internship-calendar/index.php" class="<?php echo active_page('index.php'); ?>">Home</a>
            <a href="/internship-calendar/calendar.php" class="<?php echo active_page('calendar.php'); ?>">Calendar</a>
            <a href="/internship-calendar/search.php" class="<?php echo active_page('search.php'); ?>">Search</a>
            <a href="/internship-calendar/dashboard.php" class="<?php echo active_page('dashboard.php'); ?>">Dashboard</a>
            <a href="/internship-calendar/add-event.php" class="nav-cta <?php echo active_page('add-event.php'); ?>">+ Add Event</a>
        </nav>
    </div>
</header>

<?php if ($flash): ?>
<div class="flash flash-<?php echo clean($flash['type']); ?>">
    <div class="flash-inner"><?php echo clean($flash['message']); ?></div>
</div>
<?php endif; ?>

<main class="page-wrap">