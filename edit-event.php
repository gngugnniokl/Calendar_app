<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) redirect('index.php');

// Fetch the existing row first, so the form loads pre-filled
$existing_result = mysqli_query($conn, "SELECT * FROM calendar_events WHERE id = $id LIMIT 1");
$existing = ($existing_result && mysqli_num_rows($existing_result) > 0) ? mysqli_fetch_assoc($existing_result) : null;

if (!$existing) redirect('index.php');

$errors = [];
$form = $existing; // pre-fill from the DB row

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['week'] = trim($_POST['week'] ?? '');
    $form['day'] = trim($_POST['day'] ?? '');
    $form['title'] = trim($_POST['title'] ?? '');
    $form['description'] = trim($_POST['description'] ?? '');
    $form['success_criteria'] = trim($_POST['success_criteria'] ?? '');
    $form['traps'] = trim($_POST['traps'] ?? '');
    $form['event_date'] = trim($_POST['event_date'] ?? '');

    if (empty($form['week']) || !is_numeric($form['week'])) $errors[] = 'Week must be a number.';
    if (empty($form['day'])) $errors[] = 'Day is required.';
    if (empty($form['title'])) $errors[] = 'Title is required.';
    if (empty($form['event_date'])) $errors[] = 'Date is required.';

    if (empty($errors)) {
        $week = intval($form['week']);
        $day = mysqli_real_escape_string($conn, $form['day']);
        $title = mysqli_real_escape_string($conn, $form['title']);
        $description = mysqli_real_escape_string($conn, $form['description']);
        $success_criteria = mysqli_real_escape_string($conn, $form['success_criteria']);
        $traps = mysqli_real_escape_string($conn, $form['traps']);
        $event_date = mysqli_real_escape_string($conn, $form['event_date']);

        $sql = "UPDATE calendar_events SET
                    week = $week,
                    day = '$day',
                    title = '$title',
                    description = '$description',
                    success_criteria = '$success_criteria',
                    traps = '$traps',
                    event_date = '$event_date'
                WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            flash_message('Event updated.');
            redirect('event.php?id=' . $id);
        } else {
            $errors[] = 'Database error: ' . mysqli_error($conn);
        }
    }
}

$page_title = 'Edit: ' . $existing['title'];
include __DIR__ . '/includes/header.php';
?>

<section class="page-intro">
    <span class="eyebrow">Editing</span>
    <h1>Edit Event</h1>
</section>

<?php if (!empty($errors)): ?>
<div class="form-errors">
    <ul>
        <?php foreach ($errors as $e): ?><li><?php echo clean($e); ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" class="event-form" novalidate>
    <div class="form-row">
        <label for="week">Week</label>
        <input type="number" min="1" id="week" name="week" value="<?php echo clean($form['week']); ?>" required>
    </div>
    <div class="form-row">
        <label for="day">Day</label>
        <select id="day" name="day" required>
            <option value="">Select a day</option>
            <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                <option value="<?php echo $d; ?>" <?php echo ($form['day'] === $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-row">
        <label for="event_date">Date</label>
        <input type="date" id="event_date" name="event_date" value="<?php echo clean($form['event_date']); ?>" required>
    </div>
    <div class="form-row form-row-full">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?php echo clean($form['title']); ?>" required>
    </div>
    <div class="form-row form-row-full">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?php echo clean($form['description']); ?></textarea>
    </div>
    <div class="form-row form-row-full">
        <label for="success_criteria">Success Criteria</label>
        <textarea id="success_criteria" name="success_criteria" rows="3"><?php echo clean($form['success_criteria']); ?></textarea>
    </div>
    <div class="form-row form-row-full">
        <label for="traps">Watch Outs / Traps</label>
        <textarea id="traps" name="traps" rows="3"><?php echo clean($form['traps']); ?></textarea>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="event.php?id=<?php echo $id; ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>