<?php

declare(strict_types=1);

/**
 * xhr/timeline.php — AJAX handler for timeline actions.
 *
 * Dispatches on $_POST['s'] (sub-function).
 * All responses are JSON: { success: bool, message: string, data: ... }
 */

require_once __DIR__ . '/../assets/init.php';

header('Content-Type: application/json');

// Auth guard
if (!Wo_IsLogged($conn)) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$s = isset($_POST['s']) ? trim((string) $_POST['s']) : '';

switch ($s) {

    // --- Load profile data via AJAX ---
    case 'get_profile':
        $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
        if ($username === '') {
            echo json_encode(['success' => false, 'message' => 'Username required.']);
            exit();
        }
        $user = Wo_GetTimelineUser($conn, $username);
        if ($user === null) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit();
        }
        echo json_encode(['success' => true, 'message' => '', 'data' => $user]);
        exit();

    // --- Load user's timeline events ---
    case 'get_user_events':
        $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
        if ($username === '') {
            echo json_encode(['success' => false, 'message' => 'Username required.']);
            exit();
        }
        $events = Wo_GetInternshipCalendarEvents($conn, []);
        echo json_encode(['success' => true, 'message' => '', 'data' => $events]);
        exit();

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown sub-function: ' . htmlspecialchars($s)]);
        exit();
}
