<?php
/**
 * User dashboard.
 * Displays user's entries and stats.
 */
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// Fetch user's entries
$stmt = $conn->prepare("SELECT id, title, type, created_at, view_count, slug FROM entries WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_entries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user's stats (e.g., total entries, total views on their entries)
$total_user_entries = count($user_entries);
$total_user_views = 0;
foreach ($user_entries as $entry) {
    $total_user_views += $entry['view_count'];
}

include 'header.php';
?>

<div class="main-wrapper">
    <div class="row g-0">
        <!-- Left Sidebar (User Panel Navigation) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <div class="p-3">
                <h5>User Panel</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="user_panel.php" class="text-decoration-none text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="my_entries.php" class="text-decoration-none text-white"><i class="fas fa-list me-2"></i> My Entries</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="my_drive.php" class="text-decoration-none text-white"><i class="fas fa-hdd me-2"></i> My Drive</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="profile.php" class="text-decoration-none text-white"><i class="fas fa-user-cog me-2"></i> Profile Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area (User Dashboard) -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">Welcome, <?= $username ?>!</h1>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card text-center">
                            <div class="card-header">Total Entries</div>
                            <div class="card-body">
                                <h3 class="card-title"><?= $total_user_entries ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card text-center">
                            <div class="card-header">Total Views on My Entries</div>
                            <div class="card-body">
                                <h3 class="card-title"><?= $total_user_views ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <h2 class="mb-3">My Latest Entries</h2>
                <?php if (empty($user_entries)): ?>
                    <div class="alert alert-warning text-center">You haven't created any entries yet.</div>
                <?php else: ?>
                    <?php foreach ($user_entries as $entry): ?>
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
                                <a href="entry.php?<?= $entry['slug'] ? 'slug=' . htmlspecialchars($entry['slug']) : 'id=' . $entry['id'] ?>" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <a href="edit_entry.php?id=<?= $entry['id'] ?>" class="btn btn-info btn-sm mt-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_entry.php?id=<?= $entry['id'] ?>" class="btn btn-danger btn-sm mt-2" onclick="return confirm('Are you sure you want to delete this entry?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar (Placeholder for now) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-right">
            <div class="p-3">
                <h5>Quick Links</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="index.php" class="text-decoration-none text-white">Go to Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="register.php" class="text-decoration-none text-white">Register New User</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>