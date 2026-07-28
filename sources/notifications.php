<?php
declare(strict_types=1);

if (!Wo_IsLogged($conn) || empty($wo['user']['user_id'])) {
    header("Location: ?link1=welcome");
    exit();
}

$wo['page_title'] = 'Notifications';
$wo['nudges'] = Wo_GetNudgesForUser($conn, (int)$wo['user']['user_id']); // Include nudges here as requested
$wo['content'] = Wo_LoadPage('notifications/content');
