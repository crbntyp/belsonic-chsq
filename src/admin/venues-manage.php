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
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $domain = $_POST['domain'] ?? '';
    $domain_aliases = $_POST['domain_aliases'] ?? '';
    $country = $_POST['country'] ?? '';
    $postcode = $_POST['postcode'] ?? '';

    // Convert empty string to NULL for integer fields
    $capacity = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null;

    $description = $_POST['description'] ?? '';

    // Maps
    $venue_map_url = $_POST['venue_map_url'] ?? '';
    $google_maps_api_key = $_POST['google_maps_api_key'] ?? '';

    // Pubs and Accommodation lists
    $pubs_list = $_POST['pubs_list'] ?? '';
    $accommodation_list = $_POST['accommodation_list'] ?? '';

    // Colors
    $primary_color = $_POST['primary_color'] ?? '#ff006f';
    $secondary_color = $_POST['secondary_color'] ?? '#00ffff';

    // Logo
    $logo_url = $_POST['logo_url'] ?? '';
    $logo_height = $_POST['logo_height'] ?? '60px';

    // Handle logo upload
    // Upload directly to dist/img/uploads/logos/ so files are immediately available
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        // When running from dist/admin/, go up to dist/, then into img/uploads/logos/
        $upload_dir = __DIR__ . '/../img/uploads/logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];

        if (in_array($file_extension, $allowed_extensions)) {
            $filename = 'logo_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $target_file)) {
                $logo_url = '/img/uploads/logos/' . $filename;
            }
        }
    }

    // Social Media
    $facebook_url = $_POST['facebook_url'] ?? '';
    $twitter_url = $_POST['twitter_url'] ?? '';
    $instagram_url = $_POST['instagram_url'] ?? '';

    // Background Images
    $bg_image_1 = $_POST['bg_image_1'] ?? '';
    $bg_image_2 = $_POST['bg_image_2'] ?? '';
    $bg_image_3 = $_POST['bg_image_3'] ?? '';
    $bg_image_4 = $_POST['bg_image_4'] ?? '';
    $bg_image_5 = $_POST['bg_image_5'] ?? '';

    // FAQs (stored separately in faqs table)
    $faqs = $_POST['faqs'] ?? [];

    try {
        if ($venue_id) {
            // UPDATE
            $db->prepare("
                UPDATE venues SET
                    name = ?, address = ?, city = ?, domain = ?, domain_aliases = ?, country = ?, postcode = ?,
                    capacity = ?, description = ?,
                    venue_map_url = ?, google_maps_api_key = ?, pubs_list = ?, accommodation_list = ?,
                    primary_color = ?, secondary_color = ?,
                    logo_url = ?, logo_height = ?,
                    facebook_url = ?, twitter_url = ?, instagram_url = ?,
                    bg_image_1 = ?, bg_image_2 = ?, bg_image_3 = ?, bg_image_4 = ?, bg_image_5 = ?
                WHERE id = ?
            ")->execute([
                $name, $address, $city, $domain, $domain_aliases, $country, $postcode,
                $capacity, $description,
                $venue_map_url, $google_maps_api_key, $pubs_list, $accommodation_list,
                $primary_color, $secondary_color,
                $logo_url, $logo_height,
                $facebook_url, $twitter_url, $instagram_url,
                $bg_image_1, $bg_image_2, $bg_image_3, $bg_image_4, $bg_image_5,
                $venue_id
            ]);

            // Handle FAQs
            // Delete all existing FAQs for this venue
            $db->prepare("DELETE FROM faqs WHERE venue_id = ?")->execute([$venue_id]);

            // Insert new FAQs
            if (!empty($faqs)) {
                $insertFaqStmt = $db->prepare("INSERT INTO faqs (venue_id, question, answer, sort_order) VALUES (?, ?, ?, ?)");
                foreach ($faqs as $index => $faq) {
                    if (!empty($faq['question']) && !empty($faq['answer'])) {
                        $insertFaqStmt->execute([$venue_id, $faq['question'], $faq['answer'], $index + 1]);
                    }
                }
            }
            $message = 'Venue updated successfully';
        } else {
            // INSERT
            $db->prepare("
                INSERT INTO venues (
                    name, address, city, domain, domain_aliases, country, postcode, capacity, description,
                    venue_map_url, google_maps_api_key, pubs_list, accommodation_list,
                    primary_color, secondary_color,
                    logo_url, logo_height,
                    facebook_url, twitter_url, instagram_url,
                    bg_image_1, bg_image_2, bg_image_3, bg_image_4, bg_image_5
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $name, $address, $city, $domain, $domain_aliases, $country, $postcode, $capacity, $description,
                $venue_map_url, $google_maps_api_key, $pubs_list, $accommodation_list,
                $primary_color, $secondary_color,
                $logo_url, $logo_height,
                $facebook_url, $twitter_url, $instagram_url,
                $bg_image_1, $bg_image_2, $bg_image_3, $bg_image_4, $bg_image_5
            ]);

            $new_venue_id = $db->lastInsertId();

            // Handle FAQs for new venue
            if (!empty($faqs)) {
                $insertFaqStmt = $db->prepare("INSERT INTO faqs (venue_id, question, answer, sort_order) VALUES (?, ?, ?, ?)");
                foreach ($faqs as $index => $faq) {
                    if (!empty($faq['question']) && !empty($faq['answer'])) {
                        $insertFaqStmt->execute([$new_venue_id, $faq['question'], $faq['answer'], $index + 1]);
                    }
                }
            }
            $message = 'Venue added successfully';
        }

        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error saving venue: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get venue for editing
