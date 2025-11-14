<?php
$pageTitle = 'Manage Performances';
$currentPage = 'performances';

include 'includes/header.php';

$db = getDB();
$message = '';
$messageType = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM performances WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Performance deleted successfully';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error deleting performance: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $festival_id = $_POST['festival_id'] ?? null;
    $artist_id = $_POST['artist_id'] ?? null;
    $performance_date = $_POST['performance_date'] ?? '';
    $performance_time = $_POST['performance_time'] ?? '';
    $stage = $_POST['stage'] ?? '';
    $is_headliner = isset($_POST['is_headliner']) ? 1 : 0;
    $supporting_act = isset($_POST['supporting_act']) ? 1 : 0;
    $sort_order = $_POST['sort_order'] ?? 0;

    try {
        if ($id) {
            // UPDATE
            $stmt = $db->prepare("
                UPDATE performances SET
                    festival_id = ?, artist_id = ?, performance_date = ?, performance_time = ?,
                    stage = ?, is_headliner = ?, supporting_act = ?, sort_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$festival_id, $artist_id, $performance_date, $performance_time, $stage, $is_headliner, $supporting_act, $sort_order, $id]);
            $message = 'Performance updated successfully';
        } else {
            // INSERT
            $stmt = $db->prepare("
                INSERT INTO performances (festival_id, artist_id, performance_date, performance_time, stage, is_headliner, supporting_act, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$festival_id, $artist_id, $performance_date, $performance_time, $stage, $is_headliner, $supporting_act, $sort_order]);
            $message = 'Performance added successfully';
        }
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error saving performance: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get performance for editing
$editPerformance = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM performances WHERE id = ?");
    $stmt->execute([$id]);
    $editPerformance = $stmt->fetch();
}

// Get all performances with artist and festival names
$performances = $db->query("
    SELECT p.*, a.name as artist_name, f.name as festival_name
    FROM performances p
    JOIN artists a ON p.artist_id = a.id
    JOIN festivals f ON p.festival_id = f.id
    ORDER BY p.performance_date DESC, p.sort_order ASC
")->fetchAll();

// Get artists and festivals for dropdowns
$artists = $db->query("SELECT id, name FROM artists ORDER BY name ASC")->fetchAll();
$festivals = $db->query("SELECT id, name, year FROM festivals ORDER BY start_date DESC")->fetchAll();
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['add']) || $editPerformance): ?>
    <div class="form-card">
        <h2>
            
            <?php echo $editPerformance ? 'Edit' : 'Add'; ?> Performance
        </h2>

        <form method="POST" class="admin-form">
            <?php if ($editPerformance): ?>
                <input type="hidden" name="id" value="<?php echo $editPerformance['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="festival_id">Festival *</label>
                    <select id="festival_id" name="festival_id" required>
                        <option value="">Select Festival</option>
                        <?php foreach ($festivals as $festival): ?>
                            <option value="<?php echo $festival['id']; ?>"
                                <?php echo ($editPerformance && $editPerformance['festival_id'] == $festival['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($festival['name'] . ' ' . $festival['year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="artist_id">Artist *</label>
                    <select id="artist_id" name="artist_id" required>
                        <option value="">Select Artist</option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?php echo $artist['id']; ?>"
                                <?php echo ($editPerformance && $editPerformance['artist_id'] == $artist['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($artist['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="performance_date">Date *</label>
                    <input type="date" id="performance_date" name="performance_date" required
                           value="<?php echo $editPerformance['performance_date'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label for="performance_time">Time</label>
                    <input type="time" id="performance_time" name="performance_time"
                           value="<?php echo $editPerformance['performance_time'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label for="stage">Stage</label>
                    <input type="text" id="stage" name="stage"
                           value="<?php echo htmlspecialchars($editPerformance['stage'] ?? ''); ?>"
                           placeholder="Main Stage">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order"
                           value="<?php echo $editPerformance['sort_order'] ?? 0; ?>"
                           min="0">
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_headliner"
                            <?php echo ($editPerformance && $editPerformance['is_headliner']) ? 'checked' : ''; ?>>
                        <span>Headliner / Main Act</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="supporting_act"
                            <?php echo ($editPerformance && $editPerformance['supporting_act']) ? 'checked' : ''; ?>>
                        <span>Supporting Act</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $editPerformance ? 'Update' : 'Add'; ?> Performance
                </button>
                <a href="<?php echo admin_url('performances.php'); ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="page-actions">
        <a href="<?php echo admin_url('performances.php?add=1'); ?>" class="btn btn-primary">
            Add New Performance
        </a>
    </div>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Artist</th>
                    <th>Festival</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($performances as $perf): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($perf['performance_date'])); ?></td>
                        <td><?php echo $perf['performance_time'] ? date('g:i A', strtotime($perf['performance_time'])) : '-'; ?></td>
                        <td><strong><?php echo htmlspecialchars($perf['artist_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($perf['festival_name']); ?></td>
                        <td>
                            <?php if ($perf['is_headliner']): ?>
                                <span class="badge badge-primary">Headliner</span>
                            <?php elseif ($perf['supporting_act']): ?>
                                <span class="badge badge-secondary">Support</span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="<?php echo admin_url('performances.php?edit=' . $perf['id']); ?>" class="btn-icon" title="Edit">
                                <i class="las la-edit"></i>
                            </a>
                            <a href="<?php echo admin_url('performances.php?delete=' . $perf['id']); ?>"
                               class="btn-icon btn-danger"
                               title="Delete"
                               onclick="return confirm('Are you sure you want to delete this performance?');">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
