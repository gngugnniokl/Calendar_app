<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    redirect('index.php');
}

// Grab the week first so we know where to send the user back to
$check = mysqli_query($conn, "SELECT week FROM calendar_events WHERE id = $id LIMIT 1");
$row = $check ? mysqli_fetch_assoc($check) : null;

if (!$row) {
    flash_message('That event no longer exists.', 'error');
    redirect('index.php');
}

$week = intval($row['week']);
$sql = "DELETE FROM calendar_events WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    flash_message('Event deleted.');
} else {
    flash_message('Could not delete event: ' . mysqli_error($conn), 'error');
}

redirect('calendar.php?week=' . $week);