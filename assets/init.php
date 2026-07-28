<?php
/**
 * assets/init.php — Bootstrap chain.
 * Every request flows through here before page routing.
 */

// Security headers
@ini_set('session.cookie_httponly', '1');
@ini_set('session.use_only_cookies', '1');
@header("X-FRAME-OPTIONS: SAMEORIGIN");

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database + functions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Build the $wo global
$wo = [];

// Load site config
$wo['config'] = Wo_LoadConfig($conn);

// Check authentication
$wo['loggedin'] = Wo_IsLogged($conn);

if ($wo['loggedin']) {
    $session_token = $_SESSION['user_id'] ?? ($_COOKIE['user_id'] ?? '');
    $uid = Wo_GetUserFromSessionID($conn, $session_token);
    if ($uid) {
        $wo['user'] = Wo_UserData($conn, (int) $uid);
        Wo_LastSeen($conn, (int) $uid);
    } else {
        $wo['loggedin'] = false;
    }
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$wo['csrf_token'] = $_SESSION['csrf_token'];
