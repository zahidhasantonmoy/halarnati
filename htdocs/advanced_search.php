<?php
/**
 * Advanced Search Page
 */
include 'config.php';

// Get search parameters
$query = isset($_GET['query']) ? htmlspecialchars($_GET['query']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Build search query
$whereClauses = [];
$params = [];
$types = "";

if (!empty($query)) {
    $whereClauses[] = "(e.title LIKE ? OR e.text LIKE ?)";
    $params[] = '%' . $query . '%';
    $params[] = '%' . $query . '%';
    $types .= "ss";
}

if (!empty($type)) {
    $whereClauses[] = "e.type = ?";
    $params[] = $type;
    $types .= "s";
}

if (!empty($category)) {
    $whereClauses[] = "e.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

if (!empty($dateFrom)) {
    $whereClauses[] = "e.created_at >= ?";
    $params[] = $dateFrom;
    $types .= "s";
}

if (!empty($dateTo)) {
    $whereClauses[] = "e.created_at <= ?";
    $params[] = $dateTo;
    $types .= "s";
}

// Add visibility check
$whereClauses[] = "e.is_visible = 1";

// Build main query
$sql = "SELECT e.*, c.name as category_name, c.slug as category_slug, u.username 
        FROM entries e 
        LEFT JOIN categories c ON e.category_id = c.id 
        LEFT JOIN users u ON e.user_id = u.id";

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

// Add sorting
$allowedSortBy = ['created_at', 'title', 'view_count'];
if (!in_array($sortBy, $allowedSortBy)) {
    $sortBy = 'created_at';
}

$allowedSortOrder = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedSortOrder)) {
    $sortOrder = 'DESC';
}

$sql .= " ORDER BY e.{$sortBy} {$sortOrder} LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Execute search
$searchResults = $db->fetchAll($sql, $params, $types);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total 
             FROM entries e 
             LEFT JOIN categories c ON e.category_id = c.id 
             LEFT JOIN users u ON e.user_id = u.id";

if (!empty($whereClauses)) {
    // Remove LIMIT clause for count query
    $countParams = array_slice($params, 0, -2);
    $countTypes = substr($types, 0, -2);
    $countSql .= " WHERE " . implode(" AND ", $whereClauses);
    $totalResult = $db->fetch($countSql, $countParams, $countTypes);
} else {
    $totalResult = $db->fetch($countSql);
}

$totalEntries = $totalResult['total'] ?? 0;
$totalPages = ceil($totalEntries / $limit);

// Get categories for filter dropdown
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name ASC");

include 'header.php';
?>

<div class="main-wrapper">
    <div class="row g-0">
        <div class="col-12 col-lg-8 main-content-area mx-auto">
            <div class="container py-4">
                <h1 class="text-center mb-4">Advanced Search</h1>
                
                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-search"></i> Search Filters
                    </div>
                    <div class="card-body">
                        <form method="GET" action="advanced_search.php">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="query" class="form-label">Search Query</label>
                                    <input type="text" class="form-control" id="query" name="query" value="<?= htmlspecialchars($query) ?>" placeholder="Enter keywords...">
                                </div>
                                <div class="col-md-6">
                                    <label for="type" class="form-label">Entry Type</label>
                                    <select class="form-select" id="type" name="type">
                                        <option value="">All Types</option>
                                        <option value="text" <?= $type === 'text' ? 'selected' : '' ?>>Text</option>
                                        <option value="code" <?= $type === 'code' ? 'selected' : '' ?>>Code</option>
                                        <option value="file" <?= $type === 'file' ? 'selected' : '' ?>>File</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="sort_by" class="form-label">Sort By</label>
                                    <select class="form-select" id="sort_by" name="sort_by">
                                        <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Created Date</option>
                                        <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>Title</option>
                                        <option value="view_count" <?= $sortBy === 'view_count' ? 'selected' : '' ?>>View Count</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <select class="form-select" id="sort_order" name="sort_order">
                                        <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Descending</option>
                                        <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                                    <a href="advanced_search.php" class="btn btn-secondary">Clear Filters</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Search Results -->
                <?php if (isset($_GET['query']) || isset($_GET['type']) || isset($_GET['category']) || isset($_GET['date_from']) || isset($_GET['date_to'])): ?>
                    <h2 class="mb-3">Search Results</h2>
                    
                    <?php if (empty($searchResults)): ?>
                        <div class="alert alert-info text-center">
                            No entries found matching your criteria.
                        </div>
                    <?php else: ?>
                        <p class="text-muted"><?= $totalEntries ?> entries found</p>
                        
                        <?php foreach ($searchResults as $entry): ?>
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
                                        <small class="text-muted">Created: <?= $entry['created_at'] ?> by <?= htmlspecialchars($entry['username'] ?? 'Anonymous') ?></small>
                                        <span class="view-count"><i class="fas fa-eye"></i> Views: <?= $entry['view_count'] ?? 0 ?></span>
                                        <?php if (isset($entry['category_name']) && $entry['category_name']): ?>
                                            <span class="badge bg-secondary">
                                                <a href="category.php?slug=<?= $entry['category_slug'] ?>" class="text-white text-decoration-none">
                                                    <?= htmlspecialchars($entry['category_name']) ?>
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                    </p>

                                    <?php if ($entry['lock_key']): // Locked entry ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-lock"></i> This entry is password protected. View details to unlock.
                                        </div>
                                    <?php else: // Unlocked or no password ?>
                                        <div class="entry-content">
                                            <?php if ($entry['type'] === 'code'): ?>
                                                <pre><code class="language-<?= htmlspecialchars($entry['language'] ?? 'markup') ?>"><?= htmlspecialchars(substr($entry['text'], 0, 300)) ?><?= strlen($entry['text']) > 300 ? '...' : '' ?></code></pre>
                                            <?php elseif ($entry['type'] === 'file'): ?>
                                                <?php if ($entry['thumbnail']): ?>
                                                    <img src="<?= htmlspecialchars($entry['thumbnail']) ?>" alt="Thumbnail" class="img-thumbnail mb-2" style="max-height: 100px;">
                                                <?php endif; ?>
                                                <p><strong>Attached File:</strong> <?= htmlspecialchars(basename($entry['file_path'] ?? '')) ?></p>
                                                <a href="download.php?file=<?= urlencode(basename($entry['file_path'] ?? '')) ?>" class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-download"></i> Download File
                                                </a>
                                            <?php else: // Default to text ?>
                                                <p><?= nl2br(htmlspecialchars(substr($entry['text'], 0, 300))) ?><?= strlen($entry['text']) > 300 ? '...' : '' ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <a href="entry.php?<?= $entry['slug'] ? 'slug=' . htmlspecialchars($entry['slug']) : 'id=' . $entry['id'] ?>" class="btn btn-primary btn-sm mt-2">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Search results pagination">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?query=<?= urlencode($query) ?>&type=<?= urlencode($type) ?>&category=<?= urlencode($category) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?>&page=<?= $i ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <h4><i class="fas fa-info-circle"></i> Advanced Search</h4>
                        <p>Use the filters above to search for entries. You can search by keywords, entry type, category, date range, and more.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>