$editVenue = null;
$editVenueFaqs = [];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM venues WHERE id = ?");
    $stmt->execute([$id]);
    $editVenue = $stmt->fetch();

    // Load FAQs for this venue
    $faqStmt = $db->prepare("SELECT * FROM faqs WHERE venue_id = ? ORDER BY sort_order ASC");
    $faqStmt->execute([$id]);
    $editVenueFaqs = $faqStmt->fetchAll();
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

<?php if (isset($_GET['add']) || $editVenue): ?>
    <div class="form-card">
        <h2>
            <i class="las la-map-marker-alt"></i>
            <?php echo $editVenue ? 'Edit Venue, ' . htmlspecialchars($editVenue['name']) : 'Add Venue'; ?>
        </h2>

        <form method="POST" class="gig-form" id="venueForm" enctype="multipart/form-data">
            <?php if ($editVenue): ?>
                <input type="hidden" name="venue_id" id="venue_id" value="<?php echo $editVenue['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="venue_id" id="venue_id" value="">
            <?php endif; ?>

            <!-- Form Tabs -->
            <div class="location-tabs">
                <button type="button" class="location-tab active" data-tab="basic">
                    <i class="las la-info-circle"></i>
                    Basic Info
                </button>
                <button type="button" class="location-tab" data-tab="location">
                    <i class="las la-map"></i>
                    Location
                </button>
                <button type="button" class="location-tab" data-tab="branding">
                    <i class="las la-palette"></i>
                    Branding
                </button>
                <button type="button" class="location-tab" data-tab="social">
                    <i class="lab la-instagram"></i>
                    Social
                </button>
                <button type="button" class="location-tab" data-tab="media">
                    <i class="las la-images"></i>
                    Media
                </button>
                <button type="button" class="location-tab" data-tab="faqs">
                    <i class="las la-question-circle"></i>
                    FAQs
                </button>
            </div>

            <!-- Basic Information Tab -->
            <div class="tab-content active" id="basic-tab">
                <div class="form-section">

                <!-- Two Column Layout -->
                <div class="form-row" style="gap: 2rem; align-items: flex-start;">

                    <!-- Left Column: Basic Info -->
                    <div style="flex: 1;">
                        <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--text-dark); font-size: 1.1rem;">
                            <i class="las la-info-circle"></i> Venue Details
                        </h3>

                        <div class="form-group">
                            <label for="name">Venue Name *</label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($editVenue['name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($editVenue['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address"
                                   value="<?php echo htmlspecialchars($editVenue['address'] ?? ''); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city"
                                       value="<?php echo htmlspecialchars($editVenue['city'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="postcode">Postcode</label>
                                <input type="text" id="postcode" name="postcode"
                                       value="<?php echo htmlspecialchars($editVenue['postcode'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country"
                                       value="<?php echo htmlspecialchars($editVenue['country'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="capacity">Capacity</label>
                                <input type="number" id="capacity" name="capacity"
                                       value="<?php echo htmlspecialchars($editVenue['capacity'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Domain Info -->
                    <div style="flex: 1;">
                        <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--text-dark); font-size: 1.1rem;">
                            <i class="las la-globe"></i> Domain Configuration
                        </h3>

                        <div class="form-group">
                            <label for="domain">
                                <i class="las la-globe"></i>
                                Primary Domain *
                            </label>
                            <input type="text" id="domain" name="domain"
                                   placeholder="e.g., belsonic.com"
                                   value="<?php echo htmlspecialchars($editVenue['domain'] ?? ''); ?>"
                                   required>

                        </div>

                        <div class="form-group">
                            <label for="domain_aliases">
                                <i class="las la-link"></i>
                                Additional Domains (Optional)
                            </label>
                            <input type="text" id="domain_aliases" name="domain_aliases"
                                   placeholder="e.g., chsq.com, customhouse.com"
                                   value="<?php echo htmlspecialchars($editVenue['domain_aliases'] ?? ''); ?>">

                        </div>

                        <div style="background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 4px; margin-top: 1.5rem;">
                            <strong style="display: block; margin-bottom: 0.5rem; color: #1e40af;">
                                <i class="las la-lightbulb"></i> How It Works
                            </strong>
                            <p style="margin: 0; font-size: 0.9rem; color: #1e40af; line-height: 1.5;">
                                When users visit any of these domains, the system automatically loads this venue's content, branding, and colors. No code changes needed! Any additional domains needed above should be separated by a comma!
                            </p>
                        </div>
                    </div>

                </div>

                </div>
            </div>

            <!-- Location Tab -->
            <div class="tab-content" id="location-tab">
                <div class="form-section">

                <div class="form-group">
                    <label for="venue_map_url">Venue Location Map Embed Code</label>
                    <textarea id="venue_map_url" name="venue_map_url" rows="3" placeholder="<iframe src=&quot;...&quot;></iframe>"><?php echo htmlspecialchars($editVenue['venue_map_url'] ?? ''); ?></textarea>
                    <small>Paste the full iframe embed code from Google Maps for the venue location</small>
                </div>

                <div class="form-group">
                    <label for="google_maps_api_key">Google Maps API Key (Optional)</label>
                    <input type="text" id="google_maps_api_key" name="google_maps_api_key" placeholder="AIza..."
                           value="<?php echo htmlspecialchars($editVenue['google_maps_api_key'] ?? ''); ?>">
                    <small>Required to show multiple pins on pubs/accommodation maps. <a href="https://developers.google.com/maps/documentation/embed/get-api-key" target="_blank">Get API key</a></small>
                </div>

                <h3 style="margin: 2rem 0 1.5rem 0; color: #ff006f;">Pubs & Accommodation</h3>

                <div class="form-group">
                    <label for="pubs_list">Pubs Close By</label>
                    <div id="pubs_list_editor"></div>
                    <textarea id="pubs_list" name="pubs_list" style="display: none;"><?php echo htmlspecialchars($editVenue['pubs_list'] ?? ''); ?></textarea>
                    <small>Add pubs and bars near the venue - each item will create a map pin</small>
                </div>

                <div class="form-group">
                    <label for="accommodation_list">Places to Stay Close By</label>
                    <div id="accommodation_list_editor"></div>
                    <textarea id="accommodation_list" name="accommodation_list" style="display: none;"><?php echo htmlspecialchars($editVenue['accommodation_list'] ?? ''); ?></textarea>
                    <small>Add hotels and accommodation near the venue - each item will create a map pin</small>
                </div>

                </div>
            </div>

            <!-- Branding Tab -->
            <div class="tab-content" id="branding-tab">
                <div class="form-section">

                <div class="form-group">
                    <label for="logo_file">Venue Logo - <span class="label-helper">upload logo image (jpg, png, gif, svg)</span></label>
                    <input type="file" id="logo_file" name="logo_file" accept="image/*" onchange="previewLogo(this)">
                    <input type="hidden" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars($editVenue['logo_url'] ?? ''); ?>">
                    <?php if (!empty($editVenue['logo_url'])): ?>
                    <div id="logo_preview" style="margin-top: 0.5rem;">
                        <img id="preview_logo" src="<?php echo htmlspecialchars(asset_url($editVenue['logo_url'])); ?>" style="max-width: 200px; max-height: 100px; padding: 10px; border-radius: 8px;">
                    </div>
                    <?php else: ?>
                    <div id="logo_preview" style="margin-top: 0.5rem; display: none;">
                        <img id="preview_logo" style="max-width: 200px; max-height: 100px; padding: 10px; border-radius: 8px;">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="logo_height">Logo Height (CSS)</label>
                    <input type="text" id="logo_height" name="logo_height" placeholder="60px"
                           value="<?php echo htmlspecialchars($editVenue['logo_height'] ?? '60px'); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="primary_color">
                            Primary Color
                            <span class="color-preview" id="primary_color_preview" style="background: <?php echo htmlspecialchars($editVenue['primary_color'] ?? '#ff006f'); ?>;"></span>
                        </label>
                        <input type="color" id="primary_color" name="primary_color"
                               value="<?php echo htmlspecialchars($editVenue['primary_color'] ?? '#ff006f'); ?>"
                               onchange="updateColorPreview('primary_color')">
                    </div>
                    <div class="form-group">
                        <label for="secondary_color">
                            Secondary Color
                            <span class="color-preview" id="secondary_color_preview" style="background: <?php echo htmlspecialchars($editVenue['secondary_color'] ?? '#00ffff'); ?>;"></span>
                        </label>
                        <input type="color" id="secondary_color" name="secondary_color"
                               value="<?php echo htmlspecialchars($editVenue['secondary_color'] ?? '#00ffff'); ?>"
                               onchange="updateColorPreview('secondary_color')">
                    </div>
                </div>
                </div>
            </div>

            <!-- Social Media Tab -->
            <div class="tab-content" id="social-tab">
                <div class="form-section">

                <div class="form-group">
                    <label for="facebook_url">Facebook URL</label>
                    <input type="url" id="facebook_url" name="facebook_url" placeholder="https://facebook.com/..."
                           value="<?php echo htmlspecialchars($editVenue['facebook_url'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="twitter_url">Twitter URL</label>
                    <input type="url" id="twitter_url" name="twitter_url" placeholder="https://twitter.com/..."
                           value="<?php echo htmlspecialchars($editVenue['twitter_url'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="instagram_url">Instagram URL</label>
                    <input type="url" id="instagram_url" name="instagram_url" placeholder="https://instagram.com/..."
                           value="<?php echo htmlspecialchars($editVenue['instagram_url'] ?? ''); ?>">
                </div>
                </div>
            </div>

            <!-- Media Tab -->
            <div class="tab-content" id="media-tab">
                <div class="form-section">
                <p>Upload up to 5 background images for the hero rotator. Drag and drop multiple files at once.</p>

                <!-- Single drop zone -->
                <div class="bg-dropzone-single" id="bgDropzone">
                    <i class="las la-cloud-upload-alt"></i>
                    <div>
                        <strong>Drop images here or click to browse</strong>
                        <p>Upload up to 5 images • JPG, PNG, GIF • Max 5MB each</p>
                        <span id="bgSlotCount" class="slot-count">0/5 slots used</span>
                    </div>
                </div>

                <!-- File input (hidden) -->
                <input type="file" id="bgFileInput" accept="image/jpeg,image/png,image/gif" multiple style="display: none;">

                <!-- Uploaded images row -->
                <div class="bg-images-row" id="bgImagesRow">
                    <!-- Images will be inserted here by JavaScript -->
                </div>

                <!-- Hidden URL inputs for form submission -->
                <input type="hidden" id="bg_image_1" name="bg_image_1">
                <input type="hidden" id="bg_image_2" name="bg_image_2">
                <input type="hidden" id="bg_image_3" name="bg_image_3">
                <input type="hidden" id="bg_image_4" name="bg_image_4">
                <input type="hidden" id="bg_image_5" name="bg_image_5">
                </div>
            </div>

            <!-- FAQs Tab -->
            <div class="tab-content" id="faqs-tab">
                <div class="form-section">
                <p>Manage frequently asked questions for the General Info page.</p>

                <div id="faqsContainer">
                    <!-- FAQs will be dynamically added here -->
                </div>

                <button type="button" class="btn btn-secondary" id="addFaqBtn">
                    <i class="las la-plus-circle"></i>
                    Add FAQ
                </button>

                </div>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="las la-save"></i>
                    <?php echo $editVenue ? 'Update' : 'Save'; ?> Venue
                </button>
                <a href="<?php echo admin_url('venues-manage.php'); ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="page-actions">
        <a href="<?php echo admin_url('venues-manage.php?add=1'); ?>" class="btn btn-primary">
            Add New Venue
        </a>
    </div>

    <section class="lineup">
        <div class="shows-grid">
            <?php foreach ($venues as $venue): ?>
            <article class="show-card">
                <div class="show-content">
                    <h3 class="main-artist"><?php echo htmlspecialchars($venue['name']); ?></h3>

                    <?php if (!empty($venue['primary_color']) || !empty($venue['secondary_color'])): ?>
                    <div style="display: flex; gap: 8px; margin: 16px 0; align-items: center; justify-content: center;">
                        <?php if (!empty($venue['primary_color'])): ?>
                        <span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: <?php echo htmlspecialchars($venue['primary_color']); ?>; border: 2px solid rgba(255,255,255,0.3);"></span>
                        <?php endif; ?>
                        <?php if (!empty($venue['secondary_color'])): ?>
                        <span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: <?php echo htmlspecialchars($venue['secondary_color']); ?>; border: 2px solid rgba(255,255,255,0.3);"></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="show-actions">
                        <a href="<?php echo admin_url('venues-manage.php?edit=' . $venue['id']); ?>" class="btn btn-icon btn-edit" title="Edit">
                            <i class="las la-edit"></i>
                        </a>
                        <button class="btn btn-icon btn-delete"
                                onclick="deleteVenue(<?php echo $venue['id']; ?>, '<?php echo htmlspecialchars($venue['name'], ENT_QUOTES); ?>');" title="Delete">
                            <i class="las la-trash"></i>
                        </button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Quill WYSIWYG Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<style>
#pubs_list_editor, #accommodation_list_editor {
    height: 200px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}
