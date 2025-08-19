<?php
// Database connection
$host = 'sql202.infinityfree.com';
$user = 'if0_37593963';
$pass = 'E0C7NcfVtR3j7A';
$db = 'if0_37593963_file';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_entry'])) {
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $file = $_FILES['file'];

    $filePath = null;

    // Handle file upload
    if ($file['name']) {
        $uploadsDir = 'uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $filePath = $uploadsDir . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $filePath);
    }

    // Insert entry into the database
    $stmt = $conn->prepare("INSERT INTO entries (title, text, file_path, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $title, $text, $filePath);
    $stmt->execute();
    $stmt->close();
}

// Handle search
$searchResults = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $searchQuery = htmlspecialchars($_POST['search_query']);
    $stmt = $conn->prepare("SELECT * FROM entries WHERE title LIKE ? OR text LIKE ? ORDER BY created_at DESC");
    $likeQuery = '%' . $searchQuery . '%';
    $stmt->bind_param("ss", $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $searchResults = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch the last 20 entries
$result = $conn->query("SELECT * FROM entries ORDER BY created_at DESC LIMIT 20");
$entries = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload & Download</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f4f9;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .sidebar {
            background: #007bff;
            color: white;
            padding: 20px;
            width: 30%;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            
            box-sizing: border-box;
        }
        .sidebar h2 {
            color: #ffdd00;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        .sidebar p {
            line-height: 1.6;
            font-size: 1.1rem;
            color: #fff;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            box-sizing: border-box;
        }
        h1 {
            color: #007bff;
            font-size: 2rem;
        }
        .instructions {
            background: #eaf4ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .instructions ul {
            padding-left: 20px;
        }
        .instructions ul li {
            margin-bottom: 10px;
            color: #333;
            font-size: 1rem;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            color: #007bff;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            box-sizing: border-box;
        }
        button:hover {
            background-color: #0056b3;
        }
        .entry {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fdfdfd;
        }
        .entry-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #28a745;
        }
        .entry-timestamp {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        .entry-text textarea {
            width: 100%;
            height: auto;
            border: 1px solid #ccc;
            padding: 10px;
            resize: none;
            font-family: Arial, sans-serif;
        }
        .entry-file {
            margin-top: 10px;
            font-size: 0.95rem;
        }
        .entry-file a {
            color: #007bff;
            text-decoration: none;
        }
        .entry-file a:hover {
            text-decoration: underline;
        }
        .copy-btn {
            margin-top: 10px;
            padding: 5px 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .copy-btn:hover {
            background-color: #218838;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                text-align: center;
                padding: 10px;
            }
            .main-content {
                padding: 10px;
            }
        }
























    </style>
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>স্বাগতম!</h2>
        <p>Eikhane apni file upload korte parben, text share korte parben, ar life easy korte parben. Ar golmal korar dorkar nai!</p>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <h1>Zahid-er Projukti</h1>






















        <!-- Instructions -->
        <div class="instructions">
            <p style="color: #007bff; font-weight: bold;">নির্দেশনা:</p>
            <ul>
                <li>"Choose File" e click kore apnar file select korun.</li>
                <li>Title, text, ar file diye upload korun.</li>
                <li>Search bar diye jinish khujte parben.</li>
            </ul>
        </div>

        <!-- Upload Form -->
        <div class="upload-form">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" placeholder="Apnar title den" required>
                </div>
                <div class="form-group">
                    <label for="text">Text</label>
                    <textarea name="text" id="text" placeholder="Apnar text likhun" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="file">Upload File</label>
                    <input type="file" name="file" id="file">
                </div>
                <button type="submit" name="submit_entry">Upload Korun</button>
            </form>
        </div>















<div class="container">
        <h2>Search Entries</h2>
        <form action="" method="POST">
            <div class="form-group">
                <label for="search_query">Search by Title or Text</label>
                <input type="text" id="search_query" name="search_query" placeholder="Enter keywords" required>
            </div>
            <button type="submit" name="search">Search</button>
        </form>
    </div>

    <?php if (!empty($searchResults)): ?>
        <div class="container entries">
            <h2>Search Results</h2>
            <?php foreach ($searchResults as $entry): ?>
                <div class="entry">
                    <div class="entry-title"><?= htmlspecialchars($entry['title']) ?></div>
                    <div class="entry-timestamp">Published on: <?= htmlspecialchars($entry['created_at']) ?></div>
                    <div class="entry-text">
                        <textarea readonly><?= htmlspecialchars($entry['text']) ?></textarea>
                    </div>
                    <?php if (!empty($entry['file_path'])): ?>
                        <div class="entry-file">
                            <a href="<?= htmlspecialchars($entry['file_path']) ?>" target="_blank">Download File</a>
                        </div>
                    <?php endif; ?>
                    <button class="copy-btn" onclick="copyToClipboard(this)">Copy Text</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>











        <!-- Recent Entries -->
        <h2 style="color: #28a745;">সর্বশেষ ২০ টি এন্ট্রি</h2>
        <?php foreach ($entries as $entry): ?>
            <div class="entry">
                <div class="entry-title"><?= htmlspecialchars($entry['title']) ?></div>
                <div class="entry-timestamp">Upload kora hoyeche: <?= htmlspecialchars($entry['created_at']) ?></div>
                <div class="entry-text">
                    <textarea readonly><?= htmlspecialchars($entry['text']) ?></textarea>
                </div>
                <?php if (!empty($entry['file_path'])): ?>
                    <div class="entry-file">
                        <strong>File:</strong> 
                        <span><?= basename($entry['file_path']) ?></span> |
                        <a href="<?= htmlspecialchars($entry['file_path']) ?>" target="_blank">Download File</a>
                    </div>


<script>
    function copyToClipboard(button) {
        const entry = button.closest('.entry');
        const textArea = entry.querySelector('textarea');
        const textContent = textArea.value;

        navigator.clipboard.writeText(textContent).then(() => {
            alert(`Copied text: \n${textContent}`);
        }).catch(err => {
            alert("Failed to copy!");
        });
    }
</script>




                <?php endif; ?>
                <button class="copy-btn" onclick="copyToClipboard(this)">Text Copy Korun</button>
            </div>
        <?php endforeach; ?>













        <!-- File List Table -->
        <h2>Uploaded Files</h2>
        <table class="file-table">
            <tr>
                <th>File Name</th>
                <th>Size (KB)</th>
                <th>Upload Date</th>
                <th>Action</th>
            </tr>
            <?php
            $files = glob('uploads/*');
            if (count($files) > 0) {
                foreach ($files as $file) {
                    $fileName = basename($file);
                    $fileSize = round(filesize($file) / 1024, 2);
                    $fileDate = date("Y-m-d H:i:s", filemtime($file));
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($fileName) . '</td>';
                    echo '<td>' . $fileSize . '</td>';
                    echo '<td>' . $fileDate . '</td>';
                    echo '<td><a href="download.php?file=' . urlencode($fileName) . '">Download</a></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4">No files uploaded yet.</td></tr>';
            }
            ?>
        </table>










    </div>
</div>












</body>
</html>
