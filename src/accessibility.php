<?php
require_once 'includes/config.php';

$pageTitle = 'Accessibility';
$currentPage = 'accessibility';

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

// Fetch accessibility options
$accessibilityStmt = $db->prepare("
    SELECT * FROM accessibility_options
    WHERE festival_id = ?
    ORDER BY sort_order
");
$accessibilityStmt->execute([$festival['id']]);
$options = $accessibilityStmt->fetchAll();

include 'includes/header.php';
?>

<script>
// Pass venue background images to JavaScript
window.venueBackgroundImages = <?php echo json_encode($venueBackgrounds); ?>;
// Pass base path for subdirectory deployments
window.basePath = '<?php echo BASE_PATH; ?>';
</script>

<div class="sub-container">
    <header class="page-header">
        <h1>Accessibility</h1>
        <?php include 'includes/social-links.php'; ?>
    </header>

    <!-- Mobile Dropdown Navigation -->
    <div class="location-dropdown">
        <button class="location-dropdown-trigger" id="locationDropdownTrigger">
            <i class="las la-wheelchair"></i>
            <span>Accessibility Info</span>
            <i class="las la-angle-down dropdown-arrow"></i>
        </button>
        <div class="location-dropdown-menu" id="locationDropdownMenu">
            <button class="location-dropdown-item active" data-tab="info">
                <i class="las la-wheelchair"></i>
                Accessibility Info
            </button>
            <button class="location-dropdown-item" data-tab="faq">
                <i class="las la-question-circle"></i>
                Accessibility FAQs
            </button>
        </div>
    </div>

    <!-- Tabs Navigation (Desktop) -->
    <div class="location-tabs">
        <button class="location-tab active" data-tab="info">
            <i class="las la-wheelchair"></i>
            Accessibility Info
        </button>
        <button class="location-tab" data-tab="faq">
            <i class="las la-question-circle"></i>
            Accessibility FAQs
        </button>
    </div>

    <!-- Accessibility Info Tab -->
    <div class="tab-content active" id="info-tab">
        <section class="transport-intro">
            <p class="lead">
                At <?php echo htmlspecialchars($venue['name'] ?? 'our festival'); ?> we endeavour to provide accessible access for all. Please see below for information regarding our accessibility areas and procedures. If you require any further information, please email <a href="mailto:accessibility@belsonic.com">accessibility@belsonic.com</a>
            </p>
        </section>

        <section class="ticket-info">
            <h2>Accessibility Services</h2>
        <div class="info-boxes">
            <div class="info-box">
                <h3>
                    <i class="las la-parking"></i>
                    Accessible Parking
                </h3>
                <p><strong>For Accessible Ticket Holders Only</strong></p>
                <p>We are currently putting plans in place for 2026. Accessible ticket holders will be contacted closer to the event date with information.</p>
                <p>If you have any other accessible queries please contact us on <a href="mailto:accessibility@belsonic.com">accessibility@belsonic.com</a></p>
            </div>

            <div class="info-box">
                <h3>
                    <i class="las la-door-open"></i>
                    Accessible Entrance
                </h3>
                <p>There is a dropped curb at the pelican crossing directly to the left as you leave the accessible parking area. This will take you to the dropped curb on the 'Park side' of Ormeau Embankment.</p>
                <p>Security will direct you to the Accessible entrance at <strong>GATE 1</strong>. This is the most direct Gate to the Accessible facilities at <?php echo htmlspecialchars($venue['name'] ?? 'the venue'); ?>. When you arrive at GATE 1 your tickets will be scanned, and a member of our team will escort you to the Accessible Platform.</p>
            </div>

            <div class="info-box">
                <h3>
                    <i class="las la-restroom"></i>
                    Accessible Facilities
                </h3>
                <p>Adjacent to the Accessible Platform are Accessible toilets, there are also changing places located in the Medical Tent and in the Ozone Complex.</p>
                <p>We have dedicated and trained staff onsite to help with any needs of our Accessible customers. Simply ask any of the stewards at the Parking Entrance, Gate Entrances, Accessibility Platform, or any of the team from St John's Ambulance for directions or information.</p>
            </div>

            <div class="info-box">
                <h3>
                    <i class="las la-car"></i>
                    Collection & Departure
                </h3>
                <p>For collections, cars will need to be on the Embankment from <strong>10:20pm at the latest</strong> as no cars will be permitted to drive on Ormeau Embankment for at least 15 minutes after the show is finished to allow for a safe customer egress.</p>
                <p><strong>IMPORTANT:</strong> Departure from the Accessible Parking Area is not permitted from 10 mins before scheduled show finish time. This is due to road closures for show egress and only reopens when Event Safety Controller permits the road to reopen.</p>
            </div>
        </div>
        </section>

        <section class="ticket-info">
            <h2>Need More Information?</h2>
            <div class="accessibility-contact">
                <p>
                    For specific accessibility requirements or to discuss your needs, please contact our
                    accessibility team:
                </p>
                <div class="contact-methods">
                    <div class="contact-method">
                        <i class="las la-envelope"></i>
                        <span>Email: <a href="mailto:accessibility@belsonic.com">accessibility@belsonic.com</a></span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Accessibility FAQs Tab -->
    <div class="tab-content" id="faq-tab">
        <section class="ticket-faq">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-accordion">
                <div class="faq-accordion-item">
                    <button class="faq-accordion-header">
                        <span>How can I buy accessible tickets?</span>
                        <i class="las la-angle-down"></i>
                    </button>
                    <div class="faq-accordion-content">
                        <p>Accessible tickets are available through the "Accessible Tickets" button on Ticketmaster's main event page. Purchasers must first register for an AAR (Accessible Accommodation Request) number via "Ticketmaster's Accessible Database" before buying.</p>
                        <p><strong>Important:</strong> BELSONIC requires this unique identifier and cannot accept medical evidence directly.</p>
                    </div>
                </div>

                <div class="faq-accordion-item">
                    <button class="faq-accordion-header">
                        <span>I have wheelchair/accessible tickets, but I'm coming with a larger party. Can they sit/stand nearby?</span>
                        <i class="las la-angle-down"></i>
                    </button>
                    <div class="faq-accordion-content">
                        <p>No. Due to capacity constraints, accessible ticket holders receive one companion ticket, except where there are verified additional care requirements.</p>
                        <p>This policy ensures maximum accessibility availability for all patrons requiring wheelchair access.</p>
                    </div>
                </div>

                <div class="faq-accordion-item">
                    <button class="faq-accordion-header">
                        <span>I bought General Admission tickets but now need accessible tickets—what options exist?</span>
                        <i class="las la-angle-down"></i>
                    </button>
                    <div class="faq-accordion-content">
                        <p>Check Ticketmaster for available accessible tickets. Register for the Accessible Database first, then purchase accessible tickets and request a refund of your original booking through your MyAccount.</p>
                        <p><strong>Important:</strong> Both purchases must be made via the same Ticketmaster account, with refund requests submitted the same day.</p>
                    </div>
                </div>

                <div class="faq-accordion-item">
                    <button class="faq-accordion-header">
                        <span>Can I bring a camping/collapsible chair to this outdoor event?</span>
                        <i class="las la-angle-down"></i>
                    </button>
                    <div class="faq-accordion-content">
                        <p>No. No folding or collapsible chairs are permitted in standing/general admission areas for health and safety reasons.</p>
                        <p>Limited respite seating exists but offers no sightlines. Those requiring continuous seating should explore accessible ticket exchanges instead.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Desktop tab switching
    const tabButtons = document.querySelectorAll('.location-tab');
    const tabContents = document.querySelectorAll('.tab-content');

    // Mobile dropdown elements
    const dropdownTrigger = document.getElementById('locationDropdownTrigger');
    const dropdownMenu = document.getElementById('locationDropdownMenu');
    const dropdownItems = document.querySelectorAll('.location-dropdown-item');

    // Function to switch tabs (works for both desktop and mobile)
    function switchTab(tabName) {
        // Update desktop tabs
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabButtons.forEach(btn => {
            if (btn.getAttribute('data-tab') === tabName) {
                btn.classList.add('active');
            }
        });

        // Update mobile dropdown items
        dropdownItems.forEach(item => item.classList.remove('active'));
        dropdownItems.forEach(item => {
            if (item.getAttribute('data-tab') === tabName) {
                item.classList.add('active');
                // Update trigger text and icon
                const icon = item.querySelector('i').className;
                const text = item.textContent.trim();
                dropdownTrigger.querySelector('i:first-child').className = icon;
                dropdownTrigger.querySelector('span').textContent = text;
            }
        });

        // Update content
        tabContents.forEach(content => content.classList.remove('active'));
        document.getElementById(tabName + '-tab').classList.add('active');
    }

    // Desktop tab click handlers
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            switchTab(button.getAttribute('data-tab'));
        });
    });

    // Mobile dropdown toggle
    dropdownTrigger.addEventListener('click', () => {
        dropdownTrigger.classList.toggle('open');
        dropdownMenu.classList.toggle('open');
    });

    // Mobile dropdown item click
    dropdownItems.forEach(item => {
        item.addEventListener('click', () => {
            switchTab(item.getAttribute('data-tab'));
            // Close dropdown
            dropdownTrigger.classList.remove('open');
            dropdownMenu.classList.remove('open');
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.location-dropdown')) {
            dropdownTrigger.classList.remove('open');
            dropdownMenu.classList.remove('open');
        }
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
