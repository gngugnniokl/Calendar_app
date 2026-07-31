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

// Pre-populate top 7 for server-side rendering of the podium
$top7 = Wo_GetLeaderboardData($conn, ['limit' => 7]);
$rankPos = 1;
foreach ($top7 as &$user) {
    $user['rank'] = $rankPos;
    $user['tokens'] = (int) $user['points'];
    $user['status'] = ($rankPos === 1) ? 'KING' : (($rankPos <= 3) ? 'QUEEN' : 'MEMBER');
    $rankPos++;
}
unset($user);
$wo['top7_rankings'] = $top7;

// Render the leaderboard template (created by Dev 3)
$wo['content'] = Wo_LoadPage('leaderboard/content');
