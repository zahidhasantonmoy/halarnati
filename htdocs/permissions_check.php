<?php
// Permissions check script
echo "<h1>File Permissions Check</h1>";

// Check current directory
echo "<p>Current directory: " . getcwd() . "</p>";

// Check if we can read the admin directory
$admin_dir = 'guru';
if (is_dir($admin_dir)) {
    echo "<p>✓ Admin directory exists</p>";
    
    // Check permissions
    $perms = fileperms($admin_dir);
    echo "<p>Admin directory permissions: " . substr(sprintf('%o', $perms), -4) . "</p>";
} else {
    echo "<p>✗ Admin directory does not exist</p>";
}

// Check if we can read the admin dashboard file
$admin_file = 'guru/admin_dashboard.php';
if (file_exists($admin_file)) {
    echo "<p>✓ Admin dashboard file exists</p>";
    
    // Check permissions
    $perms = fileperms($admin_file);
    echo "<p>Admin dashboard file permissions: " . substr(sprintf('%o', $perms), -4) . "</p>";
    
    // Check if file is readable
    if (is_readable($admin_file)) {
        echo "<p>✓ Admin dashboard file is readable</p>";
    } else {
        echo "<p>✗ Admin dashboard file is not readable</p>";
    }
    
    // Check if file is writable
    if (is_writable($admin_file)) {
        echo "<p>✓ Admin dashboard file is writable</p>";
    } else {
        echo "<p>✗ Admin dashboard file is not writable</p>";
    }
} else {
    echo "<p>✗ Admin dashboard file does not exist</p>";
}

// Check config file
$config_file = 'config.php';
if (file_exists($config_file)) {
    echo "<p>✓ Config file exists</p>";
    
    // Check permissions
    $perms = fileperms($config_file);
    echo "<p>Config file permissions: " . substr(sprintf('%o', $perms), -4) . "</p>";
    
    // Check if file is readable
    if (is_readable($config_file)) {
        echo "<p>✓ Config file is readable</p>";
    } else {
        echo "<p>✗ Config file is not readable</p>";
    }
} else {
    echo "<p>✗ Config file does not exist</p>";
}

// Check includes directory
$includes_dir = 'includes';
if (is_dir($includes_dir)) {
    echo "<p>✓ Includes directory exists</p>";
    
    // Check permissions
    $perms = fileperms($includes_dir);
    echo "<p>Includes directory permissions: " . substr(sprintf('%o', $perms), -4) . "</p>";
    
    // Check Database.php file
    $db_file = 'includes/Database.php';
    if (file_exists($db_file)) {
        echo "<p>✓ Database.php file exists</p>";
        
        // Check permissions
        $perms = fileperms($db_file);
        echo "<p>Database.php file permissions: " . substr(sprintf('%o', $perms), -4) . "</p>";
        
        // Check if file is readable
        if (is_readable($db_file)) {
            echo "<p>✓ Database.php file is readable</p>";
        } else {
            echo "<p>✗ Database.php file is not readable</p>";
        }
    } else {
        echo "<p>✗ Database.php file does not exist</p>";
    }
} else {
    echo "<p>✗ Includes directory does not exist</p>";
}

echo "<h2>Directory Listing</h2>";
echo "<p>Main directory contents:</p>";
echo "<ul>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>" . htmlspecialchars($file) . "</li>";
    }
}
echo "</ul>";

echo "<p>Admin directory contents:</p>";
if (is_dir('guru')) {
    echo "<ul>";
    $files = scandir('guru');
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>Admin directory not found</p>";
}
?>