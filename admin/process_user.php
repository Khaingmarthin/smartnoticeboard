<?php
session_start();

// Auth guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../aunth/login.php");
    exit();
}

include('../config/db.php');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// =============================================
// DELETE ACTION
// =============================================
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Prevent deleting yourself
    if ($id == $_SESSION['user_id']) {
        $_SESSION['flash_msg'] = 'You cannot delete your own account.';
        $_SESSION['flash_type'] = 'error';
        header("Location: users.php");
        exit();
    }

    $delete_query = "DELETE FROM users WHERE id = $id";
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['flash_msg'] = 'User deleted successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_msg'] = 'Failed to delete user.';
        $_SESSION['flash_type'] = 'error';
    }
    header("Location: users.php");
    exit();
}

// =============================================
// POST ACTIONS (add or edit)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || ($action !== 'edit' && empty($password))) {
        $_SESSION['flash_msg'] = 'Please fill in all required fields.';
        $_SESSION['flash_type'] = 'error';
        header("Location: users.php");
        exit();
    }

    // -----------------------------------------
    // EDIT ACTION
    // -----------------------------------------
    if ($action === 'edit' && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);

        // Check duplicate email (excluding current)
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['flash_msg'] = 'A user with that email already exists.';
            $_SESSION['flash_type'] = 'error';
            header("Location: users.php");
            exit();
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = "UPDATE users SET name = '$name', email = '$email', role = '$role', password_hash = '$hash' WHERE id = $user_id";
        } else {
            $update = "UPDATE users SET name = '$name', email = '$email', role = '$role' WHERE id = $user_id";
        }

        if (mysqli_query($conn, $update)) {
            $_SESSION['flash_msg'] = 'User updated successfully.';
            $_SESSION['flash_type'] = 'success';
            header("Location: users.php");
            exit();
        } else {
            die("Update failed: " . mysqli_error($conn));
        }
    }

    // -----------------------------------------
    // ADD ACTION
    // -----------------------------------------
    if (empty($password)) {
        $_SESSION['flash_msg'] = 'Password is required for new users.';
        $_SESSION['flash_type'] = 'error';
        header("Location: users.php");
        exit();
    }

    // Check duplicate email
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['flash_msg'] = 'A user with that email already exists.';
        $_SESSION['flash_type'] = 'error';
        header("Location: users.php");
        exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = "INSERT INTO users (name, email, password_hash, role) VALUES ('$name', '$email', '$hash', '$role')";

    if (mysqli_query($conn, $insert)) {
        $_SESSION['flash_msg'] = 'User added successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        die("Insert failed: " . mysqli_error($conn));
    }
}

header("Location: users.php");
exit();
