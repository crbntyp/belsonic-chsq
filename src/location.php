<?php
require_once 'includes/config.php';

$pageTitle = 'Location';
$currentPage = 'location';

$db = getDB();

// Fetch venue based on config (or test override)
$venueStmt = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$venueStmt->execute([getCurrentVenueId()]);
$venue = $venueStmt->fetch();

// Fallback to first venue if configured venue not found
if (!$venue) {
    $venueStmt = $db->prepare("SELECT * FROM venues ORDER BY id ASC LIMIT 1");
    $venueStmt->execute();
    $venue = $venueStmt->fetch();
}

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

// Fetch festival information
$festivalStmt = $db->prepare("SELECT * FROM festivals WHERE status = 'upcoming' LIMIT 1");
$festivalStmt->execute();
$festival = $festivalStmt->fetch();

// Function to extract plain text items from WYSIWYG HTML content
function extractListItems($html) {
    if (empty($html)) {
        return [];
    }

    $items = [];

    // Use DOMDocument to parse HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Suppress HTML parsing warnings
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    // Extract text from <li> elements
    $listItems = $dom->getElementsByTagName('li');
    foreach ($listItems as $li) {
        $text = trim($li->textContent);
        if (!empty($text)) {
            $items[] = $text;
        }
    }

    // Extract text from <p> elements if no list items found
    if (empty($items)) {
        $paragraphs = $dom->getElementsByTagName('p');
        foreach ($paragraphs as $p) {
            $text = trim($p->textContent);
            if (!empty($text)) {
                $items[] = $text;
            }
        }
    }

    return $items;
}

// Function to extract links with text and href from WYSIWYG HTML content
function extractLinks($html) {
    if (empty($html)) {
        return [];
    }

    $links = [];

    // Use DOMDocument to parse HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Suppress HTML parsing warnings
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    // Extract <a> elements
    $anchors = $dom->getElementsByTagName('a');
    foreach ($anchors as $anchor) {
        $text = trim($anchor->textContent);
        $href = $anchor->getAttribute('href');

        if (!empty($text) && !empty($href)) {
            $links[] = [
                'text' => $text,
                'href' => $href
            ];
        }
    }

    return $links;
}

// Initialize variables
$pubsLinks = [];
$accommodationLinks = [];

// Generate dynamic map iframes for pubs and accommodation
if ($venue) {
    $venueName = $venue['name'];
    $venueCity = $venue['city'] ?? 'Belfast';

    // Check if custom pubs list exists
    if (!empty($venue['pubs_list'])) {
        $pubsList = extractListItems($venue['pubs_list']);
        $pubsLinks = extractLinks($venue['pubs_list']);

        if (!empty($pubsList) && !empty($venue['google_maps_api_key'])) {
            // Use JavaScript API for multi-pin map
            $pubsMapId = 'pubs-map-container';
            $pubsMapHtml = '<div id="' . $pubsMapId . '" style="width:100%; height:450px; background: #f0f0f0;"></div>';

            // Store locations for JavaScript
            $pubsMapLocations = $pubsList;
        } else if (!empty($pubsList)) {
            // Fallback: Show general pubs area
            $pubsQuery = urlencode("pubs and bars near {$venueName}, {$venueCity}");
            $pubsMapHtml = '<iframe
                src="https://www.google.com/maps?q=' . $pubsQuery . '&output=embed"
                width="100%"
                height="450"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>';
        } else {
            // Fallback to generic search
            $pubsQuery = urlencode("bars and pubs near {$venueName}, {$venueCity}");
            $pubsMapHtml = '<iframe
                src="https://www.google.com/maps?q=' . $pubsQuery . '&output=embed"
                width="100%"
                height="450"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>';
        }
    } else {
        // Fallback to generic search
        $pubsQuery = urlencode("bars and pubs near {$venueName}, {$venueCity}");
        $pubsMapHtml = '<iframe
            src="https://www.google.com/maps?q=' . $pubsQuery . '&output=embed"
            width="100%"
            height="450"
            style="border:0;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>';
    }

    // Check if custom accommodation list exists
    if (!empty($venue['accommodation_list'])) {
        $accommodationList = extractListItems($venue['accommodation_list']);
        $accommodationLinks = extractLinks($venue['accommodation_list']);

        if (!empty($accommodationList) && !empty($venue['google_maps_api_key'])) {
            // Use JavaScript API for multi-pin map
            $accommodationMapId = 'accommodation-map-container';
            $accommodationMapHtml = '<div id="' . $accommodationMapId . '" style="width:100%; height:450px; background: #f0f0f0;"></div>';

            // Store locations for JavaScript
            $accommodationMapLocations = $accommodationList;
        } else if (!empty($accommodationList)) {
            // Fallback: Show general hotels area
            $accommodationQuery = urlencode("hotels near {$venueName}, {$venueCity}");
            $accommodationMapHtml = '<iframe
                src="https://www.google.com/maps?q=' . $accommodationQuery . '&output=embed"
                width="100%"
                height="450"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>';
        } else {
            // Fallback to generic search
            $accommodationQuery = urlencode("hotels in {$venueCity} city centre");
            $accommodationMapHtml = '<iframe
                src="https://www.google.com/maps?q=' . $accommodationQuery . '&output=embed"
                width="100%"
                height="450"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>';
        }
    } else {
        // Fallback to generic search
        $accommodationQuery = urlencode("hotels in {$venueCity} city centre");
        $accommodationMapHtml = '<iframe
            src="https://www.google.com/maps?q=' . $accommodationQuery . '&output=embed"
            width="100%"
            height="450"
            style="border:0;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>';
    }
}

