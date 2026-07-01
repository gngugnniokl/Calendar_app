<?php
// require_once() the connection/helpers directly (not header.php yet) so we
// can still call redirect()/header() below, before any HTML is echoed.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    redirect('index.php');
}

$sql = "SELECT * FROM calendar_events WHERE id = $id LIMIT 1";
$result = mysqli_query($conn, $sql);

$event = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
$page_title = $event ? $event['title'] : 'Event Not Found';

include __DIR__ . '/includes/header.php';
?>

<?php if (!$event): ?>
    <div class="empty-state">
        <p>That event couldn't be found. It may have been deleted.</p>
        <a href="index.php" class="btn btn-primary">Back to Home</a>
    </div>
<?php else: ?>

<article class="event-detail">
    <div class="event-detail-header">
        <span class="week-tab"><?php echo clean($event['day']); ?> &middot; Week <?php echo intval($event['week']); ?></span>
        <time><?php echo format_date($event['event_date'], 'l, F j, Y'); ?></time>
    </div>

    <h1><?php echo clean($event['title']); ?></h1>

    <?php if (!empty($event['description'])): ?>
    <section class="event-block">
        <h2>Description</h2>
        <p><?php echo nl2br(clean($event['description'])); ?></p>
    </section>
    <?php endif; ?>

    <?php if (!empty($event['success_criteria'])): ?>
    <section class="event-block event-block-success">
        <h2>Success Criteria</h2>
        <p><?php echo nl2br(clean($event['success_criteria'])); ?></p>
    </section>
    <?php endif; ?>

    <?php if (!empty($event['traps'])): ?>
    <section class="event-block event-block-caution">
        <h2>Watch Outs / Traps</h2>
        <p><?php echo nl2br(clean($event['traps'])); ?></p>
    </section>
    <?php endif; ?>

    <div class="event-actions">
        <a href="edit-event.php?id=<?php echo intval($event['id']); ?>" class="btn btn-ghost">Edit</a>
        <a href="delete-event.php?id=<?php echo intval($event['id']); ?>" class="btn btn-danger js-confirm-delete">Delete</a>
        <a href="calendar.php?week=<?php echo intval($event['week']); ?>" class="btn btn-ghost">&larr; Back to Week <?php echo intval($event['week']); ?></a>
    </div>
</article>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>