#pubs_list_editor:focus-within, #accommodation_list_editor:focus-within {
    border-color: #ff6b35;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}
</style>

<script>
// Background images array - initialize from PHP if editing
let backgroundImages = [];

<?php if ($editVenue): ?>
// Load existing background images when editing
<?php for ($i = 1; $i <= 5; $i++): ?>
    <?php if (!empty($editVenue["bg_image_$i"])): ?>
        backgroundImages.push(<?php echo json_encode($editVenue["bg_image_$i"]); ?>);
    <?php endif; ?>
<?php endfor; ?>
<?php endif; ?>

// Initialize Quill editors for pubs and accommodation
<?php if (isset($_GET['add']) || $editVenue): ?>
var pubsQuill = new Quill('#pubs_list_editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            ['clean']
        ]
    },
    placeholder: 'Add pubs and bars (e.g., The Duke of York, Kelly\'s Cellars...)'
});

var accommodationQuill = new Quill('#accommodation_list_editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            ['clean']
        ]
    },
    placeholder: 'Add hotels and accommodation (e.g., Europa Hotel, Titanic Hotel...)'
});

// Load existing content when editing
<?php if ($editVenue && !empty($editVenue['pubs_list'])): ?>
pubsQuill.clipboard.dangerouslyPasteHTML(<?php echo json_encode($editVenue['pubs_list']); ?>);
<?php endif; ?>

