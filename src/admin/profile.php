<?php
$pageTitle = 'My Profile';
$currentPage = 'profile';

include 'includes/header.php';

$db = getDB();
$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'All fields are required';
        $messageType = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'New passwords do not match';
        $messageType = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'New password must be at least 6 characters';
        $messageType = 'error';
    } else {
        // Get current admin credentials
        try {
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$_SESSION['admin_username']]);
            $admin = $stmt->fetch();

            if (!$admin) {
                $message = 'User not found';
                $messageType = 'error';
            } elseif (!password_verify($current_password, $admin['password'])) {
                $message = 'Current password is incorrect';
                $messageType = 'error';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $admin['id']]);

                $message = 'Password changed successfully';
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="las la-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <h2>
        <i class="las la-user-circle"></i>
        Profile Information
    </h2>

    <div class="profile-info">
        <div class="info-row">
            <label>Username:</label>
            <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></span>
        </div>
    </div>
</div>

<div class="form-card">
    <h2>
        <i class="las la-key"></i>
        Change Password
    </h2>

    <form method="POST" class="admin-form">
        <div class="form-group">
            <label for="current_password">Current Password *</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password *</label>
            <input type="password" id="new_password" name="new_password" required minlength="6">
            <small>Must be at least 6 characters</small>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="las la-save"></i>
                Change Password
            </button>
        </div>
    </form>
</div>

<style>
.profile-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-row {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.info-row:last-child {
    border-bottom: none;
}

.info-row label {
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    width: 150px;
    text-transform: none;
}

.info-row span {
    color: white;
    flex: 1;
}
</style>

<?php include 'includes/footer.php'; ?>
