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

    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $name = getenv('DB_NAME') ?: 'internship_calendar';

    // Don't let mysqli_connect() throw a warning with credentials in it.
    $conn = @mysqli_connect($host, $user, $pass, $name);

    if (!$conn) {
        // Log the real reason for developers, but never echo it to the page.
        error_log('DB connection failed: ' . mysqli_connect_error());
        throw new RuntimeException('Database connection failed.');
    }

    mysqli_set_charset($conn, 'utf8mb4');

    return $conn;
}

// Backward-compatible global — existing code across the app (and Dev B's
// xhr/internship_calendar.php) still does `require database.php; use $conn;`
$conn = get_db_connection();