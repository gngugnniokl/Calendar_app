<?php
declare(strict_types=1);

require_once __DIR__ . '/../assets/init.php';
header('Content-Type: application/json');

if (!Wo_IsLogged($conn) || empty($wo['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Auth required.']);
    exit();
}

$s = isset($_POST['s']) ? trim((string)$_POST['s']) : '';
$me = (int)$wo['user']['user_id'];

switch ($s) {

    case 'send_nudge':
        $receiver = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
        if ($receiver < 1 || $receiver === $me) {
            echo json_encode(['success' => false, 'message' => 'Invalid user.']);
            exit();
        }
        // Prevent duplicate nudge (one active nudge per sender→receiver pair)
        Wo_SendNudge($conn, $me, $receiver);
        echo json_encode(['success' => true]);
        exit();

    case 'nudge_back':
        $nudge_id = isset($_POST['nudge_id']) ? (int)$_POST['nudge_id'] : 0;
        $sender   = isset($_POST['sender_id']) ? (int)$_POST['sender_id'] : 0;
        if ($nudge_id < 1 || $sender < 1) {
            echo json_encode(['success' => false, 'message' => 'Invalid nudge.']);
            exit();
        }
        // Delete the inbound nudge, then insert a new one going back
        Wo_NudgeBack($conn, $nudge_id, $me, $sender);
        echo json_encode(['success' => true]);
        exit();

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit();
}
