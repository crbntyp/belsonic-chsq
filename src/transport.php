<?php
require_once 'includes/config.php';

$pageTitle = 'Transport';
$currentPage = 'transport';

$db = getDB();

// Fetch current venue
$venueStmt = $db->prepare("SELECT * FROM venues WHERE id = ?");
$venueStmt->execute([getCurrentVenueId()]);
$venue = $venueStmt->fetch();

// Build array of background images from venue for the rotator
$venueBackgrounds = [];
if ($venue) {
    for ($i = 1; $i <= 5; $i++) {
        $bgImage = $venue['bg_image_' . $i];
        if (!empty($bgImage)) {
            // Ensure path starts with / for absolute URL
            $venueBackgrounds[] = (strpos($bgImage, '/') === 0) ? $bgImage : '/' . $bgImage;
        }
    }
}

// Fetch festival information
$festivalStmt = $db->prepare("SELECT * FROM festivals WHERE status = 'upcoming' LIMIT 1");
$festivalStmt->execute();
$festival = $festivalStmt->fetch();

// Fetch transport options
$transportStmt = $db->prepare("
    SELECT * FROM transport_options
    WHERE festival_id = ?
    ORDER BY sort_order
");
$transportStmt->execute([$festival['id']]);
$transports = $transportStmt->fetchAll();

include 'includes/header.php';
?>

<script>
// Pass venue background images to JavaScript
window.venueBackgroundImages = <?php echo json_encode($venueBackgrounds); ?>;
// Pass base path for subdirectory deployments
window.basePath = '<?php echo BASE_PATH; ?>';
</script>

    <header class="page-header">
        <h1>Getting to Belsonic</h1>
        <p class="subtitle">Travel options to Ormeau Park</p>
    </header>

    <section class="transport-intro">
        <p class="lead">
            Ormeau Park is easily accessible from Belfast city centre and surrounding areas.
            We recommend planning your journey in advance, especially for evening events when services may be busier.
        </p>
    </section>

    <section class="transport-options">
        <?php foreach ($transports as $transport): ?>
            <div class="transport-card">
                <div class="transport-icon">
                    <i class="las la-<?php echo htmlspecialchars($transport['icon']); ?> la-3x"></i>
                </div>
                <div class="transport-content">
                    <h2><?php echo htmlspecialchars($transport['title']); ?></h2>
                    <p class="transport-route"><?php echo htmlspecialchars($transport['description']); ?></p>
                    <div class="transport-details">
                        <?php if ($transport['provider']): ?>
                            <div class="detail-item">
                                <span class="label">Provider:</span>
                                <span class="value"><?php echo htmlspecialchars($transport['provider']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($transport['cost']): ?>
                            <div class="detail-item">
                                <span class="label">Cost:</span>
                                <span class="value"><?php echo htmlspecialchars($transport['cost']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($transport['duration']): ?>
                            <div class="detail-item">
                                <span class="label">Duration:</span>
                                <span class="value"><?php echo htmlspecialchars($transport['duration']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="transport-tips">
        <h2>Travel Tips</h2>
        <ul class="tips-list">
            <li><i class="las la-check-circle"></i> Plan your journey in advance and check service times</li>
            <li><i class="las la-check-circle"></i> Allow extra time for travel during peak hours</li>
            <li><i class="las la-check-circle"></i> Consider return journey options before the event</li>
            <li><i class="las la-check-circle"></i> Keep your ticket/pass accessible for easy boarding</li>
            <li><i class="las la-check-circle"></i> Check for road closures or diversions on event days</li>
        </ul>
    </section>

<?php include 'includes/footer.php'; ?>
