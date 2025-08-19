<?php
include 'config.php';

$notification = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_entry'])) {
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $entry_type = htmlspecialchars($_POST['entry_type']);
    $language = ($entry_type === 'code') ? htmlspecialchars($_POST['language']) : '';
    $lockKey = htmlspecialchars($_POST['lock_key'] ?? null);
    $file = $_FILES['file'];

    $filePath = null;

    // Handle file upload if type is 'file'
    if ($entry_type === 'file' && $file['name']) {
        $uploadsDir = 'uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $filePath = $uploadsDir . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $filePath);
    }

    // Insert entry into the database
    $stmt = $conn->prepare("INSERT INTO entries (title, text, type, language, file_path, lock_key, created_at, view_count) VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)");
    $stmt->bind_param("ssssss", $title, $text, $entry_type, $language, $filePath, $lockKey);
    $stmt->execute();
    $stmt->close();

    $notification = "Entry successfully added!";
}

// Handle search functionality
$searchResults = [];
if (isset($_GET['search_query'])) {
    $searchQuery = htmlspecialchars($_GET['search_query']);
    $stmt = $conn->prepare("SELECT * FROM entries WHERE title LIKE ? OR text LIKE ? ORDER BY created_at DESC");
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
$result = $conn->query("SELECT * FROM entries ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$entries = $result->fetch_all(MYSQLI_ASSOC);

// Get total number of entries
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM entries");
$totalEntries = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalEntries / $limit);

// Get total view count for footer
$totalViewsResult = $conn->query("SELECT SUM(view_count) AS total_views FROM entries");
$totalViews = $totalViewsResult->fetch_assoc()['total_views'] ?? 0;

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
                        <label for="entry_type" class="form-label">Entry Type</label>
                        <select id="entry_type" name="entry_type" class="form-select" onchange="toggleFileType()">
                            <option value="text">Text</option>
                            <option value="code">Code</option>
                            <option value="file">File</option>
                        </select>
                    </div>
                    <div class="mb-3" id="language_field" style="display: none;">
                        <label for="language" class="form-label">Language (for Code)</label>
                        <select id="language" name="language" class="form-select">
                            <option value="php">PHP</option>
                            <option value="javascript">JavaScript</option>
                            <option value="css">CSS</option>
                            <option value="html">HTML</option>
                            <option value="sql">SQL</option>
                            <option value="python">Python</option>
                            <option value="markup">Markup (XML/HTML)</option>
                            <option value="clike">C-like (C, C++, Java, C#)</option>
                            <option value="">Other</option>
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
                    <button type="submit" name="submit_entry" class="btn btn-primary w-100"><i class="fas fa-upload"></i> Submit Entry</button>
                </form>
            </div>
        </div>

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

                        <a href="entry.php?id=<?= $entry['id'] ?>" class="btn btn-primary btn-sm mt-2">
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
                            <li class="list-group-item"><a href="#" class="text-decoration-none">Programming</a></li>
                            <li class="list-group-item"><a href="#" class="text-decoration-none">Documents</a></li>
                            <li class="list-group-item"><a href="#" class="text-decoration-none">Images</a></li>
                            <li class="list-group-item"><a href="#" class="text-decoration-none">Videos</a></li>
                            <li class="list-group-item"><a href="#" class="text-decoration-none">Other Files</a></li>
                            <li class="list-group-item"><a href="#" class="text-decoration-none">Empty Category (Link)</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Closing div for row g-0 -->
</div> <!-- Closing div for main-wrapper -->

<script>
    function toggleFileType() {
        const entryType = document.getElementById('entry_type').value;
        const languageField = document.getElementById('language_field');

        if (entryType === 'code') {
            languageField.style.display = 'block';
        } else {
            languageField.style.display = 'none';
        }
    }

    // Initial call to set correct display on page load
    document.addEventListener('DOMContentLoaded', toggleFileType);

    // Update total views in footer
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('total-views').innerText = '<?= $totalViews ?>';
    });
</script>

<?php include 'footer.php'; ?>