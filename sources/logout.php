<?php
declare(strict_types=1);

// Delete session from DB
if (!empty($_SESSION['user_id'])) {
    $token = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM Wo_AppsSessions WHERE session_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
}

// Delete cookie session from DB
if (!empty($_COOKIE['user_id'])) {
    $token = $_COOKIE['user_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM Wo_AppsSessions WHERE session_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    setcookie('user_id', '', -1, '/');
}

session_unset();
session_destroy();

header('Location: ?link1=welcome');
exit();
