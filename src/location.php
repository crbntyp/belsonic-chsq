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

// ---------------------------------------------------------------------------
// Location sets (pubs & places to stay) — defined in data/locations.php.
// Fixed reference data kept in code (the locations never change): no database
// and no runtime geocoding, and it works even for venues not yet built out in
// the backend. The set is chosen per request by domain, with a venue-name
// fallback for local/dev (where the host is just "localhost").
// ---------------------------------------------------------------------------

$locationsConfig = [
    // domain (without www) => location group
    'domains' => [
        'summerseriesbelfast.com' => 'custom-house-square',
        'customhousesquare.com'   => 'custom-house-square',
        'belsonic.com'            => 'belsonic',
    ],
    // fallback for localhost/unmapped hosts: match against the venue name
    'venueKeywords' => [
        'belsonic'     => 'belsonic',
        'chsq'         => 'custom-house-square',
        'custom house' => 'custom-house-square',
        'summer'       => 'custom-house-square',
    ],
    'groups' => [
        // Custom House Square — shared by The Summer Series + CHSQ
        'custom-house-square' => [
            'pubs' => [
                ['name' => 'The Limelight',   'url' => 'http://www.limelightbelfast.com/',     'lat' => 54.5928895, 'lng' => -5.9285395],
                ['name' => "McHugh's Bar",    'url' => 'https://www.facebook.com/mchughsbar/', 'lat' => 54.6009349, 'lng' => -5.9237855],
                ['name' => 'Duke of York',    'url' => 'https://dukeofyorkbelfast.com/',       'lat' => 54.6017784, 'lng' => -5.9273238],
                ['name' => 'The Garrick',     'url' => 'https://thegarrickbar.com/',           'lat' => 54.5972745, 'lng' => -5.9266170],
                ['name' => 'The Dirty Onion', 'url' => 'https://thedirtyonion.com/',           'lat' => 54.6015803, 'lng' => -5.9264559],
            ],
            'accommodation' => [
                ['name' => 'The Merchant',            'url' => 'https://www.themerchanthotel.com/',           'lat' => 54.6009401, 'lng' => -5.9254846],
                ['name' => 'Bullitt Hotel',           'url' => 'https://bullitthotel.com/',                   'lat' => 54.5998320, 'lng' => -5.9252684],
                ['name' => 'Clayton Hotel Belfast',   'url' => 'https://www.claytonhotelbelfast.com/',        'lat' => 54.5928336, 'lng' => -5.9301811],
                ['name' => 'Malmaison Hotel Belfast', 'url' => 'https://www.malmaison.com/',                  'lat' => 54.5999869, 'lng' => -5.9239427],
                ['name' => 'Radisson Blu',            'url' => 'https://www.radissonblu.com/en/hotel-belfast','lat' => 54.5908187, 'lng' => -5.9227438],
                ['name' => 'Park Inn',                'url' => 'https://www.parkinn.co.uk/hotel-belfast',     'lat' => 54.5937416, 'lng' => -5.9319108],
                ['name' => 'Premier Inn Alfred Street','url' => 'http://www.premierinn.com/',                 'lat' => 54.5936426, 'lng' => -5.9273408],
            ],
        ],
        // Belsonic — Ormeau Park
        'belsonic' => [
            'pubs' => [
                ['name' => 'The Limelight',       'url' => 'https://www.limelightbelfast.com/',            'lat' => 54.5928895, 'lng' => -5.9285395],
                ['name' => 'The Pavilion Bar',    'url' => 'https://pavilionbelfast.com/',                 'lat' => 54.5765837, 'lng' => -5.9174087],
                ['name' => 'The Errigle Bar',     'url' => 'https://errigle.com/',                         'lat' => 54.5760173, 'lng' => -5.9170110],
                ['name' => 'Northern Lights Bar', 'url' => 'https://galwaybaybrewery.com/northernlights/', 'lat' => 54.5756000, 'lng' => -5.9173000],
            ],
            'accommodation' => [
                ['name' => 'The Merchant',          'url' => 'https://www.themerchanthotel.com/',                                         'lat' => 54.6009401, 'lng' => -5.9254846],
                ['name' => 'The Fitzwilliam Hotel', 'url' => 'https://www.fitzwilliamhotelbelfast.com/',                                  'lat' => 54.5956189, 'lng' => -5.9353351],
                ['name' => 'Clayton Hotel',         'url' => 'https://www.claytonhotelbelfast.com/',                                      'lat' => 54.5928336, 'lng' => -5.9301811],
                ['name' => 'Bullitt',               'url' => 'https://bullitthotel.com/',                                                 'lat' => 54.5998320, 'lng' => -5.9252684],
                ['name' => 'AC Hotel Belfast',      'url' => 'https://www.marriott.com/en-gb/hotels/bfsac-ac-hotel-belfast/overview/',   'lat' => 54.6041913, 'lng' => -5.9205122],
                ['name' => 'Radisson Blu',          'url' => 'https://radisson-blu.hotelsbelfastcity.com/en/',                            'lat' => 54.5908187, 'lng' => -5.9227438],
                ['name' => 'Park Inn',              'url' => 'https://park-inn-by-radisson.hotelsbelfastcity.com/en/',                    'lat' => 54.5937416, 'lng' => -5.9319108],
                ['name' => 'Holiday Inn Express',   'url' => 'https://www.ihg.com/holidayinnexpress/hotels/gb/en/belfast/bfsex/hoteldetail','lat' => 54.5865000, 'lng' => -5.9269000],
                ['name' => "Ibis Queen's Quarter",  'url' => 'https://all.accor.com/hotel/7288/index.en.shtml',                           'lat' => 54.5859077, 'lng' => -5.9310025],
            ],
        ],
    ],
];

