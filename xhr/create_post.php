<?php
declare(strict_types=1);

require_once __DIR__ . '/../assets/init.php';
header('Content-Type: application/json');

if (!Wo_IsLogged($conn) || empty($wo['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Auth required.']);
    exit();
}

$me = (int)$wo['user']['user_id'];
$postText = isset($_POST['postText']) ? trim($_POST['postText']) : '';
$postLink = isset($_POST['postLink']) ? trim($_POST['postLink']) : '';

$file_url = '';
$file_name = '';

// Handle file upload
if (isset($_FILES['postFile']) && !empty($_FILES['postFile']['name'])) {
    $fileInfo = array(
        'file' => $_FILES["postFile"]["tmp_name"],
        'name' => $_FILES['postFile']['name'],
        'size' => $_FILES["postFile"]["size"],
        'type' => $_FILES["postFile"]["type"],
        'types' => 'jpg,png,gif,jpeg,zip,pdf,doc,docx,xls,xlsx,ppt,pttx,txt'
    );
    
    // Check if it's explicitly an audio or video
    $mime = substr($_FILES["postFile"]["type"], 0, 5);
    if ($mime === 'video' || $mime === 'audio') {
        echo json_encode(['success' => false, 'message' => 'Video and audio files are not allowed.']);
        exit();
    }
    
    // In a real app we'd use a proper upload handler like Wo_ShareFile
    // For this simple task, let's move it to a simple uploads folder
    if (!file_exists('../upload/files/')) {
        mkdir('../upload/files/', 0777, true);
    }
    
    $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
    if (!in_array($ext, $allowed_exts)) {
        echo json_encode(['success' => false, 'message' => 'Security Error: File type not allowed.']);
        exit();
    }
    $new_filename = 'upload/files/' . md5(time() . rand(11, 99)) . '.' . $ext;
    
    if (move_uploaded_file($fileInfo['file'], '../' . $new_filename)) {
        $file_url = $new_filename;
        $file_name = $fileInfo['name'];
    } else {
        echo json_encode(['success' => false, 'message' => 'File upload failed.']);
        exit();
    }
}

if (empty($postText) && empty($file_url) && empty($postLink)) {
    echo json_encode(['success' => false, 'message' => 'Post cannot be empty.']);
    exit();
}

$time = time();
$stmt = mysqli_prepare($conn, "INSERT INTO Wo_Posts (user_id, postText, postFile, postFileName, postLink, time, active) VALUES (?, ?, ?, ?, ?, ?, 1)");
mysqli_stmt_bind_param($stmt, "issssi", $me, $postText, $file_url, $file_name, $postLink, $time);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
mysqli_stmt_close($stmt);
exit();
