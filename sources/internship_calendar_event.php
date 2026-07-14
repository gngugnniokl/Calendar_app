<?php

declare(strict_types=1);

/**
 * Task A5: validate input → fetch data → hand off to event.phtml.
 * No inline HTML, no raw SQL.
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    redirect('index.php?link1=internship_calendar');
}

$event = Wo_GetInternshipCalendarEventById($conn, $id);

if (!$event) {
    redirect('index.php?link1=internship_calendar');
}

$wo['page_title'] = $event['title'];
$wo['event']      = $event;

$wo['content'] = Wo_LoadPage('internship_calendar/event');