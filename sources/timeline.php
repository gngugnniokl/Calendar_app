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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

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
    $wo = [];
    $wo['page_title'] = 'Not Found';
    $wo['content']    = '<section class="page-intro"><h1>User not found</h1>'
                      . '<p>No profile exists for <strong>' . clean($username) . '</strong>.</p>'
                      . '<a href="?link1=internship_calendar" class="btn btn-primary">Back to Calendar</a></section>';
    return;
}

// --- Build the $wo state for the template ---
$wo = [];
$wo['page_title']    = $profile['name'] ?: $profile['username'];
$wo['user_profile']  = $profile;
$wo['timeline_type'] = $type;

// Load this user's calendar activity (events they're associated with, or all if admin/mentor)
$wo['user_events'] = Wo_GetInternshipCalendarEvents($conn, []);

// Render
$wo['content'] = Wo_LoadPage('timeline/content');
