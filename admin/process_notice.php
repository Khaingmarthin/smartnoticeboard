<?php
session_start();

// Auth guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../aunth/login.php");
    exit();
}

include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $category_id = intval($_POST['category_id']);

    // Checkboxes (Mutually exclusive via your frontend JS)
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // --- ENUM, Nullable, and Date Column Logic Handling ---

    // Target Role (Matches enum('student','admin','all'))
    $target_role = !empty($_POST['target_role']) ? mysqli_real_escape_string($conn, $_POST['target_role']) : 'all';

    // Target Year ID
    $target_year_id = !empty($_POST['target_year_id']) && $_POST['target_year_id'] !== 'all' ? intval($_POST['target_year_id']) : "NULL";

    // Publish Date: If missing from form, default to current timestamp
    if (!empty($_POST['publish_date'])) {
        $publish_raw = $_POST['publish_date'];
        $publish_date = date('Y-m-d H:i:s', strtotime($publish_raw));
    } else {
        $publish_raw = null;
        $publish_date = date('Y-m-d H:i:s');
    }

    // Expire Date
    if (!empty($_POST['expire_date'])) {
        $expire_raw = $_POST['expire_date'];
        $expire_date_sql = "'" . date('Y-m-d H:i:s', strtotime($expire_raw)) . "'";
    } else {
        $expire_raw = null;
        $expire_date_sql = "NULL";
    }

    // --- Automated Status Calculation Engine (Option A) ---
    $current_timestamp = time();

    if ($publish_raw && strtotime($publish_raw) > $current_timestamp) {
        // Scheduled for a future time
        $status = 'draft';
    } elseif ($expire_raw && strtotime($expire_raw) <= $current_timestamp) {
        // Expiration time has already elapsed
        $status = 'expired';
    } else {
        // Live right now
        $status = 'published';
    }

    // Dynamic Session Admin Identification
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

    // 1. Core notice insertion execution block
    $notice_query = "INSERT INTO notices (
                        title, content, category_id, user_id, status, 
                        target_role, target_year_id, publish_date, expire_date, 
                        is_urgent, is_featured
                      ) VALUES (
                        '$title', '$content', $category_id, $user_id, '$status', 
                        '$target_role', $target_year_id, '$publish_date', $expire_date_sql, 
                        $is_urgent, $is_featured
                      )";

    if (mysqli_query($conn, $notice_query)) {
        $notice_id = mysqli_insert_id($conn);

        // 2. Separate Table Attachment File Upload Engine
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $original_name = basename($_FILES['attachment']['name']);
            $file_name = time() . '_' . $original_name;
            $target_file = $upload_dir . $file_name;
            $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                $file_path_clean = mysqli_real_escape_string($conn, $file_name);
                $file_type_clean = mysqli_real_escape_string($conn, $file_type);

                $attach_query = "INSERT INTO attachments (notice_id, file_path, file_type) 
                                 VALUES ($notice_id, '$file_path_clean', '$file_type_clean')";
                mysqli_query($conn, $attach_query);
            }
        }

        header("Location: manage_notice.php?success=1");
        exit();
    } else {
        echo "Database Insertion Failed: " . mysqli_error($conn);
    }
} else {
    header("Location: manage_notice.php");
    exit();
}
?>