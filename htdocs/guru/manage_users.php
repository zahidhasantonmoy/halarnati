<?php
/**
 * Admin page for managing users.
 * Allows adding, editing, and deleting users.
 */
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add/Edit User
    if (isset($_POST['add_user']) || isset($_POST['edit_user_modal'])) {
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
        $username = htmlspecialchars($_POST['username']);
        $email = htmlspecialchars($_POST['email']);
        $password = $_POST['password'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;

        if (empty($username) || empty($email)) {
            $notification = "Username and Email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $notification = "Invalid email format.";
        } else {
            if ($user_id) { // Edit existing user
                $sql = "UPDATE users SET username = ?, email = ?, is_admin = ?";
                $params = "ssi";
                $bind_values = [$username, $email, $is_admin];

                if (!empty($password)) {
                    $sql .= ", password = ?";
                    $params .= "s";
                    $bind_values[] = password_hash($password, PASSWORD_DEFAULT);
                }
                $sql .= " WHERE id = ?";
                $params .= "i";
                $bind_values[] = $user_id;

                $affected_rows = $db->update($sql, $bind_values, $params);
                if ($affected_rows > 0) {
                    $notification = "User updated successfully.";
                    log_activity($_SESSION['user_id'], 'User Updated', 'User profile updated for: ' . $username . ' (ID: ' . $user_id . ')');
                } else {
                    $notification = "Error updating user: " . $db->getConnection()->error;
                }
            } else { // Add new user
                if (empty($password)) {
                    $notification = "Password is required for new users.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert_id = $db->insert("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)", [$username, $email, $hashed_password, $is_admin], "sssi");
                    if ($insert_id) {
                        $notification = "User added successfully.";
                        log_activity($_SESSION['user_id'], 'User Added', 'New user created: ' . $username);
                    } else {
                        $notification = "Error adding user: " . $db->getConnection()->error;
                    }
                }
            }
        }
    }

    // Handle Delete User
    if (isset($_POST['delete_user'])) {
        $user_id_to_delete = (int)$_POST['user_id'];

        // Fetch username before deleting for logging purposes
        $result_username = $db->fetch("SELECT username FROM users WHERE id = ?", [$user_id_to_delete], "i");
        $username_to_delete = $result_username['username'] ?? 'Unknown User';

        // Prevent admin from deleting themselves
        if ($user_id_to_delete === $_SESSION['user_id']) {
            $notification = "You cannot delete your own admin account.";
        } else {
            // Set user_id to NULL for entries owned by this user
            $db->update("UPDATE entries SET user_id = NULL WHERE user_id = ?", [$user_id_to_delete], "i");

            // Delete user
            $affected_rows = $db->delete("DELETE FROM users WHERE id = ?", [$user_id_to_delete], "i");
            if ($affected_rows > 0) {
                $notification = "User deleted successfully.";
                log_activity($_SESSION['user_id'], 'User Deleted', 'User deleted: ' . $username_to_delete . ' (ID: ' . $user_id_to_delete . ')');
            } else {
                $notification = "Error deleting user: " . $db->getConnection()->error;
            }
        }
    }
}

// Fetch all users
$users = $db->fetchAll("SELECT id, username, email, created_at, is_admin FROM users ORDER BY created_at DESC");

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
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/view_activity_logs.php" class="text-decoration-none text-white"><i class="fas fa-history me-2"></i> Activity Logs</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/error_log_viewer.php" class="text-decoration-none text-white"><i class="fas fa-bug me-2"></i> Error Log</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/guru/settings.php" class="text-decoration-none text-white"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li class="list-group-item bg-transparent border-0"><a href="/logout.php" class="text-decoration-none text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-8 main-content-area">
            <div class="container py-4">
                <h1 class="text-center mb-4">Manage Users</h1>
                <?php if ($notification): ?>
                    <div class="alert alert-info text-center"><?= $notification ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        Add New User
                    </div>
                    <div class="card-body">
                        <form action="manage_users.php" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="is_admin" name="is_admin">
                                <label class="form-check-label" for="is_admin">
                                    Make Admin
                                </label>
                            </div>
                            <button type="submit" name="add_user" class="btn btn-primary w-100">Add User</button>
                        </form>
                    </div>
                </div>

                <h2 class="mb-3">All Users</h2>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Admin</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= $user['is_admin'] ? 'Yes' : 'No' ?></td>
                                <td><?= $user['created_at'] ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm edit-user-btn" 
                                            data-id="<?= $user['id'] ?>" 
                                            data-username="<?= htmlspecialchars($user['username']) ?>" 
                                            data-email="<?= htmlspecialchars($user['email']) ?>" 
                                            data-is-admin="<?= $user['is_admin'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editUserModal">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user? All their entries will become anonymous.');">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm">
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

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="user_id" id="edit-user-id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-username" class="form-label">Username</label>
                        <input type="text" name="username" id="edit-username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-email" class="form-label">Email</label>
                        <input type="email" name="email" id="edit-email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" id="edit-password" class="form-control">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="edit-is-admin" name="is_admin">
                        <label class="form-check-label" for="edit-is-admin">
                            Make Admin
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_user_modal" class="btn btn-success">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const username = this.dataset.username;
            const email = this.dataset.email;
            const isAdmin = this.dataset.isAdmin;

            document.getElementById('edit-user-id').value = id;
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-is-admin').checked = (isAdmin == '1');
            document.getElementById('edit-password').value = ''; // Clear password field
        });
    });
</script>

<?php include '../footer.php'; ?>