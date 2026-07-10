<?php

declare(strict_types=1);

/**
 * Overview page: shows all 8 weeks grouped, each linking into the
 * single-week view (internship_calendar.php). No SQL, no HTML.
 */

$page_title = 'Home';

$wo['page_title']   = $page_title;
$wo['weeks']        = Wo_GetInternshipCalendarWeeks($conn);
$wo['current_week'] = Wo_GetCurrentInternshipWeek($conn);

$wo['content'] = Wo_LoadPage('internship_calendar/home');