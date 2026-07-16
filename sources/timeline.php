<?php

declare(strict_types=1);

/**
 * sources/timeline.php — Timeline (user profile) page controller.
 *
 * Resolves the requested username from $_GET['u'], loads the user's
 * data, and hands off to the timeline/content.phtml template.
 *
 * Routing:
 *   ?link1=timeline&u=username      → user profile
 *   ?link1=timeline&u=username&type=events → sub-view (future)
 */

// --- Resolve the requested user ---
$username = isset($_GET['u']) ? trim((string) $_GET['u']) : '';
$type     = isset($_GET['type']) ? trim((string) $_GET['type']) : '';

if ($username === '') {
    // No username supplied — redirect to home
    header('Location: ?link1=internship_calendar');
    exit();
}

// Look up the user
$profile = Wo_GetTimelineUser($conn, $username);

if ($profile === null) {
    // User not found — show a 404-style message inside the normal layout
    $wo['page_title'] = 'Not Found';
    $wo['content']    = '<section class="page-intro"><h1>User not found</h1>'
                      . '<p>No profile exists for <strong>' . clean($username) . '</strong>.</p>'
                      . '<a href="?link1=internship_calendar" class="btn btn-primary">Back to Calendar</a></section>';
    return;
}

// --- Build the $wo state for the template ---
$wo['page_title']    = $profile['name'] ?: $profile['username'];
$wo['user_profile']  = $profile;
$wo['timeline_type'] = $type;

// Load this user's social posts for the timeline feed
$wo['user_posts'] = Wo_GetUserPosts($conn, (int) $profile['user_id']);

// Calendar Timeline Strip data
require_once __DIR__ . '/../includes/timeline_calendar.php';
$wo['timeline_events'] = Wo_GetUserTimelineEvents($conn, (int) $profile['user_id']);

// Load random active users for the sidebar suggestions/chat (excluding logged-in user)
$current_uid = !empty($wo['user']['user_id']) ? (int)$wo['user']['user_id'] : 0;
$wo['sidebar_users'] = Wo_GetRandomUsers($conn, $current_uid, 6);

// Render
$wo['content'] = Wo_LoadPage('timeline/content');
