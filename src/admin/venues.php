<?php
$pageTitle = 'Manage Venues';
$currentPage = 'venues';

include 'includes/header.php';

$db = getDB();
$message = '';
$messageType = '';
$messageIsHtml = false;

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $confirmDeleteAll = isset($_GET['confirm_delete_all']) && $_GET['confirm_delete_all'] === '1';

    // Check if venue has associated performances
    $performanceCheck = $db->prepare("SELECT COUNT(*) as count FROM performances WHERE venue_id = ?");
    $performanceCheck->execute([$id]);
    $performanceCount = $performanceCheck->fetch()['count'];

    if ($performanceCount > 0 && !$confirmDeleteAll) {
        // Show warning with option to delete all
        $message = "Cannot delete this venue. It has {$performanceCount} performance(s) associated with it. Please delete or reassign those performances first, or <a href='?delete={$id}&confirm_delete_all=1' onclick='return confirm(\"This will permanently delete the venue and all {$performanceCount} associated performance(s). This cannot be undone. Are you sure?\");' style='color: #dc3545; text-decoration: underline;'>delete venue and all performances</a>.";
        $messageType = 'error';
        $messageIsHtml = true;
    } else {
        try {
            // Delete associated performances first if confirmed
            if ($performanceCount > 0 && $confirmDeleteAll) {
                $db->prepare("DELETE FROM performances WHERE venue_id = ?")->execute([$id]);
            }

            // Delete the venue
            $db->prepare("DELETE FROM venues WHERE id = ?")->execute([$id]);

            if ($confirmDeleteAll && $performanceCount > 0) {
                $message = "Venue and {$performanceCount} associated performance(s) deleted successfully";
            } else {
                $message = 'Venue deleted successfully';
            }
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Error deleting venue: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Handle ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venue_id = $_POST['venue_id'] ?? null;
    $name = $_POST['name'] ?? '';

    // Get existing background images if updating
    $bgImages = ['', '', '', '', ''];
    if ($venue_id) {
        $existingVenue = $db->prepare("SELECT bg_image_1, bg_image_2, bg_image_3, bg_image_4, bg_image_5 FROM venues WHERE id = ?");
        $existingVenue->execute([$venue_id]);
        $existing = $existingVenue->fetch();
        if ($existing) {
            $bgImages = [
                $existing['bg_image_1'] ?? '',
                $existing['bg_image_2'] ?? '',
                $existing['bg_image_3'] ?? '',
                $existing['bg_image_4'] ?? '',
                $existing['bg_image_5'] ?? ''
            ];
        }
    }

    // Handle file uploads for background images (up to 5)
    // Upload directly to dist/img/uploads/venues/ so files are immediately available
    // When running from dist/admin/, go up to dist/, then into img/uploads/venues/
    $upload_dir = __DIR__ . '/../img/uploads/venues/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    for ($i = 1; $i <= 5; $i++) {
        $fieldName = 'bg_image_' . $i;

        // Check if user wants to delete this image
        if (isset($_POST['delete_' . $fieldName]) && $_POST['delete_' . $fieldName] === '1') {
            $bgImages[$i - 1] = '';
            continue;
        }

        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_extension, $allowed_extensions)) {
                $filename = 'venue-' . ($venue_id ?: 'new') . '-bg' . $i . '-' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target_path)) {
                    $bgImages[$i - 1] = '/img/uploads/venues/' . $filename;
                } else {
                    $message = 'Error uploading background image ' . $i;
                    $messageType = 'error';
                }
            }
        }
    }

    try {
        if ($venue_id) {
            // UPDATE existing
            $db->prepare("UPDATE venues SET name = ?, bg_image_1 = ?, bg_image_2 = ?, bg_image_3 = ?, bg_image_4 = ?, bg_image_5 = ? WHERE id = ?")
                ->execute([$name, $bgImages[0], $bgImages[1], $bgImages[2], $bgImages[3], $bgImages[4], $venue_id]);
            $message = 'Venue updated successfully';
        } else {
            // INSERT new
            $db->prepare("INSERT INTO venues (name, bg_image_1, bg_image_2, bg_image_3, bg_image_4, bg_image_5) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$name, $bgImages[0], $bgImages[1], $bgImages[2], $bgImages[3], $bgImages[4]]);
            $message = 'Venue added successfully';
        }
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error saving venue: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all venues
$venues = $db->query("SELECT * FROM venues ORDER BY name ASC")->fetchAll();
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="las la-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <span><?php echo $messageIsHtml ? $message : htmlspecialchars($message); ?></span>
    </div>
<?php endif; ?>

<div class="page-actions">
    <button class="btn btn-primary" onclick="openVenueModal()">
        Add New Venue
    </button>
</div>

<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($venues as $venue): ?>
                <tr>
                    <td><?php echo htmlspecialchars($venue['name']); ?></td>
                    <td class="actions">
                        <button class="btn-icon" onclick='editVenue(<?php echo json_encode($venue); ?>)'>
                            <i class="las la-edit"></i>
                        </button>
                        <button class="btn-icon btn-danger" onclick="deleteVenue(<?php echo $venue['id']; ?>, '<?php echo htmlspecialchars($venue['name']); ?>')">
                            <i class="las la-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($venues)): ?>
                <tr>
                    <td colspan="2" style="text-align: center; color: #999;">No venues yet</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Venue Modal -->
<div id="venueModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">
                Add New Venue
            </h2>
            <button class="modal-close" onclick="closeVenueModal()">
                ×
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="admin-form" id="venueForm">
            <input type="hidden" name="venue_id" id="venue_id">

            <div class="form-section">
                <div class="form-group">
                    <label for="name">Venue Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
            </div>

            <div class="form-section">
                <h3>Background Images (for rotator)</h3>
                <p style="color: #666; font-size: 0.875rem; margin-bottom: 1rem;">Upload up to 5 background images that will rotate on the venue pages</p>

                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="form-group">
                        <label for="bg_image_<?php echo $i; ?>">Background Image <?php echo $i; ?></label>
                        <div class="image-upload-wrapper">
                            <input type="file" id="bg_image_<?php echo $i; ?>" name="bg_image_<?php echo $i; ?>" accept="image/*">
                            <div id="bg_preview_<?php echo $i; ?>" class="image-preview" style="display: none;">
                                <img src="" alt="Preview">
                                <button type="button" class="btn-icon btn-danger" onclick="deleteBackgroundImage(<?php echo $i; ?>)">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                            <input type="hidden" id="delete_bg_image_<?php echo $i; ?>" name="delete_bg_image_<?php echo $i; ?>" value="0">
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">
                    Save Venue
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeVenueModal()">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openVenueModal() {
    document.getElementById('venueModal').style.display = 'flex';
    document.getElementById('modalTitle').innerHTML = 'Add New Venue';
    document.getElementById('venueForm').reset();
    document.getElementById('venue_id').value = '';

    // Hide all image previews
    for (let i = 1; i <= 5; i++) {
        document.getElementById('bg_preview_' + i).style.display = 'none';
        document.getElementById('delete_bg_image_' + i).value = '0';
    }
}

function closeVenueModal() {
    document.getElementById('venueModal').style.display = 'none';
}

function editVenue(venue) {
    document.getElementById('venueModal').style.display = 'flex';
    document.getElementById('modalTitle').innerHTML = 'Edit Venue';
    document.getElementById('venue_id').value = venue.id;
    document.getElementById('name').value = venue.name;

    // Show existing background images
    for (let i = 1; i <= 5; i++) {
        const bgImage = venue['bg_image_' + i];
        const preview = document.getElementById('bg_preview_' + i);

        if (bgImage && bgImage !== '') {
            preview.querySelector('img').src = bgImage;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
        document.getElementById('delete_bg_image_' + i).value = '0';
    }
}

function deleteBackgroundImage(index) {
    if (confirm('Are you sure you want to delete this background image?')) {
        document.getElementById('bg_preview_' + index).style.display = 'none';
        document.getElementById('delete_bg_image_' + index).value = '1';
        document.getElementById('bg_image_' + index).value = '';
    }
}

function deleteVenue(id, name) {
    if (confirm('Are you sure you want to delete ' + name + '?')) {
        window.location.href = window.adminBasePath + 'venues.php?delete=' + id;
    }
}

// Preview images on file select
for (let i = 1; i <= 5; i++) {
    document.getElementById('bg_image_' + i).addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.getElementById('bg_preview_' + i);
                preview.querySelector('img').src = event.target.result;
                preview.style.display = 'block';
                document.getElementById('delete_bg_image_' + i).value = '0';
            };
            reader.readAsDataURL(file);
        }
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('venueModal');
    if (event.target == modal) {
        closeVenueModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeVenueModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
