<?php
include('../includes/header.php');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Get and clean standard form inputs
    $notice_id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $category_id = intval($_POST['category_id']);
    // If target_role isn't set or is empty, default to 'all'
    $target_role = isset($_POST['target_role']) && $_POST['target_role'] !== ''
        ? mysqli_real_escape_string($conn, $_POST['target_role'])
        : 'all';
    // Checkbox states (1 if checked, 0 if unchecked)
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // 2. Handle Target Year (Convert empty or "all" text strings to SQL NULL)
    if (!empty($_POST['target_year_id']) && $_POST['target_year_id'] !== 'all') {
        $target_year_id = intval($_POST['target_year_id']);
        $target_year_sql = $target_year_id;
    } else {
        $target_year_sql = "NULL";
    }

    // 3. Normalize Date Inputs (Convert empty text strings to SQL NULL values)
    $publish_date_raw = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
    $expire_date_raw = !empty($_POST['expire_date']) ? $_POST['expire_date'] : null;

    $publish_date_sql = $publish_date_raw ? "'" . mysqli_real_escape_string($conn, $publish_date_raw) . "'" : "NULL";
    $expire_date_sql = $expire_date_raw ? "'" . mysqli_real_escape_string($conn, $expire_date_raw) . "'" : "NULL";

    // 4. Automated Status Engine (Option A)
    $current_timestamp = time();

    if ($publish_date_raw && strtotime($publish_date_raw) > $current_timestamp) {
        // Scheduled for a future date/time
        $status = 'draft';
    } elseif ($expire_date_raw && strtotime($expire_date_raw) <= $current_timestamp) {
        // Expiration time has already elapsed
        $status = 'expired';
    } else {
        // Currently active and visible
        $status = 'published';
    }

    // 5. Update the main notices table with new features
    $update_notice_query = "UPDATE notices SET 
                                title = '$title', 
                                content = '$content', 
                                category_id = $category_id,
                                target_role = '$target_role',
                                target_year_id = $target_year_sql,
                                publish_date = $publish_date_sql,
                                expire_date = $expire_date_sql,
                                status = '$status',
                                is_urgent = $is_urgent,
                                is_featured = $is_featured
                             WHERE id = $notice_id";

    if (mysqli_query($conn, $update_notice_query)) {

        // 6. Handle File Upload (Only if a new file is actually uploaded)
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

                // Check if this notice already has an attachment record
                $check_attach = "SELECT id FROM attachments WHERE notice_id = $notice_id";
                $check_result = mysqli_query($conn, $check_attach);

                if (mysqli_num_rows($check_result) > 0) {
                    // Update existing attachment row
                    $attach_query = "UPDATE attachments SET 
                                        file_path = '$file_path_clean', 
                                        file_type = '$file_type_clean' 
                                     WHERE notice_id = $notice_id";
                } else {
                    // Insert a brand new attachment row if none existed before
                    $attach_query = "INSERT INTO attachments (notice_id, file_path, file_type) 
                                     VALUES ($notice_id, '$file_path_clean', '$file_type_clean')";
                }

                mysqli_query($conn, $attach_query);
            }
        }

        // 7. Redirect successfully back to the list
        echo "<script>window.location.href='manage_notice.php?updated=1';</script>";
        exit();
    } else {
        echo "Failed to update notice: " . mysqli_error($conn);
    }
} else {
    header("Location: manage_notice.php");
    exit();
}
?>