<?php if ($editVenue && !empty($editVenue['accommodation_list'])): ?>
accommodationQuill.clipboard.dangerouslyPasteHTML(<?php echo json_encode($editVenue['accommodation_list']); ?>);
<?php endif; ?>

// Sync Quill content with hidden textareas on form submit
document.getElementById('venueForm').addEventListener('submit', function() {
    document.getElementById('pubs_list').value = pubsQuill.root.innerHTML;
    document.getElementById('accommodation_list').value = accommodationQuill.root.innerHTML;
});
<?php endif; ?>

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    renderBackgroundImages();
    initDropzone();

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

function deleteVenue(id, name) {
    if (confirm('Are you sure you want to delete the venue "' + name + '"?')) {
        window.location.href = window.adminBasePath + 'venues-manage.php?delete=' + id;
    }
}

function updateColorPreview(inputId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(inputId + '_preview');
    if (input && preview) {
        preview.style.background = input.value;
    }
}

function previewLogo(input) {
    const preview = document.getElementById('logo_preview');
    const previewImg = document.getElementById('preview_logo');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize dropzone
function initDropzone() {
    const dropzone = document.getElementById('bgDropzone');
    const fileInput = document.getElementById('bgFileInput');

    // Click to browse
    dropzone.addEventListener('click', () => {
        fileInput.click();
    });

    // Drag and drop
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('dragover');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        const files = Array.from(e.dataTransfer.files);
        handleFiles(files);
    });

    // File input change
    fileInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        handleFiles(files);
        e.target.value = ''; // Reset input
    });
}