$requestHost   = preg_replace('/^www\./', '', strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '')));
$locationGroup = $locationsConfig['domains'][$requestHost] ?? null;

if ($locationGroup === null) {
    // Unmapped host (e.g. localhost): match against the current venue name
    $venueNameLc = strtolower($venue['name'] ?? '');
    foreach ($locationsConfig['venueKeywords'] as $keyword => $group) {
        if ($keyword !== '' && strpos($venueNameLc, $keyword) !== false) {
            $locationGroup = $group;
            break;
        }
    }
}

$locationSet = ($locationGroup !== null && isset($locationsConfig['groups'][$locationGroup]))
    ? $locationsConfig['groups'][$locationGroup]
    : ['pubs' => [], 'accommodation' => []];

$pubsPlaces          = $locationSet['pubs'] ?? [];
$accommodationPlaces = $locationSet['accommodation'] ?? [];

// Derive the pill links and the map pins from the chosen set
$toPillLink = function ($p) { return ['text' => $p['name'], 'href' => $p['url'] ?? '#']; };
$hasCoords  = function ($p) { return isset($p['lat'], $p['lng']); };

$pubsLinks          = array_map($toPillLink, $pubsPlaces);
$accommodationLinks = array_map($toPillLink, $accommodationPlaces);
$pubsPins           = array_values(array_filter($pubsPlaces, $hasCoords));
$accommodationPins  = array_values(array_filter($accommodationPlaces, $hasCoords));

$venueName   = $venue['name'] ?? 'the venue';
$hasVenueGeo = !empty($venue['latitude']) && !empty($venue['longitude']);

include 'includes/header.php';
?>

