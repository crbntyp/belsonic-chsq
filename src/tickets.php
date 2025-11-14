<?php
require_once 'includes/config.php';

$pageTitle = 'Tickets';
$currentPage = 'tickets';

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

// Fetch ticket types
$ticketsStmt = $db->prepare("
    SELECT * FROM ticket_types
    WHERE festival_id = ?
    ORDER BY price ASC
");
$ticketsStmt->execute([$festival['id']]);
$ticketTypes = $ticketsStmt->fetchAll();

// Fetch age restrictions
$ageStmt = $db->prepare("
    SELECT * FROM age_restrictions
    WHERE festival_id = ?
    LIMIT 1
");
$ageStmt->execute([$festival['id']]);
$ageRestriction = $ageStmt->fetch();

include 'includes/header.php';
?>

<script>
// Pass venue background images to JavaScript
window.venueBackgroundImages = <?php echo json_encode($venueBackgrounds); ?>;
// Pass base path for subdirectory deployments
window.basePath = '<?php echo BASE_PATH; ?>';
</script>

    <header class="page-header">
        <h1>Tickets & Pricing</h1>
        <p class="subtitle">Get your tickets for <?php echo htmlspecialchars($venue['name'] ?? 'Festival'); ?> 2026</p>
    </header>

    <section class="ticket-types">
        <?php foreach ($ticketTypes as $index => $ticket): ?>
            <div class="ticket-card <?php echo ($index == 1) ? 'featured' : ''; ?>">
                <?php if ($index == 1): ?>
                    <div class="ticket-badge">Most Popular</div>
                <?php endif; ?>
                <div class="ticket-header">
                    <h2><?php echo htmlspecialchars($ticket['name']); ?></h2>
                    <p class="ticket-price">
                        <?php echo $ticket['currency']; ?> <?php echo number_format($ticket['price'], 2); ?>
                    </p>
                </div>
                <div class="ticket-content">
                    <p class="ticket-description"><?php echo htmlspecialchars($ticket['description']); ?></p>
                    <ul class="ticket-features">
                        <?php
                        // Parse features (you could add a features column to the database)
                        $features = [
                            'Access to main festival area',
                            'View from general standing area',
                            'Access to all food & drink outlets',
                            'Access to all facilities'
                        ];
                        if ($ticket['name'] == 'VIP Experience') {
                            $features = [
                                'All General Admission benefits',
                                'Premium viewing platform',
                                'Dedicated VIP bar',
                                'Private toilet facilities',
                                'Fast-track entry',
                                'Complimentary drink on arrival'
                            ];
                        } elseif ($ticket['name'] == 'Weekend Pass') {
                            $features = [
                                'Access to all 4 festival days',
                                'Save money vs buying individual tickets',
                                'Access to all food & drink outlets',
                                'Access to all facilities'
                            ];
                        } elseif ($ticket['name'] == 'Day Ticket') {
                            $features = [
                                'Access to main festival area',
                                'Valid for one day only',
                                'Access to all food & drink outlets',
                                'Access to all facilities'
                            ];
                        }
                        foreach ($features as $feature):
                        ?>
                            <li><i class="las la-check"></i> <?php echo $feature; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?php echo htmlspecialchars($festival['ticket_link']); ?>" class="btn btn-primary" target="_blank">Buy Tickets</a>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <?php if ($ageRestriction): ?>
        <section class="age-restrictions">
            <h2>Age Restrictions</h2>
            <div class="restrictions-content">
                <div class="restriction-icon">
                    <i class="las la-id-card la-4x"></i>
                </div>
                <div class="restriction-details">
                    <h3>This is a <?php echo $ageRestriction['min_age']; ?>+ Event</h3>
                    <p><?php echo htmlspecialchars($ageRestriction['description']); ?></p>
                    <div class="accepted-id">
                        <h4>Acceptable forms of ID:</h4>
                        <ul>
                            <li><i class="las la-passport"></i> Passport</li>
                            <li><i class="las la-id-card"></i> Driver's License</li>
                            <li><i class="las la-id-card-alt"></i> PASS Card</li>
                        </ul>
                    </div>
                    <p class="id-warning">
                        <i class="las la-exclamation-triangle"></i>
                        No ID = No Entry. Copies, screenshots or expired IDs will not be accepted.
                    </p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="ticket-info">
        <h2>Important Information</h2>
        <div class="info-boxes">
            <div class="info-box">
                <h3><i class="las la-ticket-alt"></i> Ticket Collection</h3>
                <p>E-tickets will be sent via email. Please have your ticket ready on your phone or printed out.</p>
            </div>
            <div class="info-box">
                <h3><i class="las la-exchange-alt"></i> Refunds & Exchanges</h3>
                <p>Tickets are non-refundable and non-transferable except in the event of cancellation.</p>
            </div>
            <div class="info-box">
                <h3><i class="las la-ban"></i> What Not to Bring</h3>
                <p>Professional cameras, recording equipment, illegal substances, weapons, glass containers.</p>
            </div>
            <div class="info-box">
                <h3><i class="las la-search"></i> Security Checks</h3>
                <p>All attendees will be subject to security searches on entry. Please arrive early.</p>
            </div>
        </div>
    </section>

    <section class="ticket-faq">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-list">
            <div class="faq-item">
                <h3>Can I buy tickets on the day?</h3>
                <p>Subject to availability. We recommend purchasing in advance to avoid disappointment.</p>
            </div>
            <div class="faq-item">
                <h3>What time do gates open?</h3>
                <p>Gates open at 6:00 PM each day. Check your ticket for specific timings.</p>
            </div>
            <div class="faq-item">
                <h3>Can I re-enter if I leave?</h3>
                <p>No, tickets are for single entry only. Once you leave, you cannot re-enter.</p>
            </div>
            <div class="faq-item">
                <h3>Is the festival rain or shine?</h3>
                <p>Yes, the festival will go ahead rain or shine. Come prepared for all weather conditions.</p>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
