<?php
declare(strict_types=1);

if (!Wo_IsLogged($conn) || empty($wo['user']['user_id'])) {
    header("Location: ?link1=welcome");
    exit();
}

$wo['page_title'] = 'Nudges';
$wo['nudges'] = Wo_GetNudgesForUser($conn, (int)$wo['user']['user_id']);
$wo['content'] = Wo_LoadPage('nudges/content');
