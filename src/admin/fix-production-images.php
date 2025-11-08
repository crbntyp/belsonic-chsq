<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Fix Production Images';
$currentPage = 'settings';

include 'includes/header.php';

$db = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_images'])) {
    try {
        // Delete artists with missing images (sample/placeholder data)
        $artistsToDelete = [
            'The Strokes',
            'Disclosure',
            'Stormzy',
            'Local Support Act 1',
            'Local Support Act 2'
        ];

        foreach ($artistsToDelete as $artistName) {
            // First delete any performances by this artist
            $stmt = $db->prepare("DELETE FROM performances WHERE artist_id IN (SELECT id FROM artists WHERE name = ?)");
            $stmt->execute([$artistName]);

            // Then delete the artist
            $stmt = $db->prepare("DELETE FROM artists WHERE name = ?");
            $stmt->execute([$artistName]);
        }

        // Fix artist images to use existing files
        $fixes = [
            ['name' => 'Teddy Swims', 'url' => '/img/uploads/teddy-swims-1761920128.png'],
            ['name' => 'Def Leppard', 'url' => '/img/uploads/def-leopard-1761920143.png'],
            ['name' => 'Sonny Fedora', 'url' => '/img/uploads/sonny-fedora-1761920116.png'],
            ['name' => 'The Cure', 'url' => '/img/uploads/the-cure-1761921317.png'],
            ['name' => 'Pitbull', 'url' => '/img/uploads/pitbull-1761920158.png'],
        ];

        foreach ($fixes as $fix) {
            $stmt = $db->prepare("UPDATE artists SET image_url = ? WHERE name LIKE ?");
            $stmt->execute([$fix['url'], '%' . $fix['name'] . '%']);
        }

        // Fix venue logos
        $db->prepare("UPDATE venues SET logo_url = '/img/assets/logo.svg' WHERE id = 2")->execute();
        $db->prepare("UPDATE venues SET logo_url = '/img/uploads/logos/logo_1762008023.png' WHERE id = 3")->execute();

        $message = 'Artists deleted and image paths fixed successfully!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error fixing images: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current artists with images
$artists = $db->query("SELECT id, name, image_url FROM artists WHERE image_url IS NOT NULL AND image_url != ''")->fetchAll();
$venues = $db->query("SELECT id, name, logo_url FROM venues")->fetchAll();
?>

<div class="form-card">
    <h2>
        <i class="las la-images"></i>
        Fix Production Image Paths
    </h2>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="las la-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-warning">
        <i class="las la-exclamation-triangle"></i>
        <strong>This will:</strong>
        <ul style="margin: 10px 0 0 20px;">
            <li>Delete sample artists (The Strokes, Disclosure, Stormzy, Local Support Acts) and their performances</li>
            <li>Fix image paths for remaining artists to use existing working images</li>
            <li>Fix venue logos</li>
        </ul>
        <p style="margin-top: 10px;"><strong>Only run this if images are broken on the production site.</strong></p>
    </div>

    <h3>Current Artist Images</h3>
    <table style="width: 100%; margin-bottom: 20px; border-collapse: collapse;">
        <thead>
            <tr style="background: rgba(255,255,255,0.1);">
                <th style="padding: 10px; text-align: left;">Artist</th>
                <th style="padding: 10px; text-align: left;">Image URL</th>
                <th style="padding: 10px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($artists as $artist): ?>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <td style="padding: 10px;"><?php echo htmlspecialchars($artist['name']); ?></td>
                <td style="padding: 10px; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($artist['image_url']); ?></td>
                <td style="padding: 10px; text-align: center;">
                    <?php
                    $imagePath = __DIR__ . '/..' . $artist['image_url'];
                    if (file_exists($imagePath)) {
                        echo '<span style="color: #4ade80;">✓ Exists</span>';
                    } else {
                        echo '<span style="color: #f87171;">✗ Missing</span>';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Venue Logos</h3>
    <table style="width: 100%; margin-bottom: 20px; border-collapse: collapse;">
        <thead>
            <tr style="background: rgba(255,255,255,0.1);">
                <th style="padding: 10px; text-align: left;">Venue</th>
                <th style="padding: 10px; text-align: left;">Logo URL</th>
                <th style="padding: 10px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($venues as $venue): ?>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <td style="padding: 10px;"><?php echo htmlspecialchars($venue['name']); ?></td>
                <td style="padding: 10px; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($venue['logo_url']); ?></td>
                <td style="padding: 10px; text-align: center;">
                    <?php
                    $imagePath = __DIR__ . '/..' . $venue['logo_url'];
                    if (file_exists($imagePath)) {
                        echo '<span style="color: #4ade80;">✓ Exists</span>';
                    } else {
                        echo '<span style="color: #f87171;">✗ Missing</span>';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <form method="POST">
        <button type="submit" name="fix_images" class="btn btn-primary" onclick="return confirm('Are you sure you want to fix all image paths?')">
            <i class="las la-wrench"></i>
            Fix All Image Paths
        </button>
        <a href="/admin/index.php" class="btn btn-secondary">
            Cancel
        </a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
