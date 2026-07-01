<?php
session_start();

// Auth guard: only logged-in admins can modify categories
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../aunth/login.php");
    exit();
}

include('../config/db.php');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// =============================================
// DELETE ACTION (via GET with confirmation)
// =============================================
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Check if category has notices assigned
    $check = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notices WHERE category_id = $id");
    $row = mysqli_fetch_assoc($check);
    if ($row['cnt'] > 0) {
        $_SESSION['flash_msg'] = 'Cannot delete: category still has notices assigned to it.';
        $_SESSION['flash_type'] = 'error';
        header("Location: categories.php");
        exit();
    }

    $delete_query = "DELETE FROM categories WHERE id = $id";
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['flash_msg'] = 'Category deleted successfully!';
        $_SESSION['flash_type'] = 'success';
        header("Location: categories.php");
        exit();
    } else {
        die("Database Delete Failure: " . mysqli_error($conn));
    }
}

// =============================================
// POST ACTIONS (add or edit)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    $bg_color_code = mysqli_real_escape_string($conn, trim($_POST['bg_color_code']));

    if (empty($category_name) || empty($bg_color_code)) {
        $_SESSION['flash_msg'] = 'Please fill in all required fields.';
        $_SESSION['flash_type'] = 'error';
        header("Location: categories.php");
        exit();
    }

    // -----------------------------------------
    // EDIT ACTION
    // -----------------------------------------
    if ($action === 'edit' && isset($_POST['category_id'])) {
        $category_id = intval($_POST['category_id']);

        // Check for duplicate name (excluding current record)
        $check_query = "SELECT id FROM categories WHERE LOWER(name) = LOWER('$category_name') AND id != $category_id";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['flash_msg'] = 'A category with that name already exists.';
            $_SESSION['flash_type'] = 'error';
            header("Location: categories.php");
            exit();
        }

        $update_query = "UPDATE categories SET name = '$category_name', bg_color_code = '$bg_color_code' WHERE id = $category_id";
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['flash_msg'] = 'Category updated successfully!';
            $_SESSION['flash_type'] = 'success';
            header("Location: categories.php");
            exit();
        } else {
            die("Database Update Failure: " . mysqli_error($conn));
        }
    }

    // -----------------------------------------
    // ADD ACTION (default)
    // -----------------------------------------

    // Prevent duplicate category names
    $check_query = "SELECT id FROM categories WHERE LOWER(name) = LOWER('$category_name')";
    $check_result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['flash_msg'] = 'A category with that name already exists.';
        $_SESSION['flash_type'] = 'error';
        header("Location: categories.php");
        exit();
    }

    $insert_query = "INSERT INTO categories (name, bg_color_code)
                     VALUES ('$category_name', '$bg_color_code')";

    if (mysqli_query($conn, $insert_query)) {
        $_SESSION['flash_msg'] = 'Adding category is success!';
        $_SESSION['flash_type'] = 'success';
        header("Location: categories.php");
        exit();
    } else {
        die("Database Insertion Failure: " . mysqli_error($conn));
    }

} else {
    header("Location: categories.php");
    exit();
}
