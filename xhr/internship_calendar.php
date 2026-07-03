<?php

require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
header('Content-Type: application/json');

function respond($success, $message = '', $data = null)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
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
        $errors[] = 'Week must be a number.';
    }

    if (empty($data['day'])) {
        $errors[] = 'Day is required.';
    }

    if (empty($data['title'])) {
        $errors[] = 'Title is required.';
    }

    if (empty($data['event_date'])) {
        $errors[] = 'Date is required.';
    }

    return $errors;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'save_event':
        // - Read and validate request data.
        // - Save event using helper function.
        // - Return JSON response.
        break;

    case 'update_event':
        // - Read updated data.
        // - Validate input.
        // - Update event.
        // - Return JSON response.
        break;

    case 'delete_event':
        // - Read event ID.
        // - Delete event.
        // - Return JSON response.
        break;

    case 'search_events':
        // - Read search term.
        // - Search events.
        // - Return events that match
        break;

    case 'filter_events':
        // - Read filter options.
        // - Filter events.
        // - Return filtered events.
        break;

    case 'toggle_completion':
        // - Read event ID.
        // - Toggle completion status.
        // - Return updated event.
        break;

    default:
        respond(false, 'Invalid action.');

}