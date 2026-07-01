<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

echo "Connected successfully to '$db_name'!<br><br>";

$result = mysqli_query($conn, "SELECT * FROM calendar_events");

if ($result && mysqli_num_rows($result) > 0) {
    echo "Found " . mysqli_num_rows($result) . " event(s):<br><br>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Week {$row['week']} - {$row['day']}: {$row['title']}<br>";
    }
} else {
    echo "No events found yet.";
}

mysqli_close($conn);
?>