<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function wo_json_response(bool $success, string $message, array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function wo_current_user_id(): int
{
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['user_id'])) {
        return (int) $_SESSION['user']['user_id'];
    }

    return 0;
}

$me = wo_current_user_id();

if ($me <= 0) {
    wo_json_response(false, 'Authentication required.', [], 401);
}

$action = trim((string) ($_REQUEST['s'] ?? ''));

if ($action === 'send_nudge') {
    $receiver = (int) ($_POST['receiver_id'] ?? $_POST['receiver'] ?? $_REQUEST['receiver_id'] ?? $_REQUEST['receiver'] ?? 0);

    if ($receiver <= 0) {
        wo_json_response(false, 'Invalid receiver.', [], 400);
    }

    if (!Wo_SendNudge($conn, $me, $receiver)) {
        wo_json_response(false, 'Unable to send nudge.', [], 400);
    }

    wo_json_response(true, 'Nudge sent.');
}

if ($action === 'nudge_back') {
    $nudge_id = (int) ($_POST['nudge_id'] ?? $_POST['id'] ?? $_REQUEST['nudge_id'] ?? $_REQUEST['id'] ?? 0);
    $original_sender = (int) ($_POST['original_sender'] ?? $_POST['sender_id'] ?? $_REQUEST['original_sender'] ?? $_REQUEST['sender_id'] ?? 0);

    if ($nudge_id <= 0 || $original_sender <= 0) {
        wo_json_response(false, 'Invalid nudge request.', [], 400);
    }

    if (!Wo_NudgeBack($conn, $nudge_id, $me, $original_sender)) {
        wo_json_response(false, 'Unable to nudge back.', [], 400);
    }

    wo_json_response(true, 'Nudge returned.');
}

wo_json_response(false, 'Unknown action.', [], 400);
