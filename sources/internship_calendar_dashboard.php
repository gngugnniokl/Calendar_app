<?php
$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';

// Pull everything once, then derive every stat from this one result set
// (count(), array_filter(), and the date helpers do the rest — no need
// for six separate queries).
$sql = "SELECT * FROM calendar_events ORDER BY week ASC, event_date ASC";
$result = mysqli_query($conn, $sql);

$events = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
}

$today = date('Y-m-d');

// array_map() pulls just the week numbers, array_unique() + count() gives distinct weeks
$week_numbers = array_unique(array_map(function ($e) { return intval($e['week']); }, $events));
$total_weeks = count($week_numbers);

// array_unique() on the event_date column gives distinct logged days
$unique_dates = array_unique(array_map(function ($e) { return $e['event_date']; }, $events));
$total_days = count($unique_dates);

$total_events = count($events);
$current_week = get_current_week($conn);

// array_filter() splits events into completed (date has passed) vs upcoming
$completed = array_filter($events, function ($e) use ($today) {
    return $e['event_date'] < $today;
});
$upcoming = array_filter($events, function ($e) use ($today) {
    return $e['event_date'] >= $today;
});

$completed_count = count($completed);
$upcoming_count = count($upcoming);

// Program is 8 weeks total per the brief — used for the progress bar
$program_length = 8;
$progress_pct = $program_length > 0 ? min(100, round(($current_week / $program_length) * 100)) : 0;

$stats = [
    ['label' => 'Total Weeks',      'value' => $total_weeks,      'note' => 'of ' . $program_length . ' planned'],
    ['label' => 'Total Days',       'value' => $total_days,       'note' => 'logged so far'],
    ['label' => 'Total Events',     'value' => $total_events,     'note' => 'records in the log'],
    ['label' => 'Current Week',     'value' => sprintf('W%02d', $current_week), 'note' => 'based on today\'s date'],
    ['label' => 'Completed Events', 'value' => $completed_count,  'note' => 'already in the past'],
    ['label' => 'Upcoming Events',  'value' => $upcoming_count,   'note' => 'still ahead'],
];
?>

<section class="page-intro">
    <span class="eyebrow">Program Health</span>
    <h1>Dashboard</h1>
    <p>A snapshot of the whole 8-week internship, pulled straight from the database.</p>
</section>

<div class="progress-block">
    <div class="progress-labels">
        <span>Program Progress</span>
        <span><?php echo clean($progress_pct); ?>%</span>
    </div>
    <div class="progress-track">
        <div class="progress-fill" style="width: <?php echo intval($progress_pct); ?>%;"></div>
    </div>
</div>

<div class="stat-grid">
    <?php foreach ($stats as $s): ?>
        <div class="stat-card">
            <span class="stat-value"><?php echo clean($s['value']); ?></span>
            <span class="stat-label"><?php echo clean($s['label']); ?></span>
            <span class="stat-note"><?php echo clean($s['note']); ?></span>
        </div>
    <?php endforeach; ?>
</div>

<section class="dashboard-split">
    <div class="dashboard-col">
        <h2>Recently Completed</h2>
        <?php if (empty($completed)): ?>
            <p class="dim-note">Nothing logged as completed yet.</p>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach (array_slice(array_reverse($completed), 0, 5) as $e): ?>
                    <li>
                        <a href="event.php?id=<?php echo intval($e['id']); ?>">
                            <span class="week-tab-sm">W<?php echo sprintf('%02d', $e['week']); ?></span>
                            <span><?php echo clean($e['title']); ?></span>
                            <time><?php echo format_date($e['event_date'], 'M j'); ?></time>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="dashboard-col">
        <h2>Up Next</h2>
        <?php if (empty($upcoming)): ?>
            <p class="dim-note">No upcoming events on the calendar.</p>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach (array_slice($upcoming, 0, 5) as $e): ?>
                    <li>
                        <a href="event.php?id=<?php echo intval($e['id']); ?>">
                            <span class="week-tab-sm">W<?php echo sprintf('%02d', $e['week']); ?></span>
                            <span><?php echo clean($e['title']); ?></span>
                            <time><?php echo format_date($e['event_date'], 'M j'); ?></time>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>