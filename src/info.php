<?php
require_once 'includes/config.php';

$pageTitle = 'General Info';
$currentPage = 'info';

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

// Fetch all upcoming performances with age restrictions for current venue
$performancesStmt = $db->prepare("
    SELECT p.*, a.name as artist_name, a.image_url, p.age_restriction
    FROM performances p
    JOIN artists a ON p.artist_id = a.id
    WHERE p.festival_id = ?
      AND p.venue_id = ?
      AND p.performance_date >= CURDATE()
      AND p.is_headliner = 1
      AND (p.go_live_date IS NULL OR p.go_live_date <= CURDATE())
    ORDER BY p.performance_date ASC
");
$performancesStmt->execute([$festival['id'], getCurrentVenueId()]);
$performances = $performancesStmt->fetchAll();

include 'includes/header.php';
?>

<script>
// Pass venue background images to JavaScript
window.venueBackgroundImages = <?php echo json_encode($venueBackgrounds); ?>;
</script>

<div class="sub-container">
    <header class="page-header">
        <h1>General Info</h1>
        <?php include 'includes/social-links.php'; ?>
    </header>



    <!-- Tabs Navigation -->
    <div class="location-tabs">
        <button class="location-tab active" data-tab="age">
            <i class="las la-id-card"></i>
            Age Restrictions
        </button>
        <button class="location-tab" data-tab="faq">
            <i class="las la-question-circle"></i>
            FAQs
        </button>
        <?php if (getCurrentVenueId() !== 3): // Hide parking tab for CHSQ ?>
        <button class="location-tab" data-tab="parking">
            <i class="las la-bus"></i>
            Coach/Bus Parking
        </button>
        <?php endif; ?>
    </div>

    <!-- Age Restrictions Tab -->
    <div class="tab-content active" id="age-tab">
        <section class="ticket-info">
            <h2>Age Restrictions</h2>

            <?php
            // Fetch performances with age restriction content for current venue
            $ageRestrictionsStmt = $db->prepare("
                SELECT p.*, a.name as artist_name
                FROM performances p
                JOIN artists a ON p.artist_id = a.id
                WHERE p.venue_id = ?
                  AND p.age_restriction_content IS NOT NULL
                  AND p.age_restriction_content != ''
                  AND p.performance_date >= CURDATE()
                  AND (p.go_live_date IS NULL OR p.go_live_date <= NOW())
                ORDER BY p.performance_date ASC
            ");
            $ageRestrictionsStmt->execute([getCurrentVenueId()]);
            $ageRestrictions = $ageRestrictionsStmt->fetchAll();
            ?>

            <?php if (!empty($ageRestrictions)): ?>
                <div class="age-restrictions-list">
                    <?php foreach ($ageRestrictions as $restriction): ?>
                        <div class="age-restriction-item">
                            <h3><?php echo htmlspecialchars($restriction['artist_name']); ?></h3>
                            <p class="gig-date">
                                <i class="las la-calendar"></i>
                                <?php echo date('l, F j, Y', strtotime($restriction['performance_date'])); ?>
                                <?php if (!empty($restriction['performance_time'])): ?>
                                    at <?php echo date('g:i A', strtotime($restriction['performance_time'])); ?>
                                <?php endif; ?>
                            </p>
                            <div class="age-restriction-content">
                                <?php echo $restriction['age_restriction_content']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-lineup" style="padding: 60px 20px;">
                    <i class="las la-wrench la-4x" style="color: rgba(255, 255, 255, 0.3);"></i>
                    <h3 style="margin-top: 20px; color: rgba(255, 255, 255, 0.8);">In Development</h3>
                    <p style="color: rgba(255, 255, 255, 0.6);">Age restriction information will be available soon</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="ticket-info">
            <h2>Acceptable Forms of ID</h2>

            <div class="id-warning-box">
                <i class="las la-exclamation-triangle"></i>
                <p><strong>No ID = No Entry.</strong> Copies, screenshots or expired IDs will not be accepted.</p>
            </div>

            <div class="info-boxes">
                <div class="info-box">
                    <h3>
                        <i class="las la-passport"></i>
                        Official Valid Passport
                    </h3>
                    <p>A valid passport is accepted as proof of age and identity.</p>
                </div>
                <div class="info-box">
                    <h3>
                        <i class="las la-id-card"></i>
                        Photo Driving License
                    </h3>
                    <p>A current photo driving license is an acceptable form of ID.</p>
                </div>
                <div class="info-box">
                    <h3>
                        <i class="las la-id-card-alt"></i>
                        Proof of Age Card
                    </h3>
                    <p>Government approved PASS, Citizen card or <a href="https://www.validateuk.co.uk" target="_blank">ValidateUK</a></p>
                </div>
                <div class="info-box">
                    <h3>
                        <i class="las la-vote-yea"></i>
                        Electoral Card
                    </h3>
                    <p>Electoral identity cards are accepted as valid ID.</p>
                </div>
                <div class="info-box">
                    <h3>
                        <i class="las la-graduation-cap"></i>
                        Student Cards
                    </h3>
                    <p>Valid student ID cards from recognized institutions.</p>
                </div>
                <div class="info-box">
                    <h3>
                        <i class="las la-mobile"></i>
                        Yoti App
                    </h3>
                    <p>Yoti digital ID accepted at <?php echo htmlspecialchars($venue['name'] ?? 'our'); ?> events. <a href="https://www.citizencard.com/yoti-citizencard-new-id-solution-for-the-uk" target="_blank">Learn more</a></p>
                </div>
            </div>
        </section>
    </div>

    <!-- FAQs Tab -->
    <div class="tab-content" id="faq-tab">
        <section class="ticket-faq">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-accordion">
                <?php
                // Fetch FAQs from faqs table
                $faqStmt = $db->prepare("SELECT * FROM faqs WHERE venue_id = ? ORDER BY sort_order ASC");
                $faqStmt->execute([getCurrentVenueId()]);
                $faqs = $faqStmt->fetchAll();

                foreach ($faqs as $faq):
                ?>
                <div class="faq-accordion-item">
                    <button class="faq-accordion-header">
                        <span><?php echo htmlspecialchars($faq['question']); ?></span>
                        <i class="las la-angle-down"></i>
                    </button>
                    <div class="faq-accordion-content">
                        <span><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($faqs)): ?>
                    <p style="text-align: center; color: rgba(255, 255, 255, 0.6); padding: 40px;">No FAQs available yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php if (getCurrentVenueId() !== 3): // Hide parking tab for CHSQ ?>
    <!-- Coach/Bus Parking Tab -->
    <div class="tab-content" id="parking-tab">
        <section class="transport-intro">
            <p class="lead">
                All festival coaches and busses must use the Ormeau Embankment. Road closures are in place for all concert dates.
            </p>
        </section>

        <section class="ticket-info">
            <h2>Coach & Bus Parking</h2>
            <div class="info-boxes">
                <div class="info-box">
                    <h3><i class="las la-map-marked-alt"></i> Parking Location</h3>
                    <p><strong>Ormeau Embankment ONLY</strong><br>All festival busses and coaches park on The Ormeau Embankment with direct access to entrance gates. Road closure orders are in place for all concert dates.</p>
                </div>
                <div class="info-box">
                    <h3><i class="las la-route"></i> Approach Route</h3>
                    <p>Coaches must approach from the <strong>Ormeau Road entrance</strong> to Ormeau Embankment. Do not attempt to access from any other route.</p>
                </div>
                <div class="info-box">
                    <h3><i class="las la-id-badge"></i> Numbered Pass System</h3>
                    <p>Security at the top of the road will issue each coach with a large, numbered pass sheet. Drivers should communicate this number to passengers so they can easily find their coach after the concert.</p>
                </div>
                <div class="info-box">
                    <h3><i class="las la-sign-out-alt"></i> Departure Route</h3>
                    <p>When departing, busses must continue along Ormeau Embankment following stewarding directions toward the <strong>Ravenhill Road end</strong> of the embankment.</p>
                </div>
                <div class="info-box">
                    <h3><i class="las la-exchange-alt"></i> Drop-off & Return Option</h3>
                    <p>Coaches may drop off customers on the Embankment and continue toward Ravenhill Road. If departing, coaches must return by <strong>10:00 PM at the latest</strong> to collect passengers.</p>
                </div>
                <div class="info-box">
                    <h3><i class="las la-exclamation-triangle"></i> Important Notice</h3>
                    <p><strong>NO DROP-OFF OR PARKING ANYWHERE ELSE.</strong><br>Only the Ormeau Embankment is permitted for coach and bus operations. All other locations are prohibited.</p>
                </div>
            </div>
        </section>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.location-tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.getAttribute('data-tab');

            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked tab and corresponding content
            button.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        });
    });

    // FAQ Accordion
    const accordionHeaders = document.querySelectorAll('.faq-accordion-header');

    accordionHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const item = header.parentElement;
            const content = header.nextElementSibling;
            const isActive = item.classList.contains('active');

            // Close all accordion items
            document.querySelectorAll('.faq-accordion-item').forEach(accordionItem => {
                accordionItem.classList.remove('active');
                accordionItem.querySelector('.faq-accordion-content').style.maxHeight = null;
            });

            // Open clicked item if it wasn't already open
            if (!isActive) {
                item.classList.add('active');
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
