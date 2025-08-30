<?php
/**
 * The main page of the application.
 * Handles entry creation, displays the latest entries, and includes search functionality.
 */
// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// Add debug output at the very beginning
echo "<!-- DEBUG: Starting index.php execution -->
";

include 'config.php';
include 'includes/ImageHelper.php';

echo "<!-- DEBUG: Included config.php and ImageHelper.php -->
";

// Database connection test - only show for admin users
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    echo "<!-- Database Connection Test -->
";
    echo "<div style='background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin: 10px; border-radius: 5px;'>
";
    echo "<h3>Database Connection Test (Admin Only)</h3>
";
    
    if (isset($db) && is_object($db)) {
        echo "<p>✓ Database object exists</p>
";
        
        try {
            $conn = $db->getConnection();
            if ($conn) {
                echo "<p>✓ Database connection successful</p>
";
                
                // Try a simple query
                $result = $db->fetch("SELECT 1 as test");
                if ($result) {
                    echo "<p>✓ Database query successful: " . $result['test'] . "</p>
";
                } else {
                    echo "<p>✗ Database query failed</p>
";
                }
            } else {
                echo "<p>✗ Failed to get database connection</p>
";
            }
        } catch (Exception $e) {
            echo "<p>✗ Exception occurred: " . $e->getMessage() . "</p>
";
        }
    } else {
        echo "<p>✗ Database object does not exist</p>
";
    }
    
    echo "</div>
";
    echo "<!-- End Database Connection Test -->
";
}

echo "<!-- DEBUG: Passed database test section -->
";


$notification = "";

echo "<!-- DEBUG: Starting form handling section -->\n";

// Handle form submission for creating a new entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_entry'])) {
    echo "<!-- DEBUG: Processing form submission -->\n";
    
    $title = htmlspecialchars($_POST['title']);
    $text = $_POST['text'];
    $language = htmlspecialchars($_POST['language'] ?? '');
    $category_id = (int)$_POST['category_id'];
    $is_markdown = isset($_POST['is_markdown']) ? 1 : 0;
    $entry_type = 'text'; // Default to text

    if (!empty($_FILES['file']['name'])) {
        $entry_type = 'file';
    } elseif (!empty($language)) { // If a language is selected, assume it's code
        $entry_type = 'code';
    }

    $lockKey = htmlspecialchars($_POST['lock_key'] ?? null);
    $customSlug = htmlspecialchars($_POST['custom_slug'] ?? '');
    if (empty($customSlug)) {
        $customSlug = bin2hex(random_bytes(5)); // Generates a 10-character random hex string
    } else {
        $customSlug = preg_replace('/[^a-z0-9-]+/', '', strtolower($customSlug));
    }

    $file = $_FILES['file'];
    $filePath = null;
    $thumbnailPath = null;

    // Define allowed file types and max size
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']; // Add more as needed
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx']; // Add more as needed
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    // Handle file upload
    if ($entry_type === 'file' && $file['name']) {
        echo "<!-- DEBUG: Processing file upload -->\n";
        
        // Validate file size
        if ($file['size'] > $maxFileSize) {
            $notification = "File size exceeds the maximum allowed limit (5MB).";
            $entry_type = 'text'; // Revert to text type if file upload fails
        } else {
            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($mimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
                $notification = "Invalid file type. Only images (JPG, PNG, GIF), PDF, and text/document files are allowed.";
                $entry_type = 'text'; // Revert to text type if file upload fails
            } else {
                $uploadsDir = 'uploads/';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }
                // Generate a unique filename
                $newFileName = uniqid('file_', true) . '.' . $fileExtension;
                $filePath = $uploadsDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // If the uploaded file is an image, create a thumbnail
                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $thumbnailDir = 'uploads/thumbnails/';
                        if (!is_dir($thumbnailDir)) {
                            mkdir($thumbnailDir, 0777, true);
                        }
                        $thumbnailPath = $thumbnailDir . $newFileName;
                        ImageHelper::createThumbnail($filePath, $thumbnailPath);
                    }
                } else {
                    $notification = "Error uploading file.";
                    $entry_type = 'text'; // Revert to text type if file upload fails
                }
            }
        }
    }

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

    // Insert entry into the database
    echo "<!-- DEBUG: Inserting entry into database -->\n";
    $insert_id = $db->insert("INSERT INTO entries (title, text, type, file_path, thumbnail, lock_key, slug, user_id, category_id, is_markdown, created_at, view_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)", [$title, $text, $entry_type, $filePath, $thumbnailPath, $lockKey, $customSlug, $user_id, $category_id, $is_markdown], "sssssssiis");

    $notification = "Entry successfully added!";
    log_activity($user_id, 'Entry Created', 'New entry titled: ' . $title . ' (ID: ' . $insert_id . ')');
}

echo "<!-- DEBUG: Finished form handling section -->\n";

