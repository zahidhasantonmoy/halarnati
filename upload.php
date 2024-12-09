<?php
if (isset($_POST['upload'])) {
    $uploadDir = 'uploads/';
    $fileName = basename($_FILES['file']['name']);
    $uploadPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
        header('Location: index.php?success=1');
    } else {
        header('Location: index.php?error=1');
    }
}
?>