<?php if ($hasVenueGeo || !empty($pubsPins) || !empty($accommodationPins)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
// Map data — venue marker (if the venue has coords) + the location pins
window.shineMapData = {
    venue: <?php echo $hasVenueGeo
        ? json_encode(['lat' => (float)$venue['latitude'], 'lng' => (float)$venue['longitude'], 'name' => $venue['name'] ?? 'Venue'])
        : 'null'; ?>,
    colors: {
        primary: <?php echo json_encode($venue['primary_color'] ?? '#ff006f'); ?>,
        secondary: <?php echo json_encode($venue['secondary_color'] ?? '#00ffff'); ?>
    },
    pubs: <?php echo json_encode($pubsPins); ?>,
    accommodation: <?php echo json_encode($accommodationPins); ?>
};

window.shineMaps = {};

(function () {
    var TILE_URL = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
    var TILE_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';

    function pinIcon(color, size) {
        return L.divIcon({
            className: 'shine-pin',
            html: '<span style="display:block;width:' + size + 'px;height:' + size + 'px;'
                + 'border-radius:50% 50% 50% 0;transform:rotate(-45deg);background:' + color + ';'
                + 'border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.5);"></span>',
            iconSize: [size, size],
            iconAnchor: [size / 2, size],
            popupAnchor: [0, -size]
        });
    }

    function popupHtml(name, url, lat, lng, color) {
        var title = url
            ? '<a href="' + url + '" target="_blank" rel="noopener" style="color:#1a1a2e;text-decoration:none;">' + name + '</a>'
            : name;
        return '<div style="padding:6px 8px;min-width:150px;font-family:inherit;">'
            + '<h3 style="margin:0 0 6px;font-size:15px;color:#1a1a2e;">' + title + '</h3>'
            + '<a href="https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng + '" '
            + 'target="_blank" rel="noopener" style="color:' + color + ';text-decoration:none;font-weight:700;font-size:13px;">Get Directions &rarr;</a>'
            + '</div>';
    }

    function makeMap(id, center, zoom) {
        var map = L.map(id, { scrollWheelZoom: false }).setView(center, zoom);
        // Drop Leaflet's default prefix (it includes a Ukrainian flag); keep only
        // the required OpenStreetMap/CARTO tile attribution.
        map.attributionControl.setPrefix(false);
        L.tileLayer(TILE_URL, { attribution: TILE_ATTR, subdomains: 'abcd', maxZoom: 20 }).addTo(map);
        return map;
    }

    function addVenueMarker(map) {
        var v = window.shineMapData.venue;
        if (!v) return;
        var c = window.shineMapData.colors.primary;
        L.marker([v.lat, v.lng], { icon: pinIcon(c, 24) })
            .addTo(map)
            .bindPopup(popupHtml(v.name, null, v.lat, v.lng, c));
    }

    function initVenueMap() {
        var v = window.shineMapData.venue;
        if (!v || !document.getElementById('venue-map-container')) return;
        var map = makeMap('venue-map-container', [v.lat, v.lng], 16);
        addVenueMarker(map);
        window.shineMaps['venue'] = map;
    }

    function initListMap(id, pins) {
        if (!document.getElementById(id) || !pins.length) return;
        var v = window.shineMapData.venue;
        var c = window.shineMapData.colors.secondary;
        var center = v ? [v.lat, v.lng] : [pins[0].lat, pins[0].lng];
        var map = makeMap(id, center, 14);
        var bounds = [];
        if (v) { addVenueMarker(map); bounds.push([v.lat, v.lng]); }
        pins.forEach(function (p) {
            L.marker([p.lat, p.lng], { icon: pinIcon(c, 18) })
                .addTo(map)
                .bindPopup(popupHtml(p.name, p.url, p.lat, p.lng, c));
            bounds.push([p.lat, p.lng]);
        });
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
        map._shineBounds = bounds; // re-fit after the tab becomes visible
        window.shineMaps[id] = map;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof L === 'undefined') { console.error('Leaflet failed to load'); return; }
        initVenueMap();
        initListMap('pubs-map-container', window.shineMapData.pubs);
        initListMap('accommodation-map-container', window.shineMapData.accommodation);
    });
})();
</script>
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

    <!-- Mobile Dropdown Navigation -->
    <div class="location-dropdown">
        <button class="location-dropdown-trigger" id="locationDropdownTrigger">
            <i class="las la-map-marker"></i>
            <span>Venue Location</span>
            <i class="las la-angle-down dropdown-arrow"></i>
        </button>
        <div class="location-dropdown-menu" id="locationDropdownMenu">
            <button class="location-dropdown-item active" data-tab="venue">
                <i class="las la-map-marker"></i>
                Venue Location
            </button>
            <?php if (!empty($pubsPlaces)): ?>
            <button class="location-dropdown-item" data-tab="pubs">
                <i class="las la-beer"></i>
                Pubs & Bars
            </button>
            <?php endif; ?>
            <?php if (!empty($accommodationPlaces)): ?>
            <button class="location-dropdown-item" data-tab="accommodation">
                <i class="las la-hotel"></i>
                Places to Stay
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs Navigation (Desktop) -->
    <div class="location-tabs">
        <button class="location-tab active" data-tab="venue">
            <i class="las la-map-marker"></i>
            Venue Location
        </button>
        <?php if (!empty($pubsPlaces)): ?>
        <button class="location-tab" data-tab="pubs">
            <i class="las la-beer"></i>
            Pubs & Bars
        </button>
        <?php endif; ?>
        <?php if (!empty($accommodationPlaces)): ?>
        <button class="location-tab" data-tab="accommodation">
            <i class="las la-hotel"></i>
            Places to Stay
        </button>
        <?php endif; ?>
    </div>

    <!-- Venue Tab -->
    <div class="tab-content active" id="venue-tab">
        <?php if ($hasVenueGeo): ?>
        <section class="map">
            <div id="venue-map-container" style="width:100%; height:450px;"></div>
        </section>
        <?php elseif (!empty($venue['venue_map_url'])): ?>
        <section class="map">
            <?php echo $venue['venue_map_url']; ?>
        </section>
        <?php endif; ?>
    </div>

    <!-- Pubs Tab -->
    <div class="tab-content" id="pubs-tab">
        <?php if (!empty($pubsPins)): ?>
        <section class="map">
            <div id="pubs-map-container" style="width:100%; height:450px;"></div>
        </section>
        <?php endif; ?>

        <?php if (!empty($pubsLinks)): ?>
        <section class="location-pills">
            <?php foreach ($pubsLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['href']); ?>" target="_blank" rel="noopener noreferrer" class="location-pill">
                    <i class="las la-beer"></i>
                    <?php echo htmlspecialchars($link['text']); ?>
                </a>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>
    </div>

    <!-- Accommodation Tab -->
    <div class="tab-content" id="accommodation-tab">
        <?php if (!empty($accommodationPins)): ?>
        <section class="map">
            <div id="accommodation-map-container" style="width:100%; height:450px;"></div>
        </section>
        <?php endif; ?>

        <?php if (!empty($accommodationLinks)): ?>
        <section class="location-pills">
            <?php foreach ($accommodationLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['href']); ?>" target="_blank" rel="noopener noreferrer" class="location-pill">
                    <i class="las la-hotel"></i>
                    <?php echo htmlspecialchars($link['text']); ?>
                </a>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>
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

        // Leaflet maps must recalculate size + re-fit bounds once their tab is visible
        if (window.shineMaps) {
            const mapKey = tabName === 'venue' ? 'venue' : (tabName + '-map-container');
            const map = window.shineMaps[mapKey];
            if (map) setTimeout(function () {
                map.invalidateSize();
                if (map._shineBounds) map.fitBounds(map._shineBounds, { padding: [40, 40], maxZoom: 15 });
            }, 60);
        }
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
});
</script>

<?php include 'includes/footer.php'; ?>
