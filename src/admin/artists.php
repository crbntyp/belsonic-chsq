<?php
$pageTitle = 'Manage Artists';
$currentPage = 'artists';

include 'includes/header.php';

$db = getDB();
$message = '';
$messageType = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM artists WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Artist deleted successfully';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error deleting artist: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $website = $_POST['website'] ?? '';
    $facebook = $_POST['facebook'] ?? '';
    $twitter = $_POST['twitter'] ?? '';
    $instagram = $_POST['instagram'] ?? '';
    $spotify = $_POST['spotify'] ?? '';

    // Handle image upload
    // Upload directly to dist/img/uploads/ so files are immediately available
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        // When running from dist/admin/, go up to dist/, then into img/uploads/
        $upload_dir = __DIR__ . '/../img/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            // Create safe filename from artist name
            $safe_name = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $filename = $safe_name . '-' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                $image_url = '/img/uploads/' . $filename;
            }
        }
    }

    try {
        if ($id) {
            // UPDATE
            $stmt = $db->prepare("
                UPDATE artists SET
                    name = ?, bio = ?, genre = ?, image_url = ?,
                    website = ?, facebook = ?, twitter = ?, instagram = ?, spotify = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $bio, $genre, $image_url, $website, $facebook, $twitter, $instagram, $spotify, $id]);
            $message = 'Artist updated successfully';
        } else {
            // INSERT
            $stmt = $db->prepare("
                INSERT INTO artists (name, bio, genre, image_url, website, facebook, twitter, instagram, spotify)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $bio, $genre, $image_url, $website, $facebook, $twitter, $instagram, $spotify]);
            $message = 'Artist added successfully';
        }
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error saving artist: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get artist for editing
$editArtist = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM artists WHERE id = ?");
    $stmt->execute([$id]);
    $editArtist = $stmt->fetch();
}

// Get all artists
$artists = $db->query("SELECT * FROM artists ORDER BY name ASC")->fetchAll();
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['add']) || $editArtist): ?>
    <div class="form-card">
        <h2>
            <?php echo $editArtist ? 'Edit' : 'Add'; ?> Artist
        </h2>

        <form method="POST" class="admin-form" enctype="multipart/form-data">
            <?php if ($editArtist): ?>
                <input type="hidden" name="id" value="<?php echo $editArtist['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Artist Name *</label>
                    <input type="text" id="name" name="name" required
                           value="<?php echo htmlspecialchars($editArtist['name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="genre">Genre</label>
                    <input type="text" id="genre" name="genre"
                           value="<?php echo htmlspecialchars($editArtist['genre'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="bio">Biography</label>
                <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($editArtist['bio'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="image_file">Artist Image - <span class="label-helper">upload image file (jpg, png, gif)</span></label>
                <input type="file" id="image_file" name="image_file" accept="image/*" onchange="previewArtistImage(this)">
                <?php if (!empty($editArtist['image_url'])): ?>
                <div id="artist_image_preview" style="margin-top: 0.5rem;">
                    <img id="preview_artist_image" src="<?php echo htmlspecialchars($editArtist['image_url']); ?>" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                </div>
                <?php else: ?>
                <div id="artist_image_preview" style="margin-top: 0.5rem; display: none;">
                    <img id="preview_artist_image" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                </div>
                <?php endif; ?>
                <small>Or enter a URL below if you prefer</small>
            </div>

            <div class="form-group">
                <label for="image_url">Image URL (Optional)</label>
                <input type="text" id="image_url" name="image_url"
                       value="<?php echo htmlspecialchars($editArtist['image_url'] ?? ''); ?>"
                       placeholder="/img/uploads/artist-name.jpg">
                <small>Leave blank to use uploaded file above</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website"
                           value="<?php echo htmlspecialchars($editArtist['website'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="spotify">Spotify URL</label>
                    <input type="url" id="spotify" name="spotify"
                           value="<?php echo htmlspecialchars($editArtist['spotify'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="facebook">Facebook</label>
                    <input type="text" id="facebook" name="facebook"
                           value="<?php echo htmlspecialchars($editArtist['facebook'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="twitter">Twitter</label>
                    <input type="text" id="twitter" name="twitter"
                           value="<?php echo htmlspecialchars($editArtist['twitter'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="instagram">Instagram</label>
                    <input type="text" id="instagram" name="instagram"
                           value="<?php echo htmlspecialchars($editArtist['instagram'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $editArtist ? 'Update' : 'Add'; ?> Artist
                </button>
                <a href="<?php echo admin_url('artists.php'); ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="page-actions">
        <a href="<?php echo admin_url('artists.php?add=1'); ?>" class="btn btn-primary">
            Add New Artist
        </a>
    </div>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Genre</th>
                    <th>Website</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($artists as $artist): ?>
                    <tr>
                        <td><?php echo $artist['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($artist['name']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($artist['genre'] ?? '-'); ?></td>
                        <td>
                            <?php if ($artist['website']): ?>
                                <a href="<?php echo htmlspecialchars($artist['website']); ?>" target="_blank">
                                    Visit
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="<?php echo admin_url('artists.php?edit=' . $artist['id']); ?>" class="btn-icon" title="Edit">
                                <i class="las la-edit"></i>
                            </a>
                            <a href="<?php echo admin_url('artists.php?delete=' . $artist['id']); ?>"
                               class="btn-icon btn-danger"
                               title="Delete"
                               onclick="return confirm('Are you sure you want to delete this artist?');">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
function previewArtistImage(input) {
    const preview = document.getElementById('artist_image_preview');
    const previewImg = document.getElementById('preview_artist_image');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
