<?php
/**
 * Displays entries by category.
 */
include 'config.php';

$slug = isset($_GET['slug']) ? htmlspecialchars($_GET['slug']) : null;

if (!$slug) {
    die("Category not found.");
}

// Fetch category details
$stmt = $conn->prepare("SELECT * FROM categories WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();
$stmt->close();

if (!$category) {
    die("Category not found.");
}

// Pagination functionality
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch entries for the current category and page
$stmt = $conn->prepare("SELECT e.*, c.name as category_name, c.slug as category_slug FROM entries e LEFT JOIN categories c ON e.category_id = c.id WHERE e.category_id = ? ORDER BY e.created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $category['id'], $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$entries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total number of entries for the category
$totalResult = $conn->prepare("SELECT COUNT(*) AS total FROM entries WHERE category_id = ?");
$totalResult->bind_param("i", $category['id']);
$totalResult->execute();
$totalEntries = $totalResult->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalEntries / $limit);
$totalResult->close();

include 'header.php';
?>

<div class="main-wrapper">
    <div class="row g-0">
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
                <h1 class="text-center mb-4">Category: <?= htmlspecialchars($category['name']) ?></h1>

                <?php if (empty($entries)): ?>
                    <div class="alert alert-warning text-center">No entries found in this category.</div>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
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
                                    <?php if ($entry['category_name']): ?>
                                        <span class="badge bg-secondary"><a href="category.php?slug=<?= $entry['category_slug'] ?>" class="text-white text-decoration-none"><?= htmlspecialchars($entry['category_name']) ?></a></span>
                                    <?php endif; ?>
                                </p>

                                <?php if ($entry['lock_key']): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lock"></i> This entry is password protected. View details to unlock.
                                    </div>
                                <?php else: ?>
                                    <div class="entry-content">
                                        <?php if ($entry['type'] === 'code'): ?>
                                            <pre><code class="language-<?= htmlspecialchars($entry['language'] ?? 'markup') ?>"><?= htmlspecialchars($entry['text']) ?></code></pre>
                                        <?php elseif ($entry['type'] === 'file'): ?>
                                            <p><strong>Attached File:</strong> <?= htmlspecialchars(basename($entry['file_path'] ?? '')) ?></p>
                                            <a href="download.php?file=<?= urlencode(basename($entry['file_path'] ?? '')) ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-download"></i> Download File
                                            </a>
                                        <?php else: ?>
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
                    <?php endforeach; ?>
                <?php endif; ?>

                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?slug=<?= $slug ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <!-- Right Sidebar -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-right">
            <div class="p-3">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-tags"></i> Categories
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php
                            // Fetch all categories for the sidebar
                            $categories_query_sidebar = "SELECT id, name, slug FROM categories ORDER BY name ASC";
                            $categories_result_sidebar = $conn->query($categories_query_sidebar);
                            $categories_sidebar = $categories_result_sidebar->fetch_all(MYSQLI_ASSOC);
                            foreach ($categories_sidebar as $category_sidebar):
                            ?>
                                <li class="list-group-item"><a href="category.php?slug=<?= $category_sidebar['slug'] ?>" class="text-decoration-none"><?= htmlspecialchars($category_sidebar['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
