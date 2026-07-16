<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$current_user_id = 0;

if (!empty($_SESSION['user_id'])) {
    $current_user_id = (int) $_SESSION['user_id'];
} elseif (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['user_id'])) {
    $current_user_id = (int) $_SESSION['user']['user_id'];
}

if ($current_user_id <= 0) {
    redirect('index.php');
}

$wo = [];
$wo['page_title'] = 'Nudges';
$wo['nudges'] = Wo_GetNudgesForUser($conn, $current_user_id);

$wo['content'] = Wo_LoadPage('nudges/content');
