<?php
/**
 * API Base Controller
 */
class ApiController {
    protected $db;
    protected $method;
    protected $endpoint;
    protected $verb;
    protected $args = [];
    protected $file = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->method = $_SERVER['REQUEST_METHOD'];
        
        // Get request data
        if ($this->method == 'POST' && array_key_exists('HTTP_CONTENT_TYPE', $_SERVER)) {
            if (strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) {
                $this->file = json_decode(file_get_contents("php://input"), true);
            } else {
                $this->file = $_POST;
            }
        }
        
        // Parse URL
        $request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
        $this->endpoint = array_shift($request);
        $this->verb = array_shift($request);
        
        // Collect remaining URL parts
        $this->args = $request;
    }
    
    /**
     * Process the API request
     */
    public function processRequest() {
        try {
            // Check authentication for protected endpoints
            if (!$this->isPublicEndpoint() && !$this->checkAuth()) {
                $this->sendResponse(401, ['error' => 'Unauthorized']);
                return;
            }
            
            // Route to appropriate method
            switch ($this->endpoint) {
                case 'entries':
                    $this->handleEntries();
                    break;
                case 'users':
                    $this->handleUsers();
                    break;
                case 'categories':
                    $this->handleCategories();
                    break;
                default:
                    $this->sendResponse(404, ['error' => 'Endpoint not found']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, ['error' => 'Internal server error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Check if endpoint is public
     */
    private function isPublicEndpoint() {
        $publicEndpoints = ['entries'];
        return in_array($this->endpoint, $publicEndpoints);
    }
    
    /**
     * Check authentication
     */
    private function checkAuth() {
        // For now, check if user is logged in
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Handle entries endpoint
     */
    private function handleEntries() {
        switch ($this->method) {
            case 'GET':
                if ($this->verb) {
                    // Get specific entry
                    $this->getEntry($this->verb);
                } else {
                    // Get all entries
                    $this->getEntries();
                }
                break;
            case 'POST':
                // Create new entry
                $this->createEntry();
                break;
            case 'PUT':
                // Update entry
                if ($this->verb) {
                    $this->updateEntry($this->verb);
                } else {
                    $this->sendResponse(400, ['error' => 'Entry ID required']);
                }
                break;
            case 'DELETE':
                // Delete entry
                if ($this->verb) {
                    $this->deleteEntry($this->verb);
                } else {
                    $this->sendResponse(400, ['error' => 'Entry ID required']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }
    
    /**
     * Handle users endpoint
     */
    private function handleUsers() {
        switch ($this->method) {
            case 'GET':
                if ($this->verb) {
                    // Get specific user
                    $this->getUser($this->verb);
                } else {
                    // Get all users (admin only)
                    $this->getUsers();
                }
                break;
            case 'POST':
                // Create new user (registration)
                $this->createUser();
                break;
            case 'PUT':
                // Update user
                if ($this->verb) {
                    $this->updateUser($this->verb);
                } else {
                    $this->sendResponse(400, ['error' => 'User ID required']);
                }
                break;
            case 'DELETE':
                // Delete user
                if ($this->verb) {
                    $this->deleteUser($this->verb);
                } else {
                    $this->sendResponse(400, ['error' => 'User ID required']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }
    
    /**
     * Handle categories endpoint
     */
    private function handleCategories() {
        switch ($this->method) {
            case 'GET':
                if ($this->verb) {
                    // Get specific category
                    $this->getCategory($this->verb);
                } else {
                    // Get all categories
                    $this->getCategories();
                }
                break;
            case 'POST':
                // Create new category
                $this->createCategory();
                break;
            case 'PUT':
                // Update category
                if ($this->verb) {
                    $this->updateCategory($this->verb);
                } else {
                    $this->sendResponse(400, ['error' => 'Category ID required']);
                }
                break;
            case 'DELETE':
                // Delete category
                if ($this->verb) {
                    $this->deleteCategory($this->verb);
                } else {
                    $this->sendResponse(400, ['error' => 'Category ID required']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }
    
    /**
     * Get all entries
     */
    private function getEntries() {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $entries = $this->db->fetchAll(
            "SELECT id, title, type, language, slug, user_id, created_at, view_count, is_visible 
             FROM entries 
             WHERE is_visible = 1 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?", 
            [$limit, $offset], 
            "ii"
        );
        
        $this->sendResponse(200, $entries);
    }
    
    /**
     * Get specific entry
     */
    private function getEntry($id) {
        $entry = $this->db->fetch(
            "SELECT id, title, text, type, language, file_path, lock_key, slug, user_id, created_at, view_count, is_visible 
             FROM entries 
             WHERE id = ? AND is_visible = 1", 
            [$id], 
            "i"
        );
        
        if ($entry) {
            $this->sendResponse(200, $entry);
        } else {
            $this->sendResponse(404, ['error' => 'Entry not found']);
        }
    }
    
    /**
     * Create new entry
     */
    private function createEntry() {
        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(401, ['error' => 'Authentication required']);
            return;
        }
        
        $required = ['title', 'type'];
        foreach ($required as $field) {
            if (!isset($this->file[$field])) {
                $this->sendResponse(400, ['error' => "Missing required field: $field"]);
                return;
            }
        }
        
        $title = htmlspecialchars($this->file['title']);
        $type = $this->file['type'];
        $text = isset($this->file['text']) ? htmlspecialchars($this->file['text']) : '';
        $language = isset($this->file['language']) ? htmlspecialchars($this->file['language']) : '';
        $lockKey = isset($this->file['lock_key']) ? htmlspecialchars($this->file['lock_key']) : '';
        $slug = isset($this->file['slug']) ? htmlspecialchars($this->file['slug']) : bin2hex(random_bytes(5));
        $isVisible = isset($this->file['is_visible']) ? (int)$this->file['is_visible'] : 1;
        
        $insertId = $this->db->insert(
            "INSERT INTO entries (title, text, type, language, lock_key, slug, user_id, is_visible) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$title, $text, $type, $language, $lockKey, $slug, $_SESSION['user_id'], $isVisible],
            "ssssssii"
        );
        
        if ($insertId) {
            $entry = $this->db->fetch("SELECT * FROM entries WHERE id = ?", [$insertId], "i");
            $this->sendResponse(201, $entry);
        } else {
            $this->sendResponse(500, ['error' => 'Failed to create entry']);
        }
    }
    
    /**
     * Update entry
     */
    private function updateEntry($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(401, ['error' => 'Authentication required']);
            return;
        }
        
        // Check if user owns the entry or is admin
        $entry = $this->db->fetch("SELECT user_id FROM entries WHERE id = ?", [$id], "i");
        if (!$entry) {
            $this->sendResponse(404, ['error' => 'Entry not found']);
            return;
        }
        
        if ($entry['user_id'] != $_SESSION['user_id'] && !$_SESSION['is_admin']) {
            $this->sendResponse(403, ['error' => 'Forbidden']);
            return;
        }
        
        $fields = [];
        $params = [];
        $types = "";
        
        if (isset($this->file['title'])) {
            $fields[] = "title = ?";
            $params[] = htmlspecialchars($this->file['title']);
            $types .= "s";
        }
        
        if (isset($this->file['text'])) {
            $fields[] = "text = ?";
            $params[] = htmlspecialchars($this->file['text']);
            $types .= "s";
        }
        
        if (isset($this->file['type'])) {
            $fields[] = "type = ?";
            $params[] = $this->file['type'];
            $types .= "s";
        }
        
        if (isset($this->file['language'])) {
            $fields[] = "language = ?";
            $params[] = htmlspecialchars($this->file['language']);
            $types .= "s";
        }
        
        if (isset($this->file['lock_key'])) {
            $fields[] = "lock_key = ?";
            $params[] = htmlspecialchars($this->file['lock_key']);
            $types .= "s";
        }
        
        if (isset($this->file['slug'])) {
            $fields[] = "slug = ?";
            $params[] = htmlspecialchars($this->file['slug']);
            $types .= "s";
        }
        
        if (isset($this->file['is_visible'])) {
            $fields[] = "is_visible = ?";
            $params[] = (int)$this->file['is_visible'];
            $types .= "i";
        }
        
        if (empty($fields)) {
            $this->sendResponse(400, ['error' => 'No fields to update']);
            return;
        }
        
        $params[] = $id;
        $types .= "i";
        
        $query = "UPDATE entries SET " . implode(", ", $fields) . " WHERE id = ?";
        $affected = $this->db->update($query, $params, $types);
        
        if ($affected !== false) {
            $entry = $this->db->fetch("SELECT * FROM entries WHERE id = ?", [$id], "i");
            $this->sendResponse(200, $entry);
        } else {
            $this->sendResponse(500, ['error' => 'Failed to update entry']);
        }
    }
    
    /**
     * Delete entry
     */
    private function deleteEntry($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(401, ['error' => 'Authentication required']);
            return;
        }
        
        // Check if user owns the entry or is admin
        $entry = $this->db->fetch("SELECT user_id FROM entries WHERE id = ?", [$id], "i");
        if (!$entry) {
            $this->sendResponse(404, ['error' => 'Entry not found']);
            return;
        }
        
        if ($entry['user_id'] != $_SESSION['user_id'] && !$_SESSION['is_admin']) {
            $this->sendResponse(403, ['error' => 'Forbidden']);
            return;
        }
        
        // Delete associated file if it exists
        $fileInfo = $this->db->fetch("SELECT file_path FROM entries WHERE id = ?", [$id], "i");
        if ($fileInfo && $fileInfo['file_path'] && file_exists($fileInfo['file_path'])) {
            unlink($fileInfo['file_path']);
        }
        
        $affected = $this->db->delete("DELETE FROM entries WHERE id = ?", [$id], "i");
        
        if ($affected) {
            $this->sendResponse(200, ['message' => 'Entry deleted successfully']);
        } else {
            $this->sendResponse(500, ['error' => 'Failed to delete entry']);
        }
    }
    
    /**
     * Get all users (admin only)
     */
    private function getUsers() {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            $this->sendResponse(403, ['error' => 'Forbidden']);
            return;
        }
        
        $users = $this->db->fetchAll("SELECT id, username, email, created_at, is_admin FROM users ORDER BY created_at DESC");
        $this->sendResponse(200, $users);
    }
    
    /**
     * Get specific user
     */
    private function getUser($id) {
        $user = $this->db->fetch("SELECT id, username, email, created_at, is_admin FROM users WHERE id = ?", [$id], "i");
        
        if ($user) {
            $this->sendResponse(200, $user);
        } else {
            $this->sendResponse(404, ['error' => 'User not found']);
        }
    }
    
    /**
     * Create new user (registration)
     */
    private function createUser() {
        $required = ['username', 'email', 'password'];
        foreach ($required as $field) {
            if (!isset($this->file[$field])) {
                $this->sendResponse(400, ['error' => "Missing required field: $field"]);
                return;
            }
        }
        
        $username = htmlspecialchars($this->file['username']);
        $email = htmlspecialchars($this->file['email']);
        $password = $this->file['password'];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendResponse(400, ['error' => 'Invalid email format']);
            return;
        }
        
        // Validate password
        if (strlen($password) < 8) {
            $this->sendResponse(400, ['error' => 'Password must be at least 8 characters long']);
            return;
        }
        
        // Check if username or email already exists
        $existing = $this->db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email], "ss");
        if ($existing) {
            $this->sendResponse(409, ['error' => 'Username or email already exists']);
            return;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertId = $this->db->insert(
            "INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)",
            [$username, $email, $hashedPassword],
            "sss"
        );
        
        if ($insertId) {
            $user = $this->db->fetch("SELECT id, username, email, created_at FROM users WHERE id = ?", [$insertId], "i");
            $this->sendResponse(201, $user);
        } else {
            $this->sendResponse(500, ['error' => 'Failed to create user']);
        }
    }
    
    /**
     * Update user
     */
    private function updateUser($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(401, ['error' => 'Authentication required']);
            return;
        }
        
        // Check if user is updating their own profile or is admin
        if ($id != $_SESSION['user_id'] && !$_SESSION['is_admin']) {
            $this->sendResponse(403, ['error' => 'Forbidden']);
            return;
        }
        
        $fields = [];
        $params = [];
        $types = "";
        
        if (isset($this->file['username'])) {
            $fields[] = "username = ?";
            $params[] = htmlspecialchars($this->file['username']);
            $types .= "s";
        }
        
        if (isset($this->file['email'])) {
            $fields[] = "email = ?";
            $params[] = htmlspecialchars($this->file['email']);
            $types .= "s";
        }
        
        if (isset($this->file['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($this->file['password'], PASSWORD_DEFAULT);
            $types .= "s";
        }
        
        if (isset($this->file['is_admin']) && $_SESSION['is_admin']) {
            $fields[] = "is_admin = ?";
            $params[] = (int)$this->file['is_admin'];
            $types .= "i";
        }
        
        if (empty($fields)) {
            $this->sendResponse(400, ['error' => 'No fields to update']);
            return;
        }
        
        $params[] = $id;
        $types .= "i";
        
        $query = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $affected = $this->db->update($query, $params, $types);
        
        if ($affected !== false) {
            $user = $this->db->fetch("SELECT id, username, email, created_at, is_admin FROM users WHERE id = ?", [$id], "i");
            $this->sendResponse(200, $user);
        } else {
            $this->sendResponse(500, ['error' => 'Failed to update user']);
        }
    }
    
    /**
     * Delete user
     */
    private function deleteUser($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(401, ['error' => 'Authentication required']);
            return;
        }
        
        // Check if user is deleting their own account or is admin
        if ($id != $_SESSION['user_id'] && !$_SESSION['is_admin']) {
            $this->sendResponse(403, ['error' => 'Forbidden']);
            return;
        }
        
        // Prevent admin from deleting themselves if they're the only admin
        if ($id == $_SESSION['user_id'] && $_SESSION['is_admin']) {
            $adminCount = $this->db->fetch("SELECT COUNT(*) as count FROM users WHERE is_admin = 1", [], "")['count'];
            if ($adminCount <= 1) {
                $this->sendResponse(400, ['error' => 'Cannot delete the last admin account']);
                return;
            }
        }
        
        $affected = $this->db->delete("DELETE FROM users WHERE id = ?", [$id], "i");
        
        if ($affected) {
            $this->sendResponse(200, ['message' => 'User deleted successfully']);
        } else {
            $this->sendResponse(500, ['error' => 'Failed to delete user']);
        }
    }
    
    /**
     * Get all categories
     */
    private function getCategories() {
        $categories = $this->db->fetchAll("SELECT id, name, slug FROM categories ORDER BY name ASC");
        $this->sendResponse(200, $categories);
    }
    
    /**
     * Get specific category
     */
    private function getCategory($id) {
        $category = $this->db->fetch("SELECT id, name, slug FROM categories WHERE id = ?", [$id], "i");
        
        if ($category) {
            $this->sendResponse(200, $category);
        } else {
            $this->sendResponse(404, ['error' => 'Category not found']);
        }
    }
    
    /**
     * Create new category
     */
    private function createCategory() {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            $this->sendResponse(403, ['error' => 'Forbidden']);
            return;
        }
        
        $required = ['name', 'slug'];
        foreach ($required as $field) {
            if (!isset($this->file[$field])) {
                $this->sendResponse(400, ['error' => "Missing required field: $field"]);
                return;
            }
        }
        
        $name = htmlspecialchars($this->file['name']);
        $slug = preg_replace('/[^a-z0-9-]+/', '', strtolower($this->file['slug']));
        
        $insertId = $this->db->insert(
            "INSERT INTO categories (name, slug) VALUES (?, ?)",
            [$name, $slug],
            "ss"
        );
        
        if ($insertId) {
            $category = $this->db->fetch("SELECT id, name, slug FROM categories WHERE id = ?", [$insertId], "i");
            $this->sendResponse(201, $category);
        } else {
            $this->sendResponse(500, ['error' => 'Failed to create category']);
        }
    }
    
    /**
     * Update category
     */
    private function updateCategory($id) {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            $this->sendResponse(403, ['error' => 'Forbidden']);
            return;
        }
        
        $fields = [];
        $params = [];
        $types = "";
        
        if (isset($this->file['name'])) {
            $fields[] = "name = ?";
            $params[] = htmlspecialchars($this->file['name']);
            $types .= "s";
        }
        
        if (isset($this->file['slug'])) {
            $fields[] = "slug = ?";
            $params[] = preg_replace('/[^a-z0-9-]+/', '', strtolower($this->file['slug']));
            $types .= "s";
        }
        
        if (empty($fields)) {
            $this->sendResponse(400, ['error' => 'No fields to update']);
            return;
        }
        
        $params[] = $id;
        $types .= "i";
        
        $query = "UPDATE categories SET " . implode(", ", $fields) . " WHERE id = ?";
        $affected = $this->db->update($query, $params, $types);
        
        if ($affected !== false) {
            $category = $this->db->fetch("SELECT id, name, slug FROM categories WHERE id = ?", [$id], "i");
            $this->sendResponse(200, $category);
        } else {
            $this->sendResponse(500, ['error' => 'Failed to update category']);
        }
    }
    
    /**
     * Delete category
     */
    private function deleteCategory($id) {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            $this->sendResponse(403, ['error' => 'Forbidden']);
            return;
        }
        
        // Remove associations from entry_categories table first
        $this->db->delete("DELETE FROM entry_categories WHERE category_id = ?", [$id], "i");
        
        $affected = $this->db->delete("DELETE FROM categories WHERE id = ?", [$id], "i");
        
        if ($affected) {
            $this->sendResponse(200, ['message' => 'Category deleted successfully']);
        } else {
            $this->sendResponse(500, ['error' => 'Failed to delete category']);
        }
    }
    
    /**
     * Send JSON response
     */
    protected function sendResponse($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>