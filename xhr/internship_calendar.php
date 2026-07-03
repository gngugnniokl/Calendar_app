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
        $data = getRequestData();
        $errors = validateEvent($data);

        if (!empty($errors)) {
            respond(false, implode(' ', $errors));
        }

        // - Save event using helper function.
        $eventId = Wo_SaveInternshipCalendarEvent($conn, $data);

        // - Return JSON response.
        if ($eventId) {
            $data['id'] = $eventId;
            respond(true, 'Event saved successfully.', $data);
        } else {
            respond(false, 'Failed to save event.');
        }
        break;

    case 'update_event':
        // - Read updated data.
        $data = getRequestData();

        if ($data['id'] <= 0) {
            respond(false, 'Invalid event ID.');
        }

        // - Validate input.
        $errors = validateEvent($data);
        if (!empty($errors)) {
            respond(false, implode(' ', $errors));
        }

        // - Update event.
        $updated = Wo_UpdateInternshipCalendarEvent($conn, $data);

        // - Return JSON response.
        if ($updated) {
            respond(true, 'Event updated successfully.', $data);
        } else {
            respond(false, 'Failed to update event or no changes made.');
        }
        break;

    case 'delete_event':
        // - Read event ID.
        $data = getRequestData();

        if ($data['id'] <= 0) {
            respond(false, 'Invalid event ID.');
        }

        // - Delete event.
        $deleted = Wo_DeleteInternshipCalendarEvent($conn, $data['id']);

        // - Return JSON response.
        if ($deleted) {
            respond(true, 'Event deleted successfully.');
        } else {
            respond(false, 'Failed to delete event.');
        }
        break;

    case 'search_events':

        $data = getRequestData();

        $events = Wo_GetInternshipCalendarEvents($conn, [
            'search' => $data['query']
        ]);

        respond(
            true,
            'Search completed.',
            $events
        );

        break;

    case 'filter_events':

        $data = $getRequestData();

        $events = Wo_GetInternshipCalendarEvents($conn, [
            'week' => $data['week']
        ]);

        respond(
            true,
            'Events filtered successfully.',
            $events
        );

        break;

    case 'toggle_completion':
        // - Read event ID.
        $data = getRequestData();
        
        if ($data['id'] <= 0) {
            respond(false, 'Invalid event ID.');
        }

        // - Toggle completion status.
        $updatedEvent = Wo_ToggleInternshipEventCompletion($conn, $data['id']);

        // - Return updated event.
        if ($updatedEvent) {
            respond(true, 'Completion status toggled successfully.', $updatedEvent);
        } else {
            respond(false, 'Failed to toggle completion status.');
        }
        break;

    default:
        respond(false, 'Invalid action.');

}