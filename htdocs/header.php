<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halar Nati</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-home"></i> Halar Nati
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="fas fa-list"></i> View Entries</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="entry.php"><i class="fas fa-plus-circle"></i> Add Entry</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="upload.php"><i class="fas fa-upload"></i> Upload File</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin/login.php"><i class="fas fa-user-shield"></i> Admin</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