function handleFiles(files) {
    const availableSlots = 5 - backgroundImages.length;

    if (availableSlots === 0) {
        alert('❌ Upload failed: All 5 background slots are filled.\n\nPlease delete an existing image first.');
        return;
    }

    if (files.length === 0) {
        return;
    }

    // Validate each file and collect specific errors
    const validFiles = [];
    const errors = [];

    files.forEach(file => {
        // Check file type
        if (!file.type.match('image/(jpeg|png|gif)')) {
            errors.push(`❌ "${file.name}": Invalid file type (${file.type || 'unknown'}). Only JPG, PNG, and GIF are allowed.`);
            return;
        }

        // Check file size (5MB = 5 * 1024 * 1024 bytes)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            errors.push(`❌ "${file.name}": File too large (${sizeMB}MB). Maximum size is 5MB.`);
            return;
        }

        validFiles.push(file);
    });

    // Show specific error messages if any files were rejected
    if (errors.length > 0) {
        alert('Some files were rejected:\n\n' + errors.join('\n\n'));
    }

    if (validFiles.length === 0) {
        return;
    }

    // Limit to available slots
    const filesToUpload = validFiles.slice(0, availableSlots);

    if (validFiles.length > availableSlots) {
        alert(`⚠️ Only ${availableSlots} slot${availableSlots === 1 ? '' : 's'} available.\n\nUploading ${filesToUpload.length} of ${validFiles.length} selected files.`);
    }

    // Upload each file
    filesToUpload.forEach(file => uploadFile(file));
}

