<?php
$pageTitle = 'Manage Gigs';
$currentPage = 'gigs';

include 'includes/header.php';

$db = getDB();

$message = '';
$messageType = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Delete performance and artist together
        $stmt = $db->prepare("SELECT artist_id FROM performances WHERE id = ?");
        $stmt->execute([$id]);
        $performance = $stmt->fetch();

        if ($performance) {
            $db->prepare("DELETE FROM performances WHERE id = ?")->execute([$id]);
            $db->prepare("DELETE FROM artists WHERE id = ?")->execute([$performance['artist_id']]);
            $message = 'Gig deleted successfully';
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error deleting gig: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gig_id = $_POST['gig_id'] ?? null;
    $artist_id = $_POST['artist_id'] ?? null;

    // Artist fields
    $artist_name = $_POST['artist_name'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    // Set empty values for unused fields
    $genre = '';
    $bio = '';
    $website = '';
    $facebook = '';
    $twitter = '';
    $instagram = '';

    // Gig fields
    $performance_date = $_POST['performance_date'] ?? '';
    $performance_time = $_POST['performance_time'] ?? '';
    $support_acts = $_POST['support_acts'] ?? ''; // WYSIWYG content
    $age_restriction_content = $_POST['age_restriction_content'] ?? ''; // WYSIWYG content
    $ticket_link = $_POST['ticket_link'] ?? '';
    $button_text = $_POST['button_text'] ?? 'Get Tickets';
    $sold_out = isset($_POST['sold_out']) ? 1 : 0;
    $has_afterparty = isset($_POST['has_afterparty']) ? 1 : 0;
    $ticket_url = $_POST['ticket_url'] ?? '';

    // Combine go_live_date and go_live_time into datetime format
    $go_live_date = null;
    if (!empty($_POST['go_live_date'])) {
        $go_live_date = $_POST['go_live_date'];
        if (!empty($_POST['go_live_time'])) {
            $go_live_date .= ' ' . $_POST['go_live_time'];
        }
    }

    $age_restriction = $_POST['age_restriction'] ?? null;
    $venue_id = $_POST['venue_id'] ?? null;

    // Handle file upload
    // Upload directly to dist/img/uploads/ so files are immediately available
    if (isset($_FILES['artist_image']) && $_FILES['artist_image']['error'] === UPLOAD_ERR_OK) {
        // When running from dist/admin/, go up to dist/, then into img/uploads/
        $upload_dir = __DIR__ . '/../img/uploads/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['artist_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_extension, $allowed_extensions)) {
            // Generate unique filename - sanitize artist name for filesystem
            $safe_name = strtolower($artist_name);
            // Remove or replace special characters
            $safe_name = str_replace(['&', "'", '"', '/', '\\', '?', '#', '%'], '', $safe_name);
            // Replace spaces and multiple hyphens with single hyphen
            $safe_name = preg_replace('/[\s-]+/', '-', $safe_name);
            // Remove leading/trailing hyphens
            $safe_name = trim($safe_name, '-');

            $filename = $safe_name . '-' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['artist_image']['tmp_name'], $target_path)) {
                // Direct path to uploaded image in dist/img/uploads/
                $image_url = '/img/uploads/' . $filename;
            } else {
                $message = 'Error uploading image file';
                $messageType = 'error';
            }
        } else {
            $message = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
            $messageType = 'error';
        }
    }

    try {
        $db->beginTransaction();

        if ($gig_id && $artist_id) {
            // UPDATE existing
            $db->prepare("
                UPDATE artists SET name = ?, genre = ?, image_url = ?, bio = ?,
                website = ?, facebook = ?, twitter = ?, instagram = ?
                WHERE id = ?
            ")->execute([$artist_name, $genre, $image_url, $bio, $website, $facebook, $twitter, $instagram, $artist_id]);

            $db->prepare("
                UPDATE performances SET performance_date = ?, performance_time = ?, support_acts = ?, age_restriction_content = ?, ticket_link = ?, button_text = ?, sold_out = ?, has_afterparty = ?, ticket_url = ?, go_live_date = ?, age_restriction = ?, venue_id = ?
                WHERE id = ?
            ")->execute([$performance_date, $performance_time, $support_acts, $age_restriction_content, $ticket_link, $button_text, $sold_out, $has_afterparty, $ticket_url, $go_live_date, $age_restriction, $venue_id, $gig_id]);

            $message = 'Gig updated successfully';
        } else {
            // INSERT new
            $db->prepare("
                INSERT INTO artists (name, genre, image_url, bio, website, facebook, twitter, instagram)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$artist_name, $genre, $image_url, $bio, $website, $facebook, $twitter, $instagram]);

            $new_artist_id = $db->lastInsertId();

            // Get first upcoming festival
            $festivalStmt = $db->query("SELECT id FROM festivals WHERE status = 'upcoming' LIMIT 1");
            $festival = $festivalStmt->fetch();

            if ($festival) {
                $db->prepare("
                    INSERT INTO performances (festival_id, venue_id, artist_id, performance_date, performance_time, support_acts, age_restriction_content, ticket_link, button_text, sold_out, has_afterparty, ticket_url, go_live_date, age_restriction, is_headliner, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)
                ")->execute([$festival['id'], $venue_id, $new_artist_id, $performance_date, $performance_time, $support_acts, $age_restriction_content, $ticket_link, $button_text, $sold_out, $has_afterparty, $ticket_url, $go_live_date, $age_restriction]);
            }

            $message = 'Gig added successfully';
        }

        $db->commit();
        $messageType = 'success';
    } catch (PDOException $e) {
        $db->rollBack();
        $message = 'Error saving gig: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get gig for editing
$editGig = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("
        SELECT p.*, a.name as artist_name, a.genre, a.image_url, a.bio,
               a.website, a.facebook, a.twitter, a.instagram,
               f.name as festival_name, p.age_restriction, v.name as venue_name, v.id as venue_id
        FROM performances p
        JOIN artists a ON p.artist_id = a.id
        JOIN festivals f ON p.festival_id = f.id
        LEFT JOIN venues v ON p.venue_id = v.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $editGig = $stmt->fetch();

    // Split go_live_date datetime into date and time parts
    if ($editGig && !empty($editGig['go_live_date'])) {
        $datetime = new DateTime($editGig['go_live_date']);
        $editGig['go_live_date'] = $datetime->format('Y-m-d');
        $editGig['go_live_time'] = $datetime->format('H:i');
    }
}

// Get all gigs with artist info (button_text already included in p.*)
$gigs = $db->query("
    SELECT p.*, a.name as artist_name, a.genre, a.image_url, a.bio,
           a.website, a.facebook, a.twitter, a.instagram,
           f.name as festival_name, p.age_restriction, v.name as venue_name, v.id as venue_id
    FROM performances p
    JOIN artists a ON p.artist_id = a.id
    JOIN festivals f ON p.festival_id = f.id
    LEFT JOIN venues v ON p.venue_id = v.id
    WHERE p.is_headliner = 1
    ORDER BY p.performance_date ASC
")->fetchAll();

// Get all venues for dropdown and tabs
$venues = $db->query("SELECT id, name FROM venues ORDER BY name ASC")->fetchAll();

// Group gigs by venue
$gigsByVenue = [];
foreach ($venues as $venue) {
    $gigsByVenue[$venue['id']] = [
        'name' => $venue['name'],
        'gigs' => []
    ];
}

foreach ($gigs as $gig) {
    $venueId = $gig['venue_id'] ?? 0;
    if ($venueId && isset($gigsByVenue[$venueId])) {
        $gigsByVenue[$venueId]['gigs'][] = $gig;
    }
}
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="las la-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['add']) || $editGig): ?>
    <div class="form-card">
        <h2>
            <i class="las la-calendar-plus"></i>
            <?php echo $editGig ? 'Edit' : 'Add'; ?> Gig
        </h2>

        <form method="POST" class="gig-form" id="gigForm" enctype="multipart/form-data">
            <?php if ($editGig): ?>
                <input type="hidden" name="gig_id" id="gig_id" value="<?php echo $editGig['id']; ?>">
                <input type="hidden" name="artist_id" id="artist_id" value="<?php echo $editGig['artist_id']; ?>">
            <?php else: ?>
                <input type="hidden" name="gig_id" id="gig_id" value="">
                <input type="hidden" name="artist_id" id="artist_id" value="">
            <?php endif; ?>

            <!-- Form Tabs -->
            <div class="location-tabs">
                <button type="button" class="location-tab active" data-tab="artist">
                    <i class="las la-user-circle"></i>
                    Artist Details
                </button>
                <button type="button" class="location-tab" data-tab="show">
                    <i class="las la-calendar-check"></i>
                    Show Details
                </button>
                <button type="button" class="location-tab" data-tab="age">
                    <i class="las la-id-card"></i>
                    Age Restrictions
                </button>
            </div>

            <!-- Artist Details Tab -->
            <div class="tab-content active" id="artist-tab">
                <div class="form-section">

                <div class="form-group">
                    <label for="artist_name">Artist Name *</label>
                    <input type="text" id="artist_name" name="artist_name" required
                           value="<?php echo htmlspecialchars($editGig['artist_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="artist_image">Artist Image - <span class="label-helper">upload an image file (jpg, png, or gif)</span></label>
                    <input type="file" id="artist_image" name="artist_image" accept="image/*" onchange="previewImage(this)">
                    <input type="hidden" id="image_url" name="image_url" value="<?php echo htmlspecialchars($editGig['image_url'] ?? ''); ?>">
                    <div id="image_preview" style="margin-top: 0.5rem; <?php echo !empty($editGig['image_url']) ? '' : 'display: none;'; ?>">
                        <img id="preview_img" src="<?php echo htmlspecialchars($editGig['image_url'] ?? ''); ?>" style="max-width: 200px; border-radius: 8px; border: 2px solid #e5e7eb;">
                    </div>
                </div>

                </div>
            </div>

            <!-- Show Details Tab -->
            <div class="tab-content" id="show-tab">
                <div class="form-section">

                <div class="form-row" style="margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="performance_date">Date *</label>
                        <input type="date" id="performance_date" name="performance_date" required
                               value="<?php echo htmlspecialchars($editGig['performance_date'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="performance_time">Time</label>
                        <input type="time" id="performance_time" name="performance_time"
                               value="<?php echo htmlspecialchars($editGig['performance_time'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="venue_id">Venue * - <span class="label-helper">which venue will host this gig</span></label>
                    <select id="venue_id" name="venue_id" required>
                        <option value="">Select a venue</option>
                        <?php foreach ($venues as $venue): ?>
                            <option value="<?php echo $venue['id']; ?>" <?php echo (isset($editGig['venue_id']) && $editGig['venue_id'] == $venue['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($venue['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="button_text">Button Text - <span class="label-helper">customize the ticket button text</span></label>
                    <input type="text" id="button_text" name="button_text" placeholder="Get Tickets"
                           value="<?php echo htmlspecialchars($editGig['button_text'] ?? 'Get Tickets'); ?>">
                </div>

                <div class="form-group">
                    <label for="ticket_link">Ticket or Sign up Link *</label>
                    <input type="url" id="ticket_link" name="ticket_link" placeholder="https://..." required
                           value="<?php echo htmlspecialchars($editGig['ticket_link'] ?? ''); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="checkbox-label">
                        <input type="checkbox" id="sold_out" name="sold_out" value="1" <?php echo (isset($editGig['sold_out']) && $editGig['sold_out']) ? 'checked' : ''; ?>>
                        Sold Out - <span class="label-helper">mark this gig as sold out</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="support_acts">Support Acts</label>
                    <div id="support_acts_editor"></div>
                    <textarea id="support_acts" name="support_acts" style="display: none;"><?php echo htmlspecialchars($editGig['support_acts'] ?? ''); ?></textarea>
                    <small>Use the editor to format support acts and special guests</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label afterparty-checkbox">
                        <input type="checkbox" id="has_afterparty" name="has_afterparty" value="1" onchange="toggleTicketUrl()" <?php echo (isset($editGig['has_afterparty']) && $editGig['has_afterparty']) ? 'checked' : ''; ?>>
                        Show Afterparty Button
                    </label>
                </div>

                <div class="form-group" id="ticket_url_group" style="<?php echo (isset($editGig['has_afterparty']) && $editGig['has_afterparty']) ? 'display: flex;' : 'display: none;'; ?>">
                    <label for="ticket_url">Ticket URL</label>
                    <input type="url" id="ticket_url" name="ticket_url" placeholder="https://..."
                           value="<?php echo htmlspecialchars($editGig['ticket_url'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="go_live_date">Go Live Date & Time (Optional)</label>
                    <div class="form-row">
                        <input type="date" id="go_live_date" name="go_live_date"
                               value="<?php echo htmlspecialchars($editGig['go_live_date'] ?? ''); ?>">
                        <input type="time" id="go_live_time" name="go_live_time"
                               value="<?php echo htmlspecialchars($editGig['go_live_time'] ?? ''); ?>">
                    </div>
                </div>

                </div>
            </div>

            <!-- Age Restrictions Tab -->
            <div class="tab-content" id="age-tab">
                <div class="form-section">

                <div class="form-group">
                    <label for="age_restriction_content">Age Restriction Information</label>
                    <div id="age_restriction_content_editor"></div>
                    <textarea id="age_restriction_content" name="age_restriction_content" style="display: none;"><?php echo htmlspecialchars($editGig['age_restriction_content'] ?? ''); ?></textarea>
                    <small>Add specific age restriction details for this gig. This will be displayed on the frontend Age Restrictions tab.</small>
                </div>

                </div>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="las la-save"></i>
                    <?php echo $editGig ? 'Update' : 'Save'; ?> Gig
                </button>
                <a href="<?php echo admin_url('gigs.php'); ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="page-actions">
        <a href="<?php echo admin_url('gigs.php?add=1'); ?>" class="btn btn-primary">
            Add New Gig
        </a>
    </div>

<?php if (!empty($gigsByVenue)): ?>
<!-- Venue Tabs -->
<div class="location-tabs">
    <?php $firstVenue = true; foreach ($gigsByVenue as $venueId => $venueData): ?>
        <button class="location-tab <?php echo $firstVenue ? 'active' : ''; ?>" data-tab="venue-<?php echo $venueId; ?>">
            <i class="las la-map-marker"></i>
            <?php echo htmlspecialchars($venueData['name']); ?>
        </button>
    <?php $firstVenue = false; endforeach; ?>
</div>

<!-- Venue Tab Contents -->
<?php $firstVenue = true; foreach ($gigsByVenue as $venueId => $venueData): ?>
<div class="tab-content <?php echo $firstVenue ? 'active' : ''; ?>" id="venue-<?php echo $venueId; ?>-tab">
    <?php if (!empty($venueData['gigs'])): ?>
    <section class="lineup">
        <div class="shows-grid">
        <?php foreach ($venueData['gigs'] as $gig): ?>
            <article class="show-card">
                <?php if (!empty($gig['image_url'])): ?>
                    <div class="show-image">
                        <img src="<?php echo htmlspecialchars($gig['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($gig['artist_name']); ?>"
                             onerror="this.style.display='none';">
                    </div>
                <?php endif; ?>

                <div class="show-content">
                    <div class="show-date">
                        <span class="day-name"><?php echo date('l', strtotime($gig['performance_date'])); ?></span>
                        <span class="day-number"><?php echo date('j', strtotime($gig['performance_date'])); ?></span>
                        <span class="month-name"><?php echo date('F', strtotime($gig['performance_date'])); ?></span>
                    </div>

                    <h3 class="main-artist"><?php echo htmlspecialchars($gig['artist_name']); ?></h3>

                    <?php if ($gig['go_live_date'] && strtotime($gig['go_live_date']) > time()): ?>
                        <div class="scheduled-badge">
                            <i class="las la-clock"></i>
                            Goes live: <?php echo date('M j, Y \a\t g:i A', strtotime($gig['go_live_date'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($gig['performance_time']): ?>
                        <p class="show-time">
                            <?php echo date('g:i A', strtotime($gig['performance_time'])); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($gig['support_acts'])): ?>
                        <div class="support-acts">
                            <div class="support-acts-content">
                                <?php echo $gig['support_acts']; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="show-actions">
                        <a href="<?php echo admin_url('gigs.php?edit=' . $gig['id']); ?>" class="btn btn-icon btn-edit" title="Edit">
                            <i class="las la-edit"></i>
                        </a>
                        <button class="btn btn-icon btn-delete"
                                onclick="deleteGig(<?php echo $gig['id']; ?>, '<?php echo htmlspecialchars($gig['artist_name'], ENT_QUOTES); ?>');" title="Delete">
                            <i class="las la-trash"></i>
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
        <div class="no-lineup">
            <i class="las la-calendar-times la-4x"></i>
            <h3>No Gigs for <?php echo htmlspecialchars($venueData['name']); ?></h3>
            <p>Add gigs to this venue using the "Add New Gig" button</p>
        </div>
    <?php endif; ?>
</div>
<?php $firstVenue = false; endforeach; ?>

<?php else: ?>
    <div class="no-lineup">
        <i class="las la-calendar-times la-4x"></i>
        <h3>No Gigs Yet</h3>
        <p>Click "Add New Gig" to create your first show</p>
    </div>
<?php endif; ?>
<?php endif; ?>

<!-- Quill WYSIWYG Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<style>
#support_acts_editor,
#age_restriction_content_editor {
    height: 200px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
}
#support_acts_editor:focus-within,
#age_restriction_content_editor:focus-within {
    border-color: #ff6b35;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}
.ql-toolbar {
    border-radius: 8px 8px 0 0;
}
.ql-container {
    border-radius: 0 0 8px 8px;
}
</style>

<script>
// Initialize Quill if the form exists
<?php if (isset($_GET['add']) || $editGig): ?>
var quillSupportActs = new Quill('#support_acts_editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            ['clean']
        ]
    },
    placeholder: 'Enter support act names...'
});

var quillAgeRestriction = new Quill('#age_restriction_content_editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            ['clean']
        ]
    },
    placeholder: 'Enter age restriction details...'
});

// Load existing content when editing
<?php if ($editGig && !empty($editGig['support_acts'])): ?>
quillSupportActs.clipboard.dangerouslyPasteHTML(<?php echo json_encode($editGig['support_acts']); ?>);
<?php endif; ?>

<?php if ($editGig && !empty($editGig['age_restriction_content'])): ?>
quillAgeRestriction.clipboard.dangerouslyPasteHTML(<?php echo json_encode($editGig['age_restriction_content']); ?>);
<?php endif; ?>

// Sync Quill content with hidden textareas on form submit
document.getElementById('gigForm').addEventListener('submit', function() {
    document.getElementById('support_acts').value = quillSupportActs.root.innerHTML;
    document.getElementById('age_restriction_content').value = quillAgeRestriction.root.innerHTML;
});
<?php endif; ?>

function previewImage(input) {
    const preview = document.getElementById('image_preview');
    const previewImg = document.getElementById('preview_img');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleTicketUrl() {
    const checkbox = document.getElementById('has_afterparty');
    const ticketUrlGroup = document.getElementById('ticket_url_group');
    ticketUrlGroup.style.display = checkbox.checked ? 'flex' : 'none';
}

function deleteGig(id, name) {
    if (confirm('Are you sure you want to delete the gig for ' + name + '? This will also remove the artist.')) {
        window.location.href = window.adminBasePath + 'gigs.php?delete=' + id;
    }
}

// Tab switching
document.addEventListener('DOMContentLoaded', function() {
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