// Handle search functionality
echo "<!-- DEBUG: Starting search functionality -->\n";
$searchResults = [];
if (isset($_GET['search_query'])) {
    $searchQuery = htmlspecialchars($_GET['search_query']);
    $likeQuery = '%' . $searchQuery . '%';
    $searchResults = $db->fetchAll("SELECT e.*, c.name as category_name, c.slug as category_slug FROM entries e LEFT JOIN categories c ON e.category_id = c.id WHERE e.title LIKE ? OR e.text LIKE ? ORDER BY e.created_at DESC", [$likeQuery, $likeQuery], "ss");
}

echo "<!-- DEBUG: Finished search functionality -->\n";

// Pagination functionality
echo "<!-- DEBUG: Starting pagination functionality -->\n";
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch the entries for the current page
$entries = $db->fetchAll("SELECT e.*, c.name as category_name, c.slug as category_slug FROM entries e LEFT JOIN categories c ON e.category_id = c.id ORDER BY e.created_at DESC LIMIT ? OFFSET ?", [$limit, $offset], "ii");

// Get total number of entries
$totalResult = $db->fetch("SELECT COUNT(*) AS total FROM entries");
$totalEntries = $totalResult['total'];
$totalPages = ceil($totalEntries / $limit);

// Get total view count for footer
$totalViewsResult = $db->fetch("SELECT SUM(view_count) AS total_views FROM entries");
$totalViews = $totalViewsResult['total_views'] ?? 0;

echo "<!-- DEBUG: Finished pagination functionality -->\n";

// Fetch all categories
echo "<!-- DEBUG: Starting category fetch -->\n";
$categories = $db->fetchAll("SELECT id, name, slug FROM categories ORDER BY name ASC");
echo "<!-- DEBUG: Finished category fetch -->\n";

include 'header.php';
echo "<!-- DEBUG: Included header.php -->\n";
?>

// Handle search functionality
$searchResults = [];
if (isset($_GET['search_query'])) {
    $searchQuery = htmlspecialchars($_GET['search_query']);
    $likeQuery = '%' . $searchQuery . '%';
    $searchResults = $db->fetchAll("SELECT e.*, c.name as category_name, c.slug as category_slug FROM entries e LEFT JOIN categories c ON e.category_id = c.id WHERE e.title LIKE ? OR e.text LIKE ? ORDER BY e.created_at DESC", [$likeQuery, $likeQuery], "ss");
}

// Pagination functionality
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch the entries for the current page
$entries = $db->fetchAll("SELECT e.*, c.name as category_name, c.slug as category_slug FROM entries e LEFT JOIN categories c ON e.category_id = c.id ORDER BY e.created_at DESC LIMIT ? OFFSET ?", [$limit, $offset], "ii");

// Get total number of entries
$totalResult = $db->fetch("SELECT COUNT(*) AS total FROM entries");
$totalEntries = $totalResult['total'];
$totalPages = ceil($totalEntries / $limit);

// Get total view count for footer
$totalViewsResult = $db->fetch("SELECT SUM(view_count) AS total_views FROM entries");
$totalViews = $totalViewsResult['total_views'] ?? 0;

// Fetch all categories
$categories = $db->fetchAll("SELECT id, name, slug FROM categories ORDER BY name ASC");

include 'header.php';
?>

