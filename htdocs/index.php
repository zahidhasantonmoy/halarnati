<?php
// Database connection
$host = 'sql203.infinityfree.com';
$user = 'if0_37868453';
$pass = 'Yho7V4gkz6bP1';
$db = 'if0_37868453_halarnati';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize notification messages
$notification = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_entry'])) {
    $title = htmlspecialchars($_POST['title']);
    $text = htmlspecialchars($_POST['text']);
    $file = $_FILES['file'];
    $lockKey = htmlspecialchars($_POST['lock_key'] ?? null);

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
    $stmt = $conn->prepare("INSERT INTO entries (title, text, file_path, lock_key, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $title, $text, $filePath, $lockKey);
    $stmt->execute();
    $stmt->close();

    $notification = "Entry successfully added!";
}





// Handle search functionality
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

// Handle unlock functionality
$unlockedEntries = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unlock_entry'])) {
    $entryId = (int)$_POST['entry_id'];
    $unlockKey = htmlspecialchars($_POST['unlock_key']);

    $stmt = $conn->prepare("SELECT * FROM entries WHERE id = ? AND lock_key = ?");
    $stmt->bind_param("is", $entryId, $unlockKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $unlockedEntries[$entryId] = $result->fetch_assoc();
        $notification = "Entry successfully unlocked!";
    } else {
        $notification = "Incorrect key. Please try again.";
    }
    $stmt->close();
}

// Pagination functionality
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch the entries for the current page
$result = $conn->query("SELECT * FROM entries ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$entries = $result->fetch_all(MYSQLI_ASSOC);

// Get total number of entries
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM entries");
$totalEntries = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalEntries / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File-er Jotil Dunia:Halarnati Upload Koro, Maza Koro!</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f3f4f6, #e3e8ef);
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            background: white;
            overflow: hidden;
        }
        .sidebar {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            padding: 30px;
        }
        .sidebar h2 {
            font-size: 24px;
            font-weight: bold;
        }
        .main-content {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            border-radius: 5px;
        }
        button {
            font-weight: bold;
            transition: all 0.3s;
        }
        button:hover {
            transform: scale(1.1);
        }
        .entry {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }
        .entry:hover {
            transform: translateY(-5px);
            transition: all 0.3s;
        }
        .pagination a {
            padding: 5px 10px;
            margin: 2px;
            border-radius: 5px;
        }
        .instructions {
            background: #eaf4ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .entry-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: #f70202; /* Default color */
    display: flex;
    align-items: center;
    transition: color 0.3s, transform 0.3s;
}

.entry-title i {
    transition: transform 0.3s, color 0.3s;
}

.entry-title:hover {
    color: #007bff; /* Hover color */
    transform: translateX(5px);
}

.entry-title:hover i {
    color: #0056b3; /* Icon hover color */
    transform: rotate(15deg); /* Slight rotation effect */
}

.title-text {
    transition: color 0.3s;
}

.entry:hover .title-text {
    color: #007bff; /* Hover effect on title text */
}

    </style>
