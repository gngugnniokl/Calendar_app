<?php
declare(strict_types=1);

require_once __DIR__ . '/../assets/init.php';
header('Content-Type: application/json');

if (!Wo_IsLogged($conn) || empty($wo['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Auth required.']);
    exit();
}

$color = isset($_POST['color']) ? trim((string)$_POST['color']) : '';
$me = (int)$wo['user']['user_id'];

// Validate hex color (basic)
if (preg_match('/^#[a-fA-F0-9]{6}$/', $color) || preg_match('/^#[a-fA-F0-9]{3}$/', $color)) {
    $stmt = mysqli_prepare($conn, "UPDATE Wo_Users SET profile_color = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $color, $me);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid color.']);
exit();
