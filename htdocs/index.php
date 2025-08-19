<?php
require_once 'db.php';
require_once 'header.php';

// Search functionality
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$likeSearchQuery = '%' . $searchQuery . '%';

// Pagination functionality
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base queries
$sql = "SELECT * FROM entries WHERE is_visible = 1";
$countSql = "SELECT COUNT(*) AS total FROM entries WHERE is_visible = 1";

// Append search condition if a search query exists
if (!empty($searchQuery)) {
    $sql .= " AND (title LIKE ? OR text LIKE ?)";
    $countSql .= " AND (title LIKE ? OR text LIKE ?)";
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

// Fetch total number of entries for pagination
$countStmt = $conn->prepare($countSql);
if (!empty($searchQuery)) {
    $countStmt->bind_param("ss", $likeSearchQuery, $likeSearchQuery);
}
$countStmt->execute();
$totalEntries = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalEntries / $limit);
$countStmt->close();

// Fetch the entries for the current page
$stmt = $conn->prepare($sql);
if (!empty($searchQuery)) {
    $stmt->bind_param("ssii", $likeSearchQuery, $likeSearchQuery, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Latest Entries</h1>
        <form action="index.php" method="get" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search for entries..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <?php if (count($entries) > 0): ?>
        <?php foreach ($entries as $entry):
            // Ensure that the lock_key is treated as a string, even if it's null or not set.
            $lockKey = $entry['lock_key'] ?? '';
        ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-file-alt text-primary"></i> <?= htmlspecialchars($entry['title']) ?></h5>
                        <p class="card-subtitle mb-2 text-muted">Created: <?= date('F j, Y, g:i a', strtotime($entry['created_at'])) ?></p>
                        
                        <?php if ($lockKey !== ''):
                            // Check if the entry is actually locked by comparing the provided lock_key with the stored one.
                            // For demonstration, we'll assume it's locked if lock_key is present.
                            // In a real scenario, you'd compare $entry['lock_key'] with a user-provided key.
                        ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-lock"></i> This content is locked.
                            </div>
                        <?php else:
                            // Display a snippet of the text content, ensuring it's properly escaped and truncated.
                        ?>
                            <p class="card-text"><?= nl2br(htmlspecialchars(substr($entry['text'], 0, 150))) . (strlen($entry['text']) > 150 ? '...' : '') ?></p>
                        <?php endif; ?>

                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                         <a href="entry.php?id=<?= $entry['id'] ?>" class="btn btn-primary"><i class="fas fa-eye"></i> View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else:
        // Display a message if no entries are found.
    ?>
        <div class="col-12">
            <div class="alert alert-info">No entries found.</div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1):
    // Render pagination links if there are multiple pages.
?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++):
            // Highlight the current page.
        ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '' ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once 'footer.php'; ?>