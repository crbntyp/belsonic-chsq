<?php
require_once __DIR__ . '/../auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/config.php';

$currentPage = $currentPage ?? '';

// Fetch venue background images for rotator (if not already set)
if (!isset($venueBackgrounds)) {
    $db = getDB();
    $currentVenueId = getCurrentVenueId();
    $venueStmt = $db->prepare("SELECT * FROM venues WHERE id = ?");
    $venueStmt->execute([$currentVenueId]);
    $venue = $venueStmt->fetch();

    $venueBackgrounds = [];
    if ($venue) {
        for ($i = 1; $i <= 5; $i++) {
            $bgImage = $venue['bg_image_' . $i];
            if (!empty($bgImage)) {
                // Apply base path for subdirectory deployments
                $venueBackgrounds[] = asset_url($bgImage);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin</title>
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('styles/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('admin/admin.css'); ?>">
    <script>
    // Pass venue background images to JavaScript
    window.venueBackgroundImages = <?php echo json_encode($venueBackgrounds ?? []); ?>;
    // Pass base path for admin URLs
    window.adminBasePath = '<?php echo admin_url(''); ?>';
    </script>
    <script src="<?php echo asset_url('scripts/main.js'); ?>" defer></script>
</head>
<body class="admin-body">
    <!-- Background Rotator will be inserted by JavaScript -->
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <i class="las la-guitar"></i>
                <h2>Admin</h2>
            </div>

            <nav class="admin-nav">
                <a href="<?php echo admin_url('gigs.php'); ?>" class="<?php echo $currentPage === 'gigs' ? 'active' : ''; ?>">
                    <i class="las la-calendar-alt"></i>
                    Gigs
                </a>
                <a href="<?php echo admin_url('venues-manage.php'); ?>" class="<?php echo $currentPage === 'venues' ? 'active' : ''; ?>">
                    <i class="las la-map-marker-alt"></i>
                    Venues
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo BASE_PATH . '/'; ?>" target="_blank">
                    <i class="las la-external-link-alt"></i>
                    View Website
                </a>
                <a href="<?php echo admin_url('profile.php'); ?>" class="<?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                    <i class="las la-user-circle"></i>
                    My Profile
                </a>
                <a href="<?php echo admin_url('logout.php'); ?>">
                    <i class="las la-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1><?php echo $pageTitle ?? 'Admin'; ?></h1>
                <div class="admin-user">
                    <i class="las la-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </div>

            <div class="admin-content">