function uploadFile(file) {
    const formData = new FormData();
    formData.append('background_image', file);

    // Get venue_id from the hidden input field
    const venueId = document.getElementById('venue_id').value;

    if (!venueId) {
        alert('❌ Error: Please save the venue first before uploading images.');
        return;
    }

    formData.append('venue_id', venueId);

    fetch(window.adminBasePath + 'upload-background.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Server error');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            backgroundImages.push(data.url);
            renderBackgroundImages();
            console.log('✅ Successfully uploaded:', file.name);
        } else {
            alert('❌ Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('❌ Upload failed: ' + error.message);
        console.error('Upload error:', error);
    });
}

function renderBackgroundImages() {
    const row = document.getElementById('bgImagesRow');
    const slotCount = document.getElementById('bgSlotCount');

    // Update slot count
    slotCount.textContent = `${backgroundImages.length}/5 slots used`;

    // Clear and render images
    row.innerHTML = '';

    backgroundImages.forEach((imageUrl, index) => {
        const item = document.createElement('div');
        item.className = 'bg-image-item';
        // Prepend base path for subdirectory deployments
        const imagePath = imageUrl.startsWith('http') ? imageUrl : window.basePath + '/' + imageUrl.replace(/^\/+/, '');
        item.innerHTML = `
            <img src="${imagePath}" alt="Background ${index + 1}">
            <div class="bg-image-overlay">
                <button type="button" class="bg-delete-btn" onclick="deleteBackgroundImage(${index})">
                    Delete
                </button>
            </div>
        `;
        row.appendChild(item);
    });

    // Update hidden inputs
    for (let i = 1; i <= 5; i++) {
        const input = document.getElementById(`bg_image_${i}`);
        input.value = backgroundImages[i - 1] || '';
    }
}

