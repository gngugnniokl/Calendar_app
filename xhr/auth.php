<?php
declare(strict_types=1);

require_once __DIR__ . '/../assets/init.php';

header('Content-Type: application/json');

function respond(bool $success, string $message = '', $data = null): void {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data ?? new stdClass(),
    ]);
    exit();
}

$s = trim($_POST['s'] ?? '');

switch ($s) {
    case 'login':
        // Validate CSRF
        if (!Wo_ValidateCsrf()) {
            respond(false, 'Invalid request token. Please refresh and try again.');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            respond(false, 'Please enter your username/email and password.');
        }

        // Check brute force
        if (WoCanLogin($conn) === false) {
            respond(false, 'Too many login attempts. Please try again later.');
        }

        if (!Wo_Login($conn, $username, $password)) {
            WoAddBadLoginLog($conn);
            respond(false, 'Invalid username/email or password.');
        }

        $user_id = Wo_UserIdForLogin($conn, $username);
        if ($user_id === false) {
            respond(false, 'Account not found or inactive.');
        }

        // Create session
        $session = Wo_CreateLoginSession($conn, (int) $user_id);
        $_SESSION['user_id'] = $session;

        // Update IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = mysqli_prepare($conn, "UPDATE Wo_Users SET ip_address = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $ip, $user_id);
        mysqli_stmt_execute($stmt);

        // Clear bad logins
        Wo_DeleteBadLogins($conn);

        respond(true, 'Login successful.', ['location' => '?link1=timeline']);
        break;

    case 'register':
        if (!Wo_ValidateCsrf()) {
            respond(false, 'Invalid request token.');
        }

        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $username   = trim($_POST['username'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $confirm    = $_POST['confirm_password'] ?? '';

        if ($username === '' || $email === '' || $password === '' || $first_name === '') {
            respond(false, 'Please fill in all required fields.');
        }

        if (strlen($password) < 6) {
            respond(false, 'Password must be at least 6 characters.');
        }

        if ($password !== $confirm) {
            respond(false, 'Passwords do not match.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(false, 'Please enter a valid email address.');
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            respond(false, 'Username may only contain letters, numbers, and underscores.');
        }

        if (strlen($username) < 3 || strlen($username) > 32) {
            respond(false, 'Username must be between 3 and 32 characters.');
        }

        $result = Wo_RegisterUser($conn, [
            'username'   => $username,
            'email'      => $email,
            'password'   => $password,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ]);

        if ($result === false) {
            respond(false, 'Username or email already exists.');
        }

        // Auto-login
        $session = Wo_CreateLoginSession($conn, (int) $result);
        $_SESSION['user_id'] = $session;

        respond(true, 'Account created successfully.', ['location' => '?link1=timeline']);
        break;

    case 'forgot_password':
        if (!Wo_ValidateCsrf()) {
            respond(false, 'Invalid request token.');
        }

        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            respond(false, 'Please enter your email address.');
        }

        $stmt = mysqli_prepare($conn, "SELECT user_id FROM Wo_Users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            // Don't reveal whether email exists
            respond(true, 'If that email is registered, a reset code has been generated.', ['code' => '']);
        }

        $code = random_int(111111, 999999);
        $hash = md5((string) $code);
        $expiry = time() + 7200; // 2 hours

        $stmt = mysqli_prepare($conn, "UPDATE Wo_Users SET email_code = ?, time_code_sent = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $hash, $expiry, $user['user_id']);
        mysqli_stmt_execute($stmt);

        // Dev mode: return code directly (no email service)
        respond(true, 'Reset code generated.', ['code' => (string) $code, 'user_id' => $user['user_id']]);
        break;

    case 'reset_password':
        if (!Wo_ValidateCsrf()) {
            respond(false, 'Invalid request token.');
        }

        $user_id  = intval($_POST['user_id'] ?? 0);
        $code     = trim($_POST['code'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($user_id < 1 || $code === '' || $password === '') {
            respond(false, 'Please fill in all fields.');
        }

        if (strlen($password) < 6) {
            respond(false, 'Password must be at least 6 characters.');
        }

        if ($password !== $confirm) {
            respond(false, 'Passwords do not match.');
        }

        // Verify code
        $hash = md5($code);
        $now  = time();
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM Wo_Users WHERE user_id = ? AND email_code = ? AND time_code_sent > ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "isi", $user_id, $hash, $now);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!mysqli_fetch_assoc($result)) {
            respond(false, 'Invalid or expired reset code.');
        }

        Wo_ResetPassword($conn, $user_id, $password);

        // Clear the code
        $stmt = mysqli_prepare($conn, "UPDATE Wo_Users SET email_code = '', time_code_sent = 0 WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        respond(true, 'Password reset successful. You can now log in.', ['location' => '?link1=welcome']);
        break;

    default:
        respond(false, 'Unknown action.');
}
