<?php
/**
 * The main page of the application.
 * Handles entry creation, displays the latest entries, and includes search functionality.
 */
session_start(); // Start session
include 'config.php';

$notification = "";

// Handle form submission for creating a new entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_entry'])) {
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $language = htmlspecialchars($_POST['language'] ?? '');
    $category_id = (int)$_POST['category_id'];
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

    // Handle file upload
    if ($entry_type === 'file' && $file['name']) {
        $uploadsDir = 'uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $filePath = $uploadsDir . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $filePath);
    }

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

    // Insert entry into the database
    $stmt = $conn->prepare("INSERT INTO entries (title, text, type, file_path, lock_key, slug, user_id, category_id, created_at, view_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)");
    $stmt->bind_param("ssssssii", $title, $text, $entry_type, $filePath, $lockKey, $customSlug, $user_id, $category_id);
    $stmt->execute();
    $stmt->close();

    $notification = "Entry successfully added!";
    log_activity($user_id, 'Entry Created', 'New entry titled: ' . $title . ' (ID: ' . $conn->insert_id . ')');
}

// Handle search functionality
$searchResults = [];
if (isset($_GET['search_query'])) {
    $searchQuery = htmlspecialchars($_GET['search_query']);
    $stmt = $conn->prepare("SELECT e.*, c.name as category_name, c.slug as category_slug FROM entries e LEFT JOIN categories c ON e.category_id = c.id WHERE e.title LIKE ? OR e.text LIKE ? ORDER BY e.created_at DESC");
    $likeQuery = '%' . $searchQuery . '%';
    $stmt->bind_param("ss", $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $searchResults = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Pagination functionality
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch the entries for the current page
$stmt = $conn->prepare("SELECT e.*, c.name as category_name, c.slug as category_slug FROM entries e LEFT JOIN categories c ON e.category_id = c.id ORDER BY e.created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$entries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total number of entries
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM entries");
$totalEntries = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalEntries / $limit);

// Get total view count for footer
$totalViewsResult = $conn->query("SELECT SUM(view_count) AS total_views FROM entries");
$totalViews = $totalViewsResult->fetch_assoc()['total_views'] ?? 0;

// Fetch all categories
$categories_query = "SELECT id, name, slug FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>

<div class="main-wrapper"><div class="row g-0">
            <!-- Left Sidebar -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <div class="p-3">
                <h5>Navigation</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="index.php" class="text-decoration-none text-white"><i class="fas fa-home me-2"></i> Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="#" class="text-decoration-none text-white"><i class="fas fa-plus-circle me-2"></i> Add New</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="#" class="text-decoration-none text-white"><i class="fas fa-search me-2"></i> Search</a></li>
                </ul>
            </div>
        </div>
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
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
                <form action="" method="post" enctype="multipart/form-data">
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
                    <div class="mb-3">
                        <label for="lock_key" class="form-label">Password (Optional)</label>
                        <input type="text" id="lock_key" name="lock_key" class="form-control" placeholder="Set a password to lock">
                    </div>
                    <button type="submit" name="submit_entry" class="btn btn-primary w-100"><i class="fas fa-paste"></i> Paste</button>
                </form>
            </div>
        </div>

        <div class="mb-3">
            <label for="custom_slug" class="form-label">Custom Link (Optional)</label>
            <input type="text" id="custom_slug" name="custom_slug" class="form-control" placeholder="e.g., my-awesome-paste">
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
                <form action="" method="get">
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