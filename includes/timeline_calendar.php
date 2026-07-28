<?php
declare(strict_types=1);

/**
 * includes/timeline_calendar.php
 * Helper functions for the Calendar Timeline Strip on user profiles.
 */

/**
 * Wo_GetUserTimelineEvents() — fetches a user's calendar events
 * split into completed, today, and upcoming.
 *
 * @param mysqli $conn
 * @param int $user_id
 * @return array [
 *     'completed' => [...],
 *     'today'     => [...],
 *     'upcoming'  => [...],
 *     'week'      => int,         // current week number
 *     'total_this_week' => int,   // total events in current week
 *     'done_this_week'  => int    // completed events in current week
 * ]
 */
function Wo_GetUserTimelineEvents(mysqli $conn, int $user_id): array
{
    $today = date('Y-m-d');
    $current_week = function_exists('Wo_GetCurrentInternshipWeek') ? Wo_GetCurrentInternshipWeek($conn) : 1;
    
    $result = [
        'completed' => [],
        'today'     => [],
        'upcoming'  => [],
        'week'      => $current_week,
        'total_this_week' => 0,
        'done_this_week'  => 0,
    ];

    // Fetch events for this user OR programme-wide (user_id IS NULL), but ONLY for the current week
    $stmt = mysqli_prepare($conn,
        "SELECT e.id, e.week, e.day, e.title, e.description, e.event_date, IF(c.id IS NOT NULL, 1, 0) AS is_completed
         FROM calendar_events e
         LEFT JOIN calendar_event_completions c ON e.id = c.event_id AND c.user_id = ?
         WHERE (e.user_id = ? OR e.user_id IS NULL) AND e.week = ?
         ORDER BY e.event_date ASC"
    );
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $user_id, $current_week);

    if ($stmt && mysqli_stmt_execute($stmt)) {
        $res = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($res)) {
            $event_date = $row['event_date'];
            $is_completed = (int) $row['is_completed'];

            $result['total_this_week']++;
            if ($is_completed === 1) {
                $result['done_this_week']++;
                $result['completed'][] = $row;
            } else {
                if ($event_date <= $today) {
                    $result['today'][] = $row;
                } else {
                    $result['upcoming'][] = $row;
                }
            }
        }
        mysqli_stmt_close($stmt);
    }

    return $result;
}
