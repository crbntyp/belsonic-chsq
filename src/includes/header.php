<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow" />
    <?php
    // Get current venue for dynamic title, colors, and logo
    $currentVenueId = getCurrentVenueId();
    $currentVenueStmt = $db->prepare("SELECT name, primary_color, secondary_color, logo_url, logo_height FROM venues WHERE id = ?");
    $currentVenueStmt->execute([$currentVenueId]);
    $currentVenue = $currentVenueStmt->fetch();
    $venueName = $currentVenue['name'] ?? 'Festival';
    $venuePrimaryColor = $currentVenue['primary_color'] ?? '#ff006f';
    $venueSecondaryColor = $currentVenue['secondary_color'] ?? '#00ffff';
    $venueLogoUrl = $currentVenue['logo_url'] ?? '/img/assets/logo.svg';
    $venueLogoHeight = $currentVenue['logo_height'] ?? '60px';
    ?>
    <title><?php echo isset($pageTitle) ? $pageTitle : $venueName . ' 2026'; ?> | Belfast's Premier Music Festival</title>
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('styles/main.css'); ?>">
    <style>
    /* Dynamic venue colors */
    :root {
        --venue-color-primary: <?php echo $venuePrimaryColor; ?>;
        --venue-color-secondary: <?php echo $venueSecondaryColor; ?>;
    }
    </style>
    <script src="<?php echo asset_url('scripts/main.js'); ?>" defer></script>
</head>
<body>
    <?php
    // Venue switcher - only show on localhost for testing
    $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
    $isLocalhost = ($currentDomain === 'localhost' || $currentDomain === '127.0.0.1' || strpos($currentDomain, 'localhost:') === 0);

    if ($isLocalhost) {
        // Show venue switcher for testing on localhost only
        $venuesStmt = $db->query("SELECT id, name FROM venues ORDER BY name ASC");
        $venues = $venuesStmt->fetchAll();

        if (count($venues) > 1):
    ?>
    <div class="venue-switcher" style="display: none;">
        <label for="venue-select">Test Venue:</label>
        <select id="venue-select" onchange="window.location.href='/switch-venue.php?venue_id=' + this.value">
            <?php foreach ($venues as $v): ?>
                <option value="<?php echo $v['id']; ?>" <?php echo $v['id'] == $currentVenueId ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($v['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
        endif;
    }
    ?>

    <main class="container">
        <nav class="navbar">
            <div class="nav-brand">
                <img src="<?php echo htmlspecialchars(asset_url($venueLogoUrl)); ?>" alt="<?php echo htmlspecialchars($venueName); ?>" class="logo" style="height: <?php echo htmlspecialchars($venueLogoHeight); ?>;">
            </div>
            <div class="burger-menu" id="burgerMenu">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="mobile-menu-overlay" id="mobileMenuOverlay">
                <div class="mobile-menu-container">
                    <div class="mobile-menu-logo">
                        <img src="<?php echo htmlspecialchars(asset_url($venueLogoUrl)); ?>" alt="<?php echo htmlspecialchars($venueName); ?>" style="height: <?php echo htmlspecialchars($venueLogoHeight); ?>;">
                    </div>
                    <ul class="nav-menu" id="navMenu">
                        <li><a href="index.php" class="<?php echo ($currentPage == 'lineup') ? 'active' : ''; ?>">Lineups</a></li>
                        <li><a href="location.php" class="<?php echo ($currentPage == 'location') ? 'active' : ''; ?>">Location</a></li>
                        <li><a href="info.php" class="<?php echo ($currentPage == 'info') ? 'active' : ''; ?>">General Info & Age Restrictions</a></li>
                        <?php if ($currentVenueId == 2): // Belsonic only ?>
                        <li><a href="accessibility.php" class="<?php echo ($currentPage == 'accessibility') ? 'active' : ''; ?>">Accessibility</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
