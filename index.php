<?php
$page_title = 'Home';
include __DIR__ . '/includes/header.php';

// Pull every event, ordered so days fall in date order within each week
$sql = "SELECT * FROM calendar_events ORDER BY week ASC, event_date ASC";
$result = mysqli_query($conn, $sql);

// Group rows by week number into an associative array: [1 => [...days], 2 => [...]]
$weeks = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $weeks[$row['week']][] = $row;
    }
}

$current_week = get_current_week($conn);
?>

<section class="page-intro">
    <span class="eyebrow">8-Week Program</span>
    <h1>The Internship Log</h1>
    <p>Every week, every day, every checkpoint &mdash; in one place instead of a spreadsheet.</p>
</section>

<?php if (empty($weeks)): ?>
    <div class="empty-state">
        <p>No events logged yet.</p>
        <a href="add-event.php" class="btn btn-primary">Add the first event</a>
    </div>
<?php else: ?>
<div class="week-grid">
    <?php foreach ($weeks as $week_num => $days): ?>
        <?php
            $is_current = ($week_num == $current_week);
            // implode() joins the day names for this week into one preview line
            $day_names = array_map(function($d) { return $d['day']; }, $days);
            $day_list = implode(', ', $day_names);
        ?>
        <a href="calendar.php?week=<?php echo intval($week_num); ?>" class="week-card <?php echo $is_current ? 'is-current' : ''; ?>">
            <div class="week-card-top">
                <span class="week-tab">W<?php echo sprintf('%02d', $week_num); ?></span>
                <?php if ($is_current): ?><span class="badge-live">Current</span><?php endif; ?>
            </div>
            <h2><?php echo clean($days[0]['title']); ?></h2>
            <p class="week-days"><?php echo clean($day_list); ?></p>
            <p class="week-count"><?php echo count($days); ?> day<?php echo count($days) === 1 ? '' : 's'; ?> logged</p>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>