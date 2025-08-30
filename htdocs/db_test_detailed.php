<?php
/**
 * Database Connection and Query Test
 */
include 'config.php';

echo "<h1>Database Connection and Query Test</h1>";

// Test 1: Database connection
echo "<h2>Test 1: Database Connection</h2>";
if (isset($db) && is_object($db)) {
    echo "<p style='color: green;'>✓ Database object exists</p>";
    
    try {
        $conn = $db->getConnection();
        if ($conn) {
            echo "<p style='color: green;'>✓ Database connection successful</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to get database connection</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Exception occurred: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Database object does not exist</p>";
}

// Test 2: Simple query
echo "<h2>Test 2: Simple Query</h2>";
try {
    $result = $db->fetch("SELECT 1 as test");
    if ($result) {
        echo "<p style='color: green;'>✓ Simple query successful: " . $result['test'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Simple query failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception occurred: " . $e->getMessage() . "</p>";
}

// Test 3: Categories table query
echo "<h2>Test 3: Categories Table Query</h2>";
try {
    $categories = $db->fetchAll("SELECT id, name, slug FROM categories ORDER BY name ASC");
    if ($categories !== false) {
        echo "<p style='color: green;'>✓ Categories query successful</p>";
        echo "<p>Found " . count($categories) . " categories:</p>";
        echo "<ul>";
        foreach ($categories as $category) {
            echo "<li>" . htmlspecialchars($category['name']) . " (" . $category['slug'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Categories query returned false</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception occurred: " . $e->getMessage() . "</p>";
}

// Test 4: Entries table query
echo "<h2>Test 4: Entries Table Query</h2>";
try {
    $entries = $db->fetchAll("SELECT id, title FROM entries ORDER BY created_at DESC LIMIT 5");
    if ($entries !== false) {
        echo "<p style='color: green;'>✓ Entries query successful</p>";
        echo "<p>Found " . count($entries) . " recent entries:</p>";
        echo "<ul>";
        foreach ($entries as $entry) {
            echo "<li>" . htmlspecialchars($entry['title']) . " (ID: " . $entry['id'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Entries query returned false</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception occurred: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Completed</h2>";
echo "<p>If you see this message, all tests have completed.</p>";
?>