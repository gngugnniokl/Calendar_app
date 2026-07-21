<?php
declare(strict_types=1);

require_once __DIR__ . '/../assets/init.php';
header('Content-Type: application/json');

if (!Wo_IsLogged($conn) || empty($wo['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Auth required.']);
    exit();
}

$me = (int)$wo['user']['user_id'];

if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['name'])) {
    $fileInfo = array(
        'file' => $_FILES["avatar"]["tmp_name"],
        'name' => $_FILES['avatar']['name'],
        'size' => $_FILES["avatar"]["size"],
        'type' => $_FILES["avatar"]["type"]
    );
    
    // Check if it's an image
    $mime = substr($fileInfo["type"], 0, 5);
    if ($mime !== 'image') {
        echo json_encode(['success' => false, 'message' => 'Only images are allowed.']);
        exit();
    }
    
    // Setup directory
    if (!file_exists('../upload/photos/')) {
        mkdir('../upload/photos/', 0777, true);
    }
    
    $ext = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
    $new_filename = 'upload/photos/' . md5(time() . rand(11, 99)) . '.' . $ext;
    
    if (move_uploaded_file($fileInfo['file'], '../' . $new_filename)) {
        // Update user record
        $stmt = mysqli_prepare($conn, "UPDATE Wo_Users SET avatar = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_filename, $me);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'avatar' => $new_filename]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        mysqli_stmt_close($stmt);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'File upload failed.']);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
exit();