<div class="main-wrapper">
    <!-- DEBUG: Starting main wrapper -->
    <div class="row g-0">
        <!-- DEBUG: Starting row -->
        <!-- Left Sidebar -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <!-- DEBUG: Starting left sidebar -->
            <div class="p-3">
                <h5>Navigation</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="index.php" class="text-decoration-none text-white"><i class="fas fa-home me-2"></i> Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="#" class="text-decoration-none text-white"><i class="fas fa-plus-circle me-2"></i> Add New</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="#" class="text-decoration-none text-white"><i class="fas fa-search me-2"></i> Search</a></li>
                </ul>
            </div>
            <!-- DEBUG: Ending left sidebar -->
        </div>
        <div class="col-12 col-lg-8 main-content-area">
            <!-- DEBUG: Starting main content area -->
            <div class="container py-4">
                <!-- DEBUG: Starting container -->
        <?php if ($notification): ?>
            <div class="alert alert-info text-center"><?= $notification ?></div>
        <?php endif; ?>

        <h1 class="text-center mb-4">Z྇@྇h྇i྇d྇ ྇C྇r྇e྇a྇t྇i྇o྇n྇</h1>

        <!-- Instructions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Instructions
            </div>
            <div class="card-body">
                <p style="color: #607D8B; font-weight: bold;">নির্দেশনা:</p>
                <ul>
                    <li>"Choose File" e click kore apnar file select korun.</li>
                    <li>Title, text, ar file diye upload korun.</li>
                    <li>Search bar diye jinish khujte parben.</li>
                </ul>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Create New Entry
            </div>
            <div class="card-body">
                <form action="index.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select id="category" name="category_id" class="form-select" required>
                            <?php if (empty($categories)): ?>
                                <option value="" disabled>No categories available</option>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    
                    <div class="mb-3" id="file_upload_field">
                        <label for="file" class="form-label">Upload File</label>
                        <input type="file" id="file" name="file" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="text" class="form-label">Content (Text or Code)</label>
                        <textarea id="text" name="text" rows="8" class="form-control"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="is_markdown" name="is_markdown">
                        <label class="form-check-label" for="is_markdown">
                            Enable Markdown
                        </label>
                    </div>
                    <div class="mb-3">
                        <label for="lock_key" class="form-label">Password (Optional)</label>
                        <input type="text" id="lock_key" name="lock_key" class="form-control" placeholder="Set a password to lock">
                    </div>
                    <div class="mb-3">
                        <label for="custom_slug" class="form-label">Custom Link (Optional)</label>
                        <input type="text" id="custom_slug" name="custom_slug" class="form-control" placeholder="e.g., my-awesome-paste">
                    </div>
                    <button type="submit" name="submit_entry" class="btn btn-primary w-100"><i class="fas fa-paste"></i> Paste</button>
                </form>
            </div>
        </div>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="alert alert-info text-center mb-4">
                Please <a href="login.php">login</a> or <a href="register.php">register</a> to get more features like editing and deleting your entries.
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-search"></i> Search Entries
            </div>
            <div class="card-body">
                <form action="index.php" method="get">
                    <div class="input-group">
                        <input type="text" name="search_query" class="form-control" placeholder="Search entries" value="<?= htmlspecialchars($_GET['search_query'] ?? '') ?>">
                        <button type="submit" class="btn btn-info"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>
            </div>
        </div>

        <h2 class="mb-3">Latest Entries</h2>
        <?php
        $displayEntries = !empty($searchResults) ? $searchResults : $entries;
        if (empty($displayEntries)):
        ?>
            <div class="alert alert-warning text-center">No entries found.</div>
        <?php
        else:
            foreach ($displayEntries as $entry):
        ?>
                <div class="card entry-card mb-3">
                    <div class="card-body">
                        <h5 class="card-title entry-title">
                            <?php
                            if ($entry['type'] === 'code') {
                                echo '<i class="fas fa-code"></i> Code: ';
                            } elseif ($entry['type'] === 'file') {
                                echo '<i class="fas fa-file"></i> File: ';
                            } else {
                                echo '<i class="fas fa-align-left"></i> Text: ';
                            }
                            ?><?= htmlspecialchars($entry['title']) ?>
                        </h5>
                        <p class="card-text entry-meta">
                            <small class="text-muted">Created: <?= $entry['created_at'] ?></small>
                            <span class="view-count"><i class="fas fa-eye"></i> Views: <?= $entry['view_count'] ?? 0 ?></span>
                            <?php if (isset($entry['category_name']) && $entry['category_name']): ?>
                                <span class="badge bg-secondary"><a href="category.php?slug=<?= $entry['category_slug'] ?>" class="text-white text-decoration-none"><?= htmlspecialchars($entry['category_name']) ?></a></span>
                            <?php endif; ?>
                        </p>

                        <?php if ($entry['lock_key']): // Locked entry ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-lock"></i> This entry is password protected. View details to unlock.
                            </div>
                        <?php else: // Unlocked or no password ?>
                            <div class="entry-content">
                                <?php if ($entry['type'] === 'code'): ?>
                                    <pre><code class="language-<?= htmlspecialchars($entry['language'] ?? 'markup') ?>"><?= htmlspecialchars($entry['text']) ?></code></pre>
                                <?php elseif ($entry['type'] === 'file'): ?>
                                    <?php if ($entry['thumbnail']): ?>
                                        <img src="<?= htmlspecialchars($entry['thumbnail']) ?>" alt="Thumbnail" class="img-thumbnail mb-2">
                                    <?php endif; ?>
                                    <p><strong>Attached File:</strong> <?= htmlspecialchars(basename($entry['file_path'] ?? '')) ?></p>
                                    <a href="download.php?file=<?= urlencode(basename($entry['file_path'] ?? '')) ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-download"></i> Download File
                                    </a>
                                <?php else: // Default to text ?>
                                    <p><?= nl2br(htmlspecialchars($entry['text'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-info btn-sm mt-2" onclick="copyToClipboard('entry-text-<?= $entry['id'] ?>')">
                                <i class="fas fa-copy"></i> Copy Content
                            </button>
                            <textarea id="entry-text-<?= $entry['id'] ?>" style="position: absolute; left: -9999px;" readonly><?= htmlspecialchars($entry['text']) ?></textarea>
                        <?php endif; ?>

                        <a href="entry.php?<?= $entry['slug'] ? 'slug=' . htmlspecialchars($entry['slug']) : 'id=' . $entry['id'] ?>" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
        <?php
            endforeach;
        endif;
        ?>

        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['search_query']) ? '&search_query=' . htmlspecialchars($_GET['search_query']) : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div> <!-- Closing div for container py-4 -->
        </div> <!-- Closing div for main-content-area -->

    <!-- Right Sidebar -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-right">
            <div class="p-3">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-tags"></i> Categories
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($categories as $category): ?>
                                <li class="list-group-item"><a href="category.php?slug=<?= $category['slug'] ?>" class="text-decoration-none"><?= htmlspecialchars($category['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Closing div for row g-0 -->
</div> <!-- Closing div for main-wrapper -->

<script>
    // Update total views in footer
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('total-views').innerText = '<?= $totalViews ?>';
    });
</script>

<?php include 'footer.php'; ?>
