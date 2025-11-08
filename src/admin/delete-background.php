<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['venue_id']) || !isset($input['image_url'])) {
        throw new Exception('Missing required parameters');
    }

    $venueId = (int)$input['venue_id'];
    $imageUrl = $input['image_url'];

    if (!$venueId) {
        throw new Exception('Invalid venue ID');
    }

    // Get database connection
    $db = getDB();

    // Get current venue
    $venueStmt = $db->prepare("SELECT * FROM venues WHERE id = ?");
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch();

    if (!$venue) {
        throw new Exception('Venue not found');
    }

    // Find which slot contains this image
    $slotToDelete = null;
    for ($i = 1; $i <= 5; $i++) {
        $imageField = 'bg_image_' . $i;
        if ($venue[$imageField] === $imageUrl) {
            $slotToDelete = $i;
            break;
        }
    }

    if ($slotToDelete === null) {
        throw new Exception('Image not found in venue slots');
    }

    // Clear the database field
    $imageField = 'bg_image_' . $slotToDelete;
    $stmt = $db->prepare("UPDATE venues SET $imageField = NULL WHERE id = ?");
    $stmt->execute([$venueId]);

    // Optionally delete the physical file
    $filePath = __DIR__ . '/..' . $imageUrl;
    if (file_exists($filePath)) {
        @unlink($filePath); // @ suppresses warnings if deletion fails
    }

    echo json_encode([
        'success' => true,
        'message' => 'Background image deleted successfully',
        'slot' => $slotToDelete
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
