<?php
// auth/process_login.php
session_start();

// 1. Database Connection Configuration
$host = 'localhost';
$dbname = 'noticeboard_db';
$username = 'root';
$password = 'km@24y8*';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 2. Process Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // ERROR 1: Email doesn't exist in database
        header("Location: login.php?error=no_account");
        exit();
    } else {
        // ERROR 2: Email exists, now verify password hash matches
        if (password_verify($password, $user['password'])) {

            // SUCCESS: Setup persistent secure session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role']; // e.g., 'admin' or 'student'

            // Redirect admin users directly to their dashboard
            if ($user['role'] === 'admin') {
                header("Location: ../admin/admindashboard.php");
            } else {
                header("Location: ../student/dashboard.php"); // Or your student page
            }
            exit();
        } else {
            // Password verification failed
            header("Location: login.php?error=invalid_password");
            exit();
        }
    }
}