</head>
<body>
<div class="container">
    <?php if ($notification): ?>
        <div class="alert alert-info text-center"><?= $notification ?></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-3 sidebar">
            <h2>ωєℓ¢σмє</h2>
        <p> ɪᴛs ᴀɴ ᴜᴘɢʀᴀᴅᴇ ᴠᴇʀsɪᴏɴ ᴏғ ʟᴏʜᴀʟᴀ ᴠ𝟷.𝟸 ᴇɪᴋʜᴀɴᴇ ᴀᴘɴɪ ғɪʟᴇ ᴜᴘʟᴏᴀᴅ ᴋᴏʀᴛᴇ ᴘᴀʀʙᴇɴ, ᴛᴇxᴛ sʜᴀʀᴇ ᴋᴏʀᴛᴇ ᴘᴀʀʙᴇɴ, ᴀʀ ʟɪғᴇ ᴇᴀsʏ ᴋᴏʀᴛᴇ ᴘᴀʀʙᴇɴ. ᴀʀ ɢᴏʟᴍᴀʟ ᴋᴏʀᴀʀ ᴅᴏʀᴋᴀʀ ɴᴀɪ!  </p>
         <p> 𝒜𝓁𝓁 𝓇𝒾𝑔𝒽𝓉𝓈 𝓇𝑒𝓈𝑒𝓇𝓋𝑒𝒹 𝒷𝓎 𝒵𝒶𝒽𝒾𝒹 ℋ𝒶𝓈𝒶𝓃</p>
    
   
        </div>


        <div class="col-md-9 main-content">
        <h1>Z྇@྇h྇i྇d྇ ྇C྇r྇e྇a྇t྇i྇o྇n྇</h1>

        <!-- Instructions -->
        <div class="instructions">
            <p style="color: #007bff; font-weight: bold;">নির্দেশনা:</p>
            <ul>
                <li>"Choose File" e click kore apnar file select korun.</li>
                <li>Title, text, ar file diye upload korun.</li>
                <li>Search bar diye jinish khujte parben.</li>
            </ul>
        </div>
            
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="text">Text</label>
                    <textarea id="text" name="text" rows="4" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="file">File</label>
                    <input type="file" id="file" name="file" class="form-control">
                </div>
                <div class="form-group">
                    <label for="lock_key"><b><h5>Lagaw Password (Optional)</h5></b></label>
                    <input type="text" id="lock_key" name="lock_key" class="form-control" placeholder="Set a password to lock">
                </div>
                <button type="submit" name="submit_entry" class="btn btn-primary"><i class="fas fa-upload"></i> Submit</button>
            </form>

            <form action="" method="post">
                <div class="form-group mt-4">
                    <input type="text" name="search_query" class="form-control" placeholder="Search entries">
                    <button type="submit" name="search" class="btn btn-success mt-2"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>

            <?php if (!empty($searchResults)): ?>
                <h2 class="mt-4">Search Results</h2>
                <?php foreach ($searchResults as $entry): ?>
                    <div class="entry">
                        <h3><?= htmlspecialchars($entry['title']) ?></h3>
                        <p><strong>Created:</strong> <?= $entry['created_at'] ?></p>
                        <textarea class="form-control" readonly><?= htmlspecialchars($entry['text']) ?></textarea>
                        <?php if ($entry['file_path']): ?>
                            <p><strong>File:</strong> <?= htmlspecialchars(basename($entry['file_path'])) ?></p>
                            <a href="<?= $entry['file_path'] ?>" class="btn btn-secondary" download>Download</a>
                        <?php endif; ?>
                        <button class="btn btn-info mt-2" onclick="copyText('<?= htmlspecialchars($entry['text']) ?>')"><i class="fas fa-copy"></i> Copy</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
 <h2 class="mt-4">Latest Entries</h2>
            <?php foreach ($entries as $entry): ?>
                <div class="entry my-3">
                    <h3 class="entry-title">
                        <i class="fas fa-file-alt"></i> <?= htmlspecialchars($entry['title']) ?>
                    </h3>
                    <p><strong>Created:</strong> <?= $entry['created_at'] ?></p>
                    <?php if (isset($unlockedEntries[$entry['id']])): ?>
                        <textarea class="form-control" readonly><?= htmlspecialchars($unlockedEntries[$entry['id']]['text']) ?></textarea>
                        <?php if ($unlockedEntries[$entry['id']]['file_path']): ?>
                            <p><strong>File:</strong> <?= htmlspecialchars(basename($unlockedEntries[$entry['id']]['file_path'])) ?></p>
                            <a href="<?= $unlockedEntries[$entry['id']]['file_path'] ?>" class="btn btn-secondary" download>Download</a>
                        <?php endif; ?>
                    <?php elseif ($entry['lock_key']): ?>
                        <form action="" method="post">
                            <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                            <input type="text" name="unlock_key" placeholder="Password Daw " class="form-control mb-2">
                            <button type="submit" name="unlock_entry" class="btn btn-warning">Unlock</button>
                        </form>
                    <?php else: ?>
                        <textarea class="form-control" readonly><?= htmlspecialchars($entry['text']) ?></textarea>
                        <?php if ($entry['file_path']): ?>
                            <p><strong>File:</strong> <?= htmlspecialchars(basename($entry['file_path'])) ?></p>
                            <a href="<?= $entry['file_path'] ?>" class="btn btn-secondary" download>Download</a>
                        <?php endif; ?>
                        <button class="btn btn-info mt-2" onclick="copyText('<?= htmlspecialchars($entry['text']) ?>')"><i class="fas fa-copy"></i> Copy</button>
                    <?php endif; ?>
                    <a href="entry.php?id=<?= $entry['id'] ?>" class="btn btn-primary mt-2">
                        <i class="fas fa-eye"></i> View Entry
                    </a>
                    <button class="btn btn-secondary mt-2" onclick="copyText('http://halarnati.000.pe/entry.php?id=<?= $entry['id'] ?>')">
                        <i class="fas fa-share-alt"></i> Share Link
                    </button>
                </div>
                
            <?php endforeach; ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="btn <?= $i == $page ? 'btn-primary' : 'btn-light' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function copyText(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Text copied to clipboard!');
    }
</script>
</body>
</html>
