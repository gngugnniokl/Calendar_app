<?php

/*
ALTER TABLE calendar_events
ADD completed TINYINT(1) NOT NULL DEFAULT 0;
*/

require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
header('Content-Type: application/json');

function respond($success, $message = '', $data = null)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data !== null ? $data : new stdClass()
    ]);
    exit;
}

/**
 * Returns cleaned POST data.
 *
 * @return array
 */
function getRequestData()
{
    return [
        'id' => intval($_POST['id'] ?? 0),
        'week' => intval($_POST['week'] ?? 0),
        'day' => trim($_POST['day'] ?? ''),
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'success_criteria' => trim($_POST['success_criteria'] ?? ''),
        'traps' => trim($_POST['traps'] ?? ''),
        'event_date' => trim($_POST['event_date'] ?? ''),
        'query' => trim($_POST['query'] ?? ''),
        'date_from' => trim($_POST['date_from'] ?? ''),
        'date_to' => trim($_POST['date_to'] ?? '')
    ];
}

/**
 * Validates event data.
 *
 * @param array $data
 * @return array
 */
function validateEvent(array $data)
{
    $errors = [];

    if ($data['week'] <= 0) {
        $errors[] = 'Week must be a number greater than 0.';
    }

    if (empty($data['day'])) {
        $errors[] = 'Day is required.';
    }

    if (empty($data['title'])) {
        $errors[] = 'Title is required.';
    }
    
    if (empty($data['description'])) {
        $errors[] = 'Description is required.';
    }
    
    if (empty($data['success_criteria'])) {
        $errors[] = 'Success criteria is required.';
    }
    
    if (empty($data['traps'])) {
        $errors[] = 'Traps is required.';
    }

    if (empty($data['event_date'])) {
        $errors[] = 'Date is required.';
    } else {
        $dateObj = DateTime::createFromFormat('Y-m-d', $data['event_date']);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $data['event_date']) {
            $errors[] = 'Invalid date format.';
        }
    }

    return $errors;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'save_event':
        $data = getRequestData();
        $errors = validateEvent($data);

        if (!empty($errors)) {
            respond(false, 'Validation error: ' . implode(' ', $errors));
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO calendar_events (week, day, title, description, success_criteria, traps, event_date, completed) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        if (!$stmt) {
            respond(false, 'Database error');
        }
        
        mysqli_stmt_bind_param($stmt, 'issssss', $data['week'], $data['day'], $data['title'], $data['description'], $data['success_criteria'], $data['traps'], $data['event_date']);
        
        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            respond(true, 'Event saved.', ['id' => $newId]);
        } else {
            mysqli_stmt_close($stmt);
            respond(false, 'Failed to save event');
        }
        break;

    case 'update_event':
        $data = getRequestData();

        if ($data['id'] <= 0) {
            respond(false, 'Validation error: Invalid event ID.');
        }

        $errors = validateEvent($data);
        if (!empty($errors)) {
            respond(false, 'Validation error: ' . implode(' ', $errors));
        }
        
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM calendar_events WHERE id = ?");
        mysqli_stmt_bind_param($checkStmt, 'i', $data['id']);
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        if (mysqli_num_rows($res) === 0) {
            mysqli_stmt_close($checkStmt);
            respond(false, 'Validation error: Event not found');
        }
        mysqli_stmt_close($checkStmt);

        $stmt = mysqli_prepare($conn, "UPDATE calendar_events SET week = ?, day = ?, title = ?, description = ?, success_criteria = ?, traps = ?, event_date = ? WHERE id = ?");
        if (!$stmt) {
            respond(false, 'Database error');
        }
        
        mysqli_stmt_bind_param($stmt, 'issssssi', $data['week'], $data['day'], $data['title'], $data['description'], $data['success_criteria'], $data['traps'], $data['event_date'], $data['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            respond(true, 'Event updated.', $data);
        } else {
            mysqli_stmt_close($stmt);
            respond(false, 'Failed to update event');
        }
        break;

    case 'delete_event':
        $data = getRequestData();

        if ($data['id'] <= 0) {
            respond(false, 'Validation error: Invalid event ID.');
        }
        
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM calendar_events WHERE id = ?");
        mysqli_stmt_bind_param($checkStmt, 'i', $data['id']);
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        if (mysqli_num_rows($res) === 0) {
            mysqli_stmt_close($checkStmt);
            respond(false, 'Validation error: Event not found');
        }
        mysqli_stmt_close($checkStmt);

        $stmt = mysqli_prepare($conn, "DELETE FROM calendar_events WHERE id = ?");
        if (!$stmt) {
            respond(false, 'Database error');
        }
        mysqli_stmt_bind_param($stmt, 'i', $data['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            respond(true, 'Event deleted successfully.');
        } else {
            mysqli_stmt_close($stmt);
            respond(false, 'Failed to delete event');
        }
        break;

    case 'search_events':
        $data = getRequestData();
        $query = $data['query'];
        $results = [];

        if ($query === '') {
            $stmt = mysqli_prepare($conn, "SELECT * FROM calendar_events ORDER BY event_date ASC");
            if ($stmt) {
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) {
                    $results[] = $row;
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $stmt = mysqli_prepare($conn, "SELECT * FROM calendar_events WHERE title LIKE ? OR description LIKE ? OR success_criteria LIKE ? OR traps LIKE ? ORDER BY event_date ASC");
            if ($stmt) {
                $likeQuery = "%" . $query . "%";
                mysqli_stmt_bind_param($stmt, 'ssss', $likeQuery, $likeQuery, $likeQuery, $likeQuery);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) {
                    $results[] = $row;
                }
                mysqli_stmt_close($stmt);
            }
        }

        respond(true, 'Results found', $results);
        break;

    case 'filter_events':
        $data = getRequestData();
        $conditions = [];
        $params = [];
        $types = "";

        if ($data['week'] > 0) {
            $conditions[] = "week = ?";
            $params[] = $data['week'];
            $types .= "i";
        }
        if ($data['day'] !== '') {
            $conditions[] = "day = ?";
            $params[] = $data['day'];
            $types .= "s";
        }
        if ($data['date_from'] !== '') {
            $conditions[] = "event_date >= ?";
            $params[] = $data['date_from'];
            $types .= "s";
        }
        if ($data['date_to'] !== '') {
            $conditions[] = "event_date <= ?";
            $params[] = $data['date_to'];
            $types .= "s";
        }

        $sql = "SELECT * FROM calendar_events";
        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY event_date ASC";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            if (!empty($params)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $results = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $results[] = $row;
            }
            mysqli_stmt_close($stmt);
            respond(true, 'Events filtered.', $results);
        } else {
            respond(false, 'Database error');
        }
        break;

    case 'toggle_completion':
        $data = getRequestData();
        
        if ($data['id'] <= 0) {
            respond(false, 'Validation error: Invalid event ID.');
        }

        $checkStmt = mysqli_prepare($conn, "SELECT completed FROM calendar_events WHERE id = ?");
        if (!$checkStmt) {
            respond(false, 'Database error');
        }
        mysqli_stmt_bind_param($checkStmt, 'i', $data['id']);
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        if (mysqli_num_rows($res) === 0) {
            mysqli_stmt_close($checkStmt);
            respond(false, 'Validation error: Event not found');
        }
        
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($checkStmt);
        
        $newStatus = $row['completed'] == 1 ? 0 : 1;

        $stmt = mysqli_prepare($conn, "UPDATE calendar_events SET completed = ? WHERE id = ?");
        if (!$stmt) {
            respond(false, 'Database error');
        }
        mysqli_stmt_bind_param($stmt, 'ii', $newStatus, $data['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            respond(true, 'Completion updated', ['completed' => $newStatus]);
        } else {
            mysqli_stmt_close($stmt);
            respond(false, 'Failed to toggle completion.');
        }
        break;

    default:
        respond(false, 'Invalid action.');

}