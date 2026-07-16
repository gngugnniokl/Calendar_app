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
    $result = [
        'completed' => [],
        'today'     => [],
        'upcoming'  => [],
        'week'      => 1,
        'total_this_week' => 0,
        'done_this_week'  => 0,
    ];

    // Fetch events for this user OR programme-wide (user_id IS NULL)
    $stmt = mysqli_prepare($conn,
        "SELECT e.id, e.week, e.day, e.title, e.description, e.event_date, IF(c.id IS NOT NULL, 1, 0) AS is_completed
         FROM calendar_events e
         LEFT JOIN calendar_event_completions c ON e.id = c.event_id AND c.user_id = ?
         WHERE e.user_id = ? OR e.user_id IS NULL
         ORDER BY e.event_date ASC"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    // Determine current week from today's date
    $current_week = 1;
    $upcoming_count = 0;
    $found_today_week = false;

    if ($stmt && mysqli_stmt_execute($stmt)) {
        $res = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($res)) {
            $event_date = $row['event_date'];
            $is_completed = (int) $row['is_completed'];

            // Robust week tracking: always capture the week if the event is today
            if ($event_date === $today) {
                $current_week = (int) $row['week'];
                $found_today_week = true;
            }

            // Bucketing logic
            if ($is_completed === 1) {
                $result['completed'][] = $row;
            } elseif ($event_date <= $today) {
                // IMPROVEMENT: Overdue events (past date, not completed) 
                // fall into 'today' so they remain actionable instead of showing as 'upcoming'
                $result['today'][] = $row;
            } else {
                // Cap upcoming at 5
                if ($upcoming_count < 5) {
                    $result['upcoming'][] = $row;
                }
                $upcoming_count++;
            }
        }
    }

    if ($stmt) {
        mysqli_stmt_close($stmt);
    }

    // IMPROVEMENT: Smarter week inference if no event fell exactly on today
    if (!$found_today_week) {
        if (!empty($result['today'])) {
            $current_week = (int) $result['today'][0]['week'];
        } elseif (!empty($result['upcoming'])) {
            $current_week = (int) $result['upcoming'][0]['week'];
        } elseif (!empty($result['completed'])) {
            $last = end($result['completed']);
            $current_week = (int) $last['week'];
        }
    }

    $result['week'] = $current_week;

    // Count stats for current week
    $stmt2 = mysqli_prepare($conn,
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END) AS done
         FROM calendar_events e
         LEFT JOIN calendar_event_completions c ON e.id = c.event_id AND c.user_id = ?
         WHERE (e.user_id = ? OR e.user_id IS NULL)
           AND e.week = ?"
    );
    mysqli_stmt_bind_param($stmt2, 'iii', $user_id, $user_id, $current_week);
    mysqli_stmt_execute($stmt2);
    $stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    mysqli_stmt_close($stmt2);

    $result['total_this_week'] = (int) ($stats['total'] ?? 0);
    $result['done_this_week']  = (int) ($stats['done'] ?? 0);

    return $result;
}
