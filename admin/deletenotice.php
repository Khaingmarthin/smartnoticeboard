<?php
session_start();
include('../config/db.php');

// 1. Check if an ID was passed in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $notice_id = intval($_GET['id']);

    // 2. Optional: First delete any linked files from your attachments table
    $delete_attachments = "DELETE FROM attachments WHERE notice_id = $notice_id";
    mysqli_query($conn, $delete_attachments);

    // 3. Delete the notice from the notices table
    $delete_notice = "DELETE FROM notices WHERE id = $notice_id";

    if (mysqli_query($conn, $delete_notice)) {
        // Success! Redirect back with a delete flag
        header("Location: manage_notice.php?deleted=1");
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
} else {
    header("Location: manage_notice.php");
    exit();
}
?>