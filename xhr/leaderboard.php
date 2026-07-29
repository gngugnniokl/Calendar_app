<?php

declare(strict_types=1);

/**
 * xhr/leaderboard.php — AJAX handler for leaderboard data.
 *
 * Actions:
 *   top7     -> Fetch hero podium data
 *   rankings -> Fetch paginated/searchable table data
 *   user     -> Fetch current user's progress for sticky footer
 */

require_once __DIR__ . '/../assets/init.php';

header('Content-Type: application/json');

// Auth guard
if (!Wo_IsLogged($conn)) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';

switch ($action) {

    case 'top7':
        // Fetch top 7 for the podium and cards (Dev 3)
        $top7 = Wo_GetLeaderboardData($conn, ['limit' => 7]);
        
        // Map points to tokens to align with UI terminology
        foreach ($top7 as &$user) {
            $user['tokens'] = $user['points'];
            // UI expects 'status' and 'trend' fields for some components
            $user['status'] = ($user['rank'] === 1) ? 'KING' : (($user['rank'] <= 3) ? 'QUEEN' : 'MEMBER');
            $user['trend']  = 'neutral'; 
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'podium' => array_slice($top7, 0, 3), // Top 3
                'others' => array_slice($top7, 3)     // 4-7
            ]
        ]);
        exit();

    case 'rankings':
        // Paginated rankings for the table (Dev 4)
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

        $rankings = Wo_GetLeaderboardData($conn, [
            'limit'  => $limit,
            'offset' => $offset,
            'search' => $search
        ]);

        // Alignment: Map points to tokens and add UI-specific fields
        foreach ($rankings as &$user) {
            $user['tokens'] = $user['points'];
            $user['status'] = ($user['rank'] === 1) ? 'KING' : (($user['rank'] <= 3) ? 'QUEEN' : 'MEMBER');
            $user['trend']  = 'neutral';
        }

        echo json_encode([
            'success' => true,
            'data' => $rankings,
            'pagination' => [
                'limit'  => $limit,
                'offset' => $offset,
                'has_more' => count($rankings) === $limit
            ]
        ]);
        exit();

    case 'user':
        // Current user's stats for sticky footer (Dev 6)
        $user_id = (int)($wo['user']['user_id'] ?? 0);
        $data = Wo_GetUserRank($conn, $user_id);
        
        // Progress bar logic: token progress towards next 1000 increment
        $progress = ($data['points'] % 1000) / 10; 

        echo json_encode([
            'success' => true,
            'data' => [
                'tokens' => number_format((float)$data['points']),
                'points' => (int)$data['points'],
                'rank'   => $data['rank'],
                'progress_percent' => round($progress, 1)
            ]
        ]);
        exit();

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . htmlspecialchars($action)]);
        exit();
}
