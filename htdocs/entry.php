<?php
// Database connection
$host = 'sql203.infinityfree.com';
$user = 'if0_37868453';
$pass = 'Yho7V4gkz6bP1';
$db = 'if0_37868453_halarnati';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get entry ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    die("Invalid entry ID.");
}

// Fetch entry from the database
$stmt = $conn->prepare("SELECT * FROM entries WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$entry = $result->fetch_assoc();
$stmt->close();

if (!$entry) {
    die("Entry not found.");
}

// Handle unlock request
$isUnlocked = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unlock_key'])) {
    $unlockKey = htmlspecialchars($_POST['unlock_key']);
    if ($unlockKey === $entry['lock_key']) {
        $isUnlocked = true;
    } else {
        $error = "Incorrect unlock key.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Entry - Halarnati</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef2f3, #8e9eab);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background: #35495e;
            color: white;
            padding: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .navbar a {
            text-decoration: none;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            font-family: 'Poppins', sans-serif;
        }
        .navbar a:hover {
            color: #42b883;
            text-shadow: 0 0 5px rgba(66, 184, 131, 0.8);
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 50px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        h1 {
            font-size: 4rem;
            color: #35495e;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            position: relative;
            display: inline-block;
            padding: 20px 30px;
            margin: 0 auto;
        }
        h1:after {
            content: "";
            display: block;
            width: 80%;
            height: 6px;
            background: #42b883;
            margin: 15px auto 0;
            border-radius: 3px;
        }
        .btn {
            border-radius: 50px;
            transition: all 0.3s ease-in-out;
        }
        .btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .form-label {
            font-weight: bold;
        }
        textarea {
            border: 2px solid #42b883;
        }
        .info-box {
            background: #f9fbfc;
            border-left: 5px solid #42b883;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .info-box p {
            margin: 0;
        }
        .download-info {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #555;
        }
    </style>
</head>
<body>
   
    
    <div class="container">
        <div class="info-box">
            <p><strong>Instructions:</strong></p>
            <ul>
                <li>"Unlock Key" enter kore content unlock korun.</li>
                <li>Text copy korar jonno "Copy Text" button click korun.</li>
                <li>Download button diye file download korun.</li>
            </ul>
        </div>
        <h1><?= htmlspecialchars($entry['title']) ?></h1>
        <p><strong>Created on:</strong> <?= $entry['created_at'] ?></p>
        <?php if ($entry['lock_key'] && !$isUnlocked): ?>
            <form method="post" class="mt-4">
                <div class="mb-3">
                    <label for="unlock_key" class="form-label">Unlock Key:</label>
                    <input type="text" id="unlock_key" name="unlock_key" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning w-100"><i class="fas fa-lock"></i> Unlock</button>
                <?php if (isset($error)): ?>
                    <p class="text-danger mt-3"><?= $error ?></p>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="mt-4">
                <label for="entry-text" class="form-label">Content:</label>
                <textarea id="entry-text" class="form-control" rows="5" readonly><?= htmlspecialchars($entry['text']) ?></textarea>
                <button class="btn btn-primary mt-3" onclick="copyText('<?= htmlspecialchars($entry['text']) ?>')">
                    <i class="fas fa-copy"></i> Copy Text
                </button>
            </div>
            <?php if ($entry['file_path']): ?>
                <div class="mt-3">
                    <p><strong>Attached File:</strong> <?= htmlspecialchars(basename($entry['file_path'])) ?></p>
                    <a href="http://halarnati.000.pe/<?= htmlspecialchars($entry['file_path']) ?>" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-download"></i> Download File
                    </a>
                    <p class="download-info">Downloaded <?= $entry['download_count'] ?? 0 ?> times</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <footer class="text-center mt-5">
        <p>&copy; <?= date("Y") ?> Halarnati | All rights reserved.</p>
    </footer>
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