include 'includes/header.php';
?>

<?php if (!empty($venue['google_maps_api_key'])): ?>
<script>
// Map configuration data
window.mapConfig = {
    apiKey: <?php echo json_encode($venue['google_maps_api_key']); ?>,
    venueCenter: {lat: <?php echo $venue['latitude'] ?? '54.5973'; ?>, lng: <?php echo $venue['longitude'] ?? '-5.9301'; ?>},
    venueCity: <?php echo json_encode($venueCity); ?>,
    pubsLocations: <?php echo json_encode($pubsMapLocations ?? []); ?>,
    accommodationLocations: <?php echo json_encode($accommodationMapLocations ?? []); ?>
};

// Initialize maps when Google Maps API loads
function initMaps() {
    console.log('Initializing maps...', window.mapConfig);

    // Initialize pubs map
    if (window.mapConfig.pubsLocations.length > 0) {
        const pubsMapDiv = document.getElementById('pubs-map-container');
        if (pubsMapDiv) {
            console.log('Creating pubs map...');
            const map = new google.maps.Map(pubsMapDiv, {
                zoom: 14,
                center: window.mapConfig.venueCenter
            });
            const geocoder = new google.maps.Geocoder();
            const bounds = new google.maps.LatLngBounds();

            window.mapConfig.pubsLocations.forEach((location) => {
                geocoder.geocode({
                    address: location + ', ' + window.mapConfig.venueCity
                }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        const marker = new google.maps.Marker({
                            map: map,
                            position: results[0].geometry.location,
                            title: location
                        });

                        // Create info window
                        const infoWindow = new google.maps.InfoWindow({
                            content: `
                                <div style="padding: 8px;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #1f2937;">${location}</h3>
                                    <p style="margin: 0 0 8px 0; font-size: 14px; color: #6b7280;">${results[0].formatted_address}</p>
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(results[0].formatted_address)}"
                                       target="_blank"
                                       style="color: #ff6b35; text-decoration: none; font-weight: 600; font-size: 14px;">
                                        Get Directions →
                                    </a>
                                </div>
                            `
                        });

                        // Open info window on marker click
                        marker.addListener('click', () => {
                            infoWindow.open(map, marker);
                        });

                        bounds.extend(results[0].geometry.location);
                        map.fitBounds(bounds);
                    } else {
                        console.error('Geocode failed for:', location, status);
                    }
                });
            });
        }
    }

    // Initialize accommodation map
    if (window.mapConfig.accommodationLocations.length > 0) {
        const accommodationMapDiv = document.getElementById('accommodation-map-container');
        if (accommodationMapDiv) {
            console.log('Creating accommodation map...');
            const map = new google.maps.Map(accommodationMapDiv, {
                zoom: 14,
                center: window.mapConfig.venueCenter
            });
            const geocoder = new google.maps.Geocoder();
            const bounds = new google.maps.LatLngBounds();

            window.mapConfig.accommodationLocations.forEach((location) => {
                geocoder.geocode({
                    address: location + ', ' + window.mapConfig.venueCity
                }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        const marker = new google.maps.Marker({
                            map: map,
                            position: results[0].geometry.location,
                            title: location
                        });

                        // Create info window
                        const infoWindow = new google.maps.InfoWindow({
                            content: `
                                <div style="padding: 8px;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #1f2937;">${location}</h3>
                                    <p style="margin: 0 0 8px 0; font-size: 14px; color: #6b7280;">${results[0].formatted_address}</p>
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(results[0].formatted_address)}"
                                       target="_blank"
                                       style="color: #ff6b35; text-decoration: none; font-weight: 600; font-size: 14px;">
                                        Get Directions →
                                    </a>
                                </div>
                            `
                        });

                        // Open info window on marker click
                        marker.addListener('click', () => {
                            infoWindow.open(map, marker);
                        });

                        bounds.extend(results[0].geometry.location);
                        map.fitBounds(bounds);
                    } else {
                        console.error('Geocode failed for:', location, status);
                    }
                });
            });
        }
    }
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($venue['google_maps_api_key']); ?>&callback=initMaps&v=weekly" async></script>
<?php endif; ?>

