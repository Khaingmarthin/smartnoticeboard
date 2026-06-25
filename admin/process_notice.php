<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../config/db.php';


if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    $fileExt = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));

    if (in_array($fileExt, $allowed)) {
        // Use a unique name to prevent collisions
        $newName = uniqid('notice_') . '.' . $fileExt;
        $destination = '../uploads/' . $newName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
            // Now save $newName into your 'attachments' table
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction(); // Transaction: Ensures both steps succeed or both fail

        // 1. Insert Notice
        $stmt = $pdo->prepare("INSERT INTO notices (title, content, user_id, status, publish_date, created_at, updated_at, is_urgent) 
                               VALUES (?, ?, ?, 'published', NOW(), NOW(), NOW(), ?)");
        $stmt->execute([
            $_POST['title'],
            $_POST['content'],
            $_SESSION['user_id'],
            isset($_POST['is_urgent']) ? 1 : 0
        ]);
        $notice_id = $pdo->lastInsertId();

        // 2. Handle Attachment (if uploaded)
        if (!empty($_FILES['attachment']['name'])) {
            $upload_dir = '../uploads/';
            $new_filename = time() . '_' . basename($_FILES['attachment']['name']);

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $new_filename)) {
                $stmt = $pdo->prepare("INSERT INTO attachments (notice_id, file_path, original_name) VALUES (?, ?, ?)");
                $stmt->execute([$notice_id, $new_filename, $_FILES['attachment']['name']]);
            }
        }

        $pdo->commit();
        header("Location: ../admin/admindashboard.php?success=1");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}
?>