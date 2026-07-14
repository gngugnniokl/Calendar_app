<?php
/**
 * Database connection.
 *
 * Task A1: credentials moved out of source, connection wrapped in a
 * function so it can be reused/tested, errors no longer leak details
 * to the browser, charset is set inside the wrapper.
 */

/**
 * get_db_connection() — returns a shared mysqli connection.
 * Reads credentials from environment variables when available and
 * falls back to the previous local defaults so nothing breaks on a
 * dev machine that hasn't set env vars yet.
 */
function get_db_connection(): mysqli
{
    static $conn = null;

    if ($conn !== null) {
        return $conn;
    }

    // Load .env.local into environment if it exists
    $envFile = __DIR__ . '/../.env.local';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            $value = trim($value, " \t\n\r\0\x0B'\"");
            putenv(trim($key) . '=' . $value);
        }
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $name = getenv('DB_NAME') ?: 'internship_calendar';

    // Try configured credentials first, then common local fallback.
    $attempts = [
        ['host' => $host, 'user' => $user, 'pass' => $pass, 'name' => $name],
    ];

    if ($user !== 'root' || $pass !== '') {
        $attempts[] = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'name' => $name];
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $lastError = 'Unknown connection error.';

    foreach ($attempts as $attempt) {
        $conn = @mysqli_connect($attempt['host'], $attempt['user'], $attempt['pass'], $attempt['name']);
        if ($conn) {
            mysqli_set_charset($conn, 'utf8mb4');
            return $conn;
        }
        $lastError = mysqli_connect_error();
    }

    // Log the real reason for developers, but never echo it to the page.
    error_log('DB connection failed: ' . $lastError);
    throw new RuntimeException('Database connection failed.');
}

// Backward-compatible global — existing code across the app (and Dev B's
// xhr/internship_calendar.php) still does `require database.php; use $conn;`
$conn = get_db_connection();