<?php

declare(strict_types=1);

/**
 * sources/leaderboard.php — Leaderboard page controller.
 *
 * Loads the leaderboard view for the logged-in user.
 * Data fetching is handled client-side via xhr/leaderboard.php (Dev 2).
 *
 * Routing:
 *   ?link1=leaderboard → main leaderboard view
 */

if (!Wo_IsLogged($conn) || empty($wo['user']['user_id'])) {
    header("Location: ?link1=welcome");
    exit();
}

$wo['page_title'] = 'Leaderboard';

// Pass current user ID for JS-side rank highlighting
$wo['leaderboard_user_id'] = (int) $wo['user']['user_id'];

// Render the leaderboard template (created by Dev 3)
$wo['content'] = Wo_LoadPage('leaderboard/content');