function deleteBackgroundImage(index) {
    if (!confirm('Delete this background image?')) {
        return;
    }

    const imageUrl = backgroundImages[index];
    const venueId = document.getElementById('venue_id').value;

    if (!venueId) {
        alert('❌ Error: Venue ID not found. Please save the venue first before managing images.');
        return;
    }

    // Send delete request to server
    fetch(window.adminBasePath + 'delete-background.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            venue_id: venueId,
            image_url: imageUrl
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from local array
            backgroundImages.splice(index, 1);
            renderBackgroundImages();
        } else {
            alert('❌ Delete failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('❌ Delete failed: ' + error.message);
    });
}

// ===== FAQ Management =====
let faqIndex = 0;
const faqsContainer = document.getElementById('faqsContainer');
const addFaqBtn = document.getElementById('addFaqBtn');

// Load existing FAQs
const existingFaqs = <?php echo json_encode($editVenueFaqs); ?>;
existingFaqs.forEach((faq) => {
    addFaqField(faq.question, faq.answer);
});

// If no FAQs exist, add one empty FAQ
if (existingFaqs.length === 0) {
    addFaqField();
}

addFaqBtn.addEventListener('click', () => {
    addFaqField();
});

function addFaqField(question = '', answer = '') {
    const faqDiv = document.createElement('div');
    faqDiv.className = 'faq-item';

    faqDiv.innerHTML = `
        <button type="button" onclick="removeFaq(this)" class="faq-remove-btn">
            <i class="las la-trash"></i> Remove
        </button>

        <h4>FAQ ${faqIndex + 1}</h4>

        <div class="form-group">
            <label>Question</label>
            <input type="text"
                   name="faqs[${faqIndex}][question]"
                   placeholder="Enter FAQ question..."
                   value="${escapeHtml(question)}"
                   required>
        </div>

        <div class="form-group">
            <label>Answer</label>
            <textarea name="faqs[${faqIndex}][answer]"
                      rows="3"
                      placeholder="Enter FAQ answer..."
                      required>${escapeHtml(answer)}</textarea>
        </div>
    `;

    faqsContainer.appendChild(faqDiv);
    faqIndex++;
    updateFaqNumbers();
}

function removeFaq(btn) {
    if (confirm('Delete this FAQ?')) {
        btn.closest('.faq-item').remove();
        updateFaqNumbers();
    }
}

function updateFaqNumbers() {
    const faqItems = faqsContainer.querySelectorAll('.faq-item');
    faqItems.forEach((item, index) => {
        const heading = item.querySelector('h4');
        if (heading) {
            heading.textContent = `FAQ ${index + 1}`;
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include 'includes/footer.php'; ?>
