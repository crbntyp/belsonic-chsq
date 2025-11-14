<?php
require_once 'includes/config.php';

$pageTitle = 'Venue';
$currentPage = 'venue';

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

// Merge for compatibility with existing template (if needed)
$data = array_merge($festival ?: [], $venue ?: []);

include 'includes/header.php';
?>

<script>
// Pass venue background images to JavaScript
window.venueBackgroundImages = <?php echo json_encode($venueBackgrounds); ?>;
// Pass base path for subdirectory deployments
window.basePath = '<?php echo BASE_PATH; ?>';
</script>

    <header class="page-header">
        <h1>Venue Information</h1>
        <p class="subtitle"><?php echo htmlspecialchars($data['name']); ?>, <?php echo htmlspecialchars($data['city']); ?></p>
    </header>

    <section class="venue-info">
        <div class="venue-details">
            <h2><?php echo htmlspecialchars($data['name']); ?></h2>
            <p class="venue-description">
                <?php echo nl2br(htmlspecialchars($data['description'])); ?>
            </p>

            <div class="info-grid">
                <div class="info-item">
                    <i class="las la-map-marker"></i>
                    <h3>Address</h3>
                    <p>
                        <?php echo nl2br(htmlspecialchars($data['address'])); ?><br>
                        <?php echo htmlspecialchars($data['city']); ?><br>
                        <?php echo htmlspecialchars($data['postcode']); ?><br>
                        <?php echo htmlspecialchars($data['country']); ?>
                    </p>
                </div>

                <div class="info-item">
                    <i class="las la-users"></i>
                    <h3>Capacity</h3>
                    <p><?php echo number_format($data['capacity']); ?> attendees</p>
                </div>

                <div class="info-item">
                    <i class="las la-clock"></i>
                    <h3>Gates Open</h3>
                    <p>6:00 PM daily</p>
                </div>

                <div class="info-item">
                    <i class="las la-parking"></i>
                    <h3>Parking</h3>
                    <p>Limited on-site parking<br>Pre-booking recommended</p>
                </div>
            </div>
        </div>

        <div class="venue-map">
            <h3>Location</h3>
            <div class="map-placeholder">
                <p><i class="las la-map-marked-alt la-3x"></i></p>
                <p>Map integration coming soon</p>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($data['name'] . ' ' . $data['city']); ?>"
                   class="btn btn-secondary" target="_blank">
                    Open in Google Maps
                </a>
            </div>
        </div>
    </section>

    <section class="venue-facilities">
        <h2>Facilities</h2>
        <div class="facilities-grid">
            <div class="facility-card">
                <i class="las la-restroom la-2x"></i>
                <h3>Toilets</h3>
                <p>Multiple toilet facilities throughout the venue</p>
            </div>
            <div class="facility-card">
                <i class="las la-utensils la-2x"></i>
                <h3>Food & Drink</h3>
                <p>Wide selection of food stalls and bars</p>
            </div>
            <div class="facility-card">
                <i class="las la-first-aid la-2x"></i>
                <h3>Medical</h3>
                <p>On-site medical team available</p>
            </div>
            <div class="facility-card">
                <i class="las la-shield-alt la-2x"></i>
                <h3>Security</h3>
                <p>Professional security staff on duty</p>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