<script>
// Pass venue background images to JavaScript
window.venueBackgroundImages = <?php echo json_encode($venueBackgrounds); ?>;
// Pass base path for subdirectory deployments
window.basePath = '<?php echo BASE_PATH; ?>';
</script>

<div class="sub-container">
    <header class="page-header">
        <h1>Getting to <?php echo htmlspecialchars($venue['name'] ?? 'Belsonic'); ?></h1>
        <?php include 'includes/social-links.php'; ?>
    </header>

    <!-- Tabs Navigation -->
    <div class="location-tabs">
        <button class="location-tab active" data-tab="venue">
            <i class="las la-map-marker"></i>
            Venue Location
        </button>
        <?php if (!empty($venue['pubs_list']) || !empty($pubsMapHtml)): ?>
        <button class="location-tab" data-tab="pubs">
            <i class="las la-beer"></i>
            Pubs & Bars
        </button>
        <?php endif; ?>
        <?php if (!empty($venue['accommodation_list']) || !empty($accommodationMapHtml)): ?>
        <button class="location-tab" data-tab="accommodation">
            <i class="las la-hotel"></i>
            Places to Stay
        </button>
        <?php endif; ?>
    </div>

    <!-- Venue Tab -->
    <div class="tab-content active" id="venue-tab">
        <?php if (!empty($venue['venue_map_url'])): ?>
        <section class="map">
            <?php echo $venue['venue_map_url']; ?>
        </section>
        <?php endif; ?>
    </div>

    <!-- Pubs Tab -->
    <div class="tab-content" id="pubs-tab">
        <?php if (!empty($pubsMapHtml)): ?>
        <section class="map">
            <?php echo $pubsMapHtml; ?>
        </section>
        <?php endif; ?>

        <!-- DEBUG: <?php echo 'pubsLinks count: ' . count($pubsLinks); ?> -->
        <?php if (!empty($pubsLinks)): ?>
        <section class="location-pills">
            <?php foreach ($pubsLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['href']); ?>" target="_blank" rel="noopener noreferrer" class="location-pill">
                    <i class="las la-beer"></i>
                    <?php echo htmlspecialchars($link['text']); ?>
                </a>
            <?php endforeach; ?>
        </section>
        <?php else: ?>
        <!-- DEBUG: No pubs links found. Raw data length: <?php echo strlen($venue['pubs_list'] ?? ''); ?> -->
        <?php endif; ?>
    </div>

    <!-- Accommodation Tab -->
    <div class="tab-content" id="accommodation-tab">
        <?php if (!empty($accommodationMapHtml)): ?>
        <section class="map">
            <?php echo $accommodationMapHtml; ?>
        </section>
        <?php endif; ?>

        <!-- DEBUG: <?php echo 'accommodationLinks count: ' . count($accommodationLinks); ?> -->
        <?php if (!empty($accommodationLinks)): ?>
        <section class="location-pills">
            <?php foreach ($accommodationLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['href']); ?>" target="_blank" rel="noopener noreferrer" class="location-pill">
                    <i class="las la-hotel"></i>
                    <?php echo htmlspecialchars($link['text']); ?>
                </a>
            <?php endforeach; ?>
        </section>
        <?php else: ?>
        <!-- DEBUG: No accommodation links found. Raw data length: <?php echo strlen($venue['accommodation_list'] ?? ''); ?> -->
        <?php endif; ?>
    </div>
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
});
</script>

<?php include 'includes/footer.php'; ?>
