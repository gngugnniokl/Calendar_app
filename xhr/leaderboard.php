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
        $account_types = isset($_GET['account_types']) ? explode(',', (string)$_GET['account_types']) : [];
        $top7 = Wo_GetLeaderboardData($conn, [
            'limit' => 7,
            'account_types' => $account_types
        ]);
        
        // Assign sequential ranks 1-7 based on sorted position
        $rankPos = 1;
        foreach ($top7 as &$user) {
            $user['rank'] = $rankPos;
            $user['tokens'] = (int) $user['points'];
            $user['status'] = ($rankPos === 1) ? 'KING' : (($rankPos <= 3) ? 'QUEEN' : 'MEMBER');
            $user['trend']  = 'neutral'; 

            // Ensure avatar path is correct
            if (!empty($user['avatar']) && strpos($user['avatar'], 'http') !== 0) {
                $user['avatar'] = $wo['config']['site_url'] . '/' . $user['avatar'];
            }
            $rankPos++;
        }

        echo json_encode([
            'success' => true,
            'data' => $top7 // Return flat array as expected by JS
        ]);
        exit();

    case 'rankings':
        // Paginated rankings for the table (Dev 4)
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $account_types = isset($_GET['account_types']) ? explode(',', (string)$_GET['account_types']) : [];
        
        $offset = ($page - 1) * $limit;

        $rankings = Wo_GetLeaderboardData($conn, [
            'limit'  => $limit,
            'offset' => $offset,
            'search' => $search,
            'account_types' => $account_types
        ]);
        
        $total = Wo_GetLeaderboardTotalCount($conn, $search, $account_types);

        // Alignment: Map points to tokens and add UI-specific fields
        $rankPos = $offset + 1;
        foreach ($rankings as &$user) {
            $user['rank'] = $rankPos;
            $user['tokens'] = (int) $user['points'];
            $user['status'] = ($rankPos === 1) ? 'KING' : (($rankPos <= 3) ? 'QUEEN' : 'MEMBER');
            $user['trend']  = 'neutral';
            // Ensure avatar path is correct
            if (!empty($user['avatar']) && strpos($user['avatar'], 'http') !== 0) {
                $user['avatar'] = $wo['config']['site_url'] . '/' . $user['avatar'];
            }
            $rankPos++;
        }

        echo json_encode([
            'success' => true,
            'data' => $rankings,
            'total' => $total,
            'totalPages' => ceil($total / $limit),
            'pagination' => [
                'limit'  => $limit,
                'page'   => $page,
                'has_more' => ($offset + count($rankings)) < $total
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
                'tokens' => (int)$data['points'], // Send as number for JS to format
                'rank'   => (int)$data['rank'],
                'progress_pct' => round($progress, 1) // Match JS property name
            ]
        ]);
        exit();

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . htmlspecialchars($action)]);
        exit();
}
