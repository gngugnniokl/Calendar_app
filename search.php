<?php
$page_title = 'Search';
include __DIR__ . '/includes/header.php';

$term = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

if ($term !== '') {
    // mysqli_real_escape_string() on the raw term, then wrap with % for LIKE
    $safe_term = mysqli_real_escape_string($conn, $term);
    $sql = "SELECT * FROM calendar_events
            WHERE title LIKE '%$safe_term%'
            ORDER BY week ASC, event_date ASC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
    }
}
?>

<section class="page-intro">
    <span class="eyebrow">Find an Entry</span>
    <h1>Search</h1>
</section>

<form method="GET" class="search-form">
    <input type="text" name="q" placeholder="Search event titles…" value="<?php echo clean($term); ?>" autofocus>
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<?php if ($term !== ''): ?>
    <p class="search-meta"><?php echo count($results); ?> result<?php echo count($results) === 1 ? '' : 's'; ?> for &ldquo;<?php echo clean($term); ?>&rdquo;</p>
<?php endif; ?>

<?php if ($term !== '' && empty($results)): ?>
    <div class="empty-state">
        <p>No events match that search.</p>
    </div>
<?php elseif (!empty($results)): ?>
<div class="search-results">
    <?php foreach ($results as $r): ?>
        <a href="event.php?id=<?php echo intval($r['id']); ?>" class="search-result-item">
            <span class="week-tab-sm">W<?php echo sprintf('%02d', $r['week']); ?></span>
            <div>
                <h3><?php echo clean($r['title']); ?></h3>
                <p><?php echo clean($r['day']); ?> &middot; <?php echo format_date($r['event_date']); ?></p>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>