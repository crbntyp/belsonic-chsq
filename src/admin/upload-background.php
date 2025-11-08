<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['background_image'])) {
        throw new Exception('Missing required parameters');
    }

    $file = $_FILES['background_image'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
    }

    // Validate file size (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size exceeds 5MB limit');
    }

    // Get database connection
    $db = getDB();

    // Get venue_id from POST data (sent from JavaScript)
    $venueId = isset($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;

    if (!$venueId) {
        throw new Exception('Missing venue_id parameter');
    }

    // Get current venue
    $venueStmt = $db->prepare("SELECT * FROM venues WHERE id = ?");
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch();

    if (!$venue) {
        throw new Exception('Venue not found');
    }

    // Find first available slot
    $slot = null;
    for ($i = 1; $i <= 5; $i++) {
        $imageField = 'bg_image_' . $i;
        if (empty($venue[$imageField])) {
            $slot = $i;
            break;
        }
    }

    if ($slot === null) {
        throw new Exception('All 5 background slots are filled');
    }

    // Create backgrounds directory if it doesn't exist
    // Upload directly to dist/img/backgrounds/ so files are immediately available
    // When running from dist/admin/, go up to dist/, then into img/backgrounds/
    $uploadDir = __DIR__ . '/../img/backgrounds/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'bg_' . $slot . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    $dbPath = '/img/backgrounds/' . $filename; // Use absolute path

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Update database - save to venue table
    $imageField = 'bg_image_' . $slot;
    $stmt = $db->prepare("UPDATE venues SET $imageField = ? WHERE id = ?");
    $stmt->execute([$dbPath, $venueId]);

    echo json_encode([
        'success' => true,
        'message' => 'Background image uploaded successfully',
        'url' => $dbPath,  // Changed 'path' to 'url' to match JavaScript expectation
        'slot' => $slot
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
