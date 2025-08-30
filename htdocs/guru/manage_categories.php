<?php
/** * Admin page for managing categories. * Allows adding, editing, and deleting categories. */
// Include your database connection, which now initializes $db
include_once '../config.php';

// Check if $db is properly initialized
if (!isset($db) || !is_object($db)) {
    die("Database connection failed. Please check your configuration.");
}

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin'])) {
    header("Location: ../login.php");
    exit;
}

$notification = "";

// Handle Add/Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_category']) || isset($_POST['edit_category_modal']))) {
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $name = htmlspecialchars($_POST['name']);
    $slug = htmlspecialchars($_POST['slug']);
    $slug = preg_replace('/[^a-z0-9-]+/', '', strtolower($slug)); // Sanitize slug

    if (empty($name) || empty($slug)) {
        $notification = "Category Name and Slug are required.";
    } else {
        if ($category_id) { // Edit existing category
            $affected_rows = $db->update("UPDATE categories SET name = ?, slug = ? WHERE id = ?", [$name, $slug, $category_id], "ssi");
            if ($affected_rows > 0) {
                $notification = "Category updated successfully.";
            } else {
                $notification = "Error updating category: " . $db->getConnection()->error;
            }
        } else {
            $insert_id = $db->insert("INSERT INTO categories (name, slug) VALUES (?, ?)", [$name, $slug], "ss");
            if ($insert_id) {
                $notification = "Category added successfully.";
            } else {
                $notification = "Error adding category: " . $db->getConnection()->error;
            }
        }
    }
}

// Handle Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id_to_delete = (int)$_POST['category_id'];

    // Remove associations from entry_categories table first
    $db->delete("DELETE FROM entry_categories WHERE category_id = ?", [$category_id_to_delete], "i");

    // Delete category
    $affected_rows = $db->delete("DELETE FROM categories WHERE id = ?", [$category_id_to_delete], "i");
    if ($affected_rows > 0) {
        $notification = "Category deleted successfully.";
    } else {
        $notification = "Error deleting category: " . $db->getConnection()->error;
    }
}

// Fetch all categories
$categories = $db->fetchAll("SELECT id, name, slug FROM categories ORDER BY name ASC");

include '../header.php'; // Use new header
?>

<div class="main-wrapper">
    <div class="row g-0">
        <!-- Left Sidebar (Admin Panel Navigation) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-left">
            <div class="p-3">
                <h5>Admin Panel</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/admin_dashboard.php" class="text-decoration-none text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/manage_users.php" class="text-decoration-none text-white"><i class="fas fa-users me-2"></i> Manage Users</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/manage_entries.php" class="text-decoration-none text-white"><i class="fas fa-list me-2"></i> Manage Entries</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/manage_categories.php" class="text-decoration-none text-white"><i class="fas fa-folder-open me-2"></i> Manage Categories</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/view_activity_logs.php" class="text-decoration-none text-white"><i class="fas fa-history me-2"></i> Activity Logs</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/analytics_dashboard.php" class="text-decoration-none text-white"><i class="fas fa-chart-bar me-2"></i> Analytics</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/error_log_viewer.php" class="text-decoration-none text-white"><i class="fas fa-bug me-2"></i> Error Log</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/settings.php" class="text-decoration-none text-white"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">Manage Categories</h1>
                <?php if ($notification): ?>
                    <div class="alert alert-info text-center"><?= $notification ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        Add New Category
                    </div>
                    <div class="card-body">
                        <form action="manage_categories.php" method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name</label>
                                <input type="text" id="name" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="slug" class="form-label">Category Slug</label>
                                <input type="text" id="slug" name="slug" class="form-control" required>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary w-100">Add Category</button>
                        </form>
                    </div>
                </div>

                <h2 class="mb-3">All Categories</h2>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= $category['id'] ?></td>
                                <td><?= htmlspecialchars($category['name']) ?></td>
                                <td><?= htmlspecialchars($category['slug']) ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm edit-category-btn" 
                                            data-id="<?= $category['id'] ?>" 
                                            data-name="<?= htmlspecialchars($category['name']) ?>" 
                                            data-slug="<?= htmlspecialchars($category['slug']) ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCategoryModal">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this category? All associated entries will lose this category.\');">
                                        <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                        <button type="submit" name="delete_category" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Sidebar (Placeholder for now) -->
        <div class="col-12 col-lg-2 d-none d-lg-block sidebar-right">
            <div class="p-3">
                <h5>Quick Links</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-transparent border-0"><a href="/index.php" class="text-decoration-none text-white">Go to Home</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/register.php" class="text-decoration-none text-white">Register New User</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="category_id" id="edit-category-id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">Category Name</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-slug" class="form-label">Category Slug</label>
                        <input type="text" name="slug" id="edit-slug" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_category_modal" class="btn btn-success">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.edit-category-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const slug = this.dataset.slug;

            document.getElementById('edit-category-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-slug').value = slug;
        });
    });
</script>

<?php include '../footer.php'; ?>