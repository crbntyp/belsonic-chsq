<?php
require_once 'includes/config.php';

$pageTitle = 'Lineup';
$currentPage = 'lineup';

// Get database connection
$db = getDB();

// Fetch current venue
$venueStmt = $db->prepare("SELECT * FROM venues WHERE id = ?");
$venueStmt->execute([getCurrentVenueId()]);
$venue = $venueStmt->fetch();

// Build array of background images from venue for the rotator
$venueBackgrounds = [];
if ($venue) {
    for ($i = 1; $i <= 5; $i++) {
        $bgImage = $venue['bg_image_' . $i] ?? null;
        if (!empty($bgImage)) {
            // Ensure path starts with / for absolute URL
            $venueBackgrounds[] = (strpos($bgImage, '/') === 0) ? $bgImage : '/' . $bgImage;
        }
    }
}

// Fetch festival information for dates
$festivalStmt = $db->prepare("
    SELECT f.*
    FROM festivals f
    WHERE f.status = 'upcoming'
    ORDER BY f.start_date
    LIMIT 1
");
$festivalStmt->execute();
$festival = $festivalStmt->fetch();

// Fetch lineup grouped by date (only show gigs that have gone live and match current venue)
$lineupStmt = $db->prepare("
    SELECT
        p.performance_date,
        p.performance_time,
        p.is_headliner,
        p.supporting_act,
        p.support_acts,
        p.has_afterparty,
        p.ticket_link,
        p.button_text,
        p.sold_out,
        p.ticket_url,
        a.name as artist_name,
        a.genre,
        a.image_url
    FROM performances p
    JOIN artists a ON p.artist_id = a.id
    WHERE p.festival_id = ?
    AND p.venue_id = ?
    AND (p.go_live_date IS NULL OR p.go_live_date <= NOW())
    ORDER BY p.performance_date, p.sort_order
");
$lineupStmt->execute([$festival['id'], getCurrentVenueId()]);
$lineup = $lineupStmt->fetchAll();

// Group performances by date
$performancesByDate = [];
foreach ($lineup as $performance) {
    $date = $performance['performance_date'];
    if (!isset($performancesByDate[$date])) {
        $performancesByDate[$date] = [];
    }
    $performancesByDate[$date][] = $performance;
}

include 'includes/header.php';
?>

<script>
// Pass venue background images to JavaScript
window.venueBackgroundImages = <?php echo json_encode($venueBackgrounds); ?>;
</script>

    <section class="lineup">
        <div class="lineup-header">
            <h2><?php echo htmlspecialchars($venue['name'] ?? 'Festival'); ?> Lineup 2026</h2>
            <?php include 'includes/social-links.php'; ?>
        </div>
        <div class="shows-grid">
            <?php foreach ($performancesByDate as $date => $performances): ?>
                <?php
                // Separate main act and support acts
                $mainAct = null;
                $supportActs = [];
                foreach ($performances as $performance) {
                    if ($performance['is_headliner']) {
                        $mainAct = $performance;
                    } else {
                        $supportActs[] = $performance;
                    }
                }

                // Skip if no main act
                if (!$mainAct) continue;
                ?>

                <article class="show-card">
                        <?php if (!empty($mainAct['image_url'])): ?>
                            <div class="show-image">
                                <img src="<?php echo htmlspecialchars($mainAct['image_url']); ?>"
                                     alt="<?php echo htmlspecialchars($mainAct['artist_name']); ?>">
                            </div>
                        <?php endif; ?>

                        <div class="show-content">
                            <div class="show-date">
                                <span class="day-name"><?php echo date('l', strtotime($date)); ?></span>
                                <span class="day-number"><?php echo date('j', strtotime($date)); ?></span>
                                <span class="month-name"><?php echo date('F', strtotime($date)); ?></span>
                            </div>
                            <h3 class="main-artist"><?php echo htmlspecialchars($mainAct['artist_name']); ?></h3>

                            <?php if ($mainAct['performance_time']): ?>
                                <p class="show-time">
                                    <?php echo date('g:i A', strtotime($mainAct['performance_time'])); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($mainAct['support_acts'])): ?>
                                <div class="support-acts">
                                    <div class="support-acts-content">
                                        <?php echo $mainAct['support_acts']; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="show-actions">
                                <?php if (!empty($mainAct['ticket_link'])): ?>
                                    <?php if ($mainAct['sold_out']): ?>
                                        <button class="btn btn-primary sold-out" disabled>
                                            SOLD OUT
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($mainAct['ticket_link']); ?>"
                                           class="btn btn-primary"
                                           target="_blank">
                                            <!-- <i class="las la-ticket-alt"></i> -->
                                            <?php echo htmlspecialchars($mainAct['button_text'] ?? 'Get Tickets'); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($mainAct['has_afterparty'] && $mainAct['ticket_url']): ?>
                                    <a href="<?php echo htmlspecialchars($mainAct['ticket_url']); ?>"
                                       class="btn btn-afterparty"
                                       target="_blank">
                                        <!-- <i class="las la-glass-cheers"></i> -->
                                        Afterparty Tickets
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                </article>
            <?php endforeach; ?>

            <?php if (empty($performancesByDate)): ?>
                <div class="no-lineup">
                    <i class="las la-calendar-times la-4x"></i>
                    <h3>Lineup Coming Soon</h3>
                    <p>Check back later for artist announcements</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
