<?php
$page_title = 'Calendar';
include __DIR__ . '/includes/header.php';

// Figure out which week to show: from ?week=, else the current week
$week = isset($_GET['week']) ? intval($_GET['week']) : get_current_week($conn);
if ($week < 1) $week = 1;

// mysqli_real_escape_string isn't needed here since intval() already
// guarantees $week is a plain integer, safe to interpolate.
$sql = "SELECT * FROM calendar_events WHERE week = $week ORDER BY event_date ASC";
$result = mysqli_query($conn, $sql);

$days = [];
while ($row = mysqli_fetch_assoc($result)) {
    $days[] = $row;
}

// Work out how many weeks exist total, to bound Prev/Next nav
$range = mysqli_query($conn, "SELECT MIN(week) AS min_week, MAX(week) AS max_week FROM calendar_events");
$bounds = mysqli_fetch_assoc($range);
$min_week = intval($bounds['min_week'] ?? 1);
$max_week = intval($bounds['max_week'] ?? 1);
?>

<section class="page-intro page-intro-row">
    <div>
        <span class="eyebrow">Timeline</span>
        <h1>Week <?php echo $week; ?></h1>
    </div>
    <div class="week-nav">
        <?php if ($week > $min_week): ?>
            <a href="calendar.php?week=<?php echo $week - 1; ?>" class="btn btn-ghost">&larr; Prev</a>
        <?php endif; ?>
        <?php if ($week < $max_week): ?>
            <a href="calendar.php?week=<?php echo $week + 1; ?>" class="btn btn-ghost">Next &rarr;</a>
        <?php endif; ?>
    </div>
</section>

<?php if (empty($days)): ?>
    <div class="empty-state">
        <p>No events logged for week <?php echo $week; ?> yet.</p>
        <a href="add-event.php" class="btn btn-primary">Add an event</a>
    </div>
<?php else: ?>
<div class="timeline">
    <?php foreach ($days as $d): ?>
        <a href="event.php?id=<?php echo intval($d['id']); ?>" class="timeline-item <?php echo is_today($d['event_date']) ? 'is-today' : ''; ?>">
            <div class="timeline-marker <?php echo day_badge_class($d['day']); ?>">
                <span class="timeline-day"><?php echo clean($d['day']); ?></span>
                <span class="timeline-date"><?php echo format_date($d['event_date'], 'M j'); ?></span>
            </div>
            <div class="timeline-content">
                <h3><?php echo clean($d['title']); ?></h3>
                <p><?php echo clean(truncate($d['description'], 120)); ?></p>
                <?php if (is_today($d['event_date'])): ?><span class="badge-live">Today</span><?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>