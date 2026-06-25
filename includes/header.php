<?php
//includes/header.php

// 1. Session & Access Control Security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//  THE BOUNCER: If the user_id session is missing, or they are not an admin, kick them out
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
 
    header("Location: ../aunth/login.php");
    exit(); // Stop executing the rest of the script immediately
}

$adminName = $_SESSION['user_name'] ?? 'Admin';

// 2. Global Database Connection 
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

// 3. Set Active Class Helper Function for Sidebar Highlight
$current_page = basename($_SERVER['PHP_SELF']);
function is_active($page_name, $current_page)
{
    return $page_name === $current_page ? 'bg-blue-600 font-medium shadow-md text-white' : 'text-gray-300 hover:bg-indigo-900';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Smart University Notice Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .sidebar {
            background-color: #2d6fa5;
        }
    </style>
</head>

<body class="flex h-screen overflow-hidden text-gray-800">

    <aside class="sidebar w-64 text-white flex flex-col justify-between h-full flex-shrink-0">
        <div>
            <div class="p-6 flex items-center space-x-3 border-b border-indigo-900">
                <img src="../assets/ucsmtlalogo.png" alt="UCSMTLA" class="w-12 h-12">
                <span class="text-lg font-bold leading-tight">Smart University<br>
                    <span class="text-md font-semibold text-gray-300">Notice Board</span>
                </span>
            </div>

            <nav class="mt-6 px-4 space-y-2">
                <a href="../admin/admindashboard.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?php echo is_active('admindashboard.php', $current_page); ?>">
                    <i class="fas fa-home w-5"></i><span>Dashboard</span>
                </a>
                <a href="../admin/manage_notice.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?php echo is_active('manage_notice.php', $current_page); ?>">
                    <i class="fas fa-file-alt w-5"></i><span>Manage Notices</span>
                </a>
                <a href="../admin/categories.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?php echo is_active('categories.php', $current_page); ?>">
                    <i class="fas fa-list w-5"></i><span>Categories</span>
                </a>
                <a href="../admin/scheduled_notice.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?php echo is_active('scheduled_notices.php', $current_page); ?>">
                    <i class="fas fa-calendar-alt w-5"></i><span>Scheduled Notices</span>
                </a>
                <a href="../admin/users.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?php echo is_active('users.php', $current_page); ?>">
                    <i class="fas fa-users w-5"></i><span>Users</span>
                </a>
            </nav>
        </div>

        <div class="p-4">
            <a href="../aunth/logout.php" onclick="return confirm('Are you sure you want to log out?');"
                class="flex items-center space-x-3 px-4 py-3 text-gray-300 hover:bg-indigo-900 rounded-lg transition-colors">
                <i class="fas fa-sign-out-alt w-5"></i><span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden">

        <header class="bg-white px-8 py-4 flex justify-between items-center shadow-sm z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Smart University Notice Board</h1>

            </div>
            <div class="flex items-center space-x-6">
                <?php date_default_timezone_set('Asia/Yangon'); ?>

                <div class="text-right">
                    <p id="realTimeClock" class="text-lg font-bold text-gray-800"><?php echo date('h:i A'); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date('l, d M Y'); ?></p>
                </div>

                <div class="flex items-center space-x-3">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($adminName); ?>&background=0D8ABC&color=fff"
                        alt="Admin" class="w-10 h-10 rounded-full">
                    <div>
                        <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($adminName); ?></p>
                        <p class="text-xs text-gray-400 capitalize"><?php echo htmlspecialchars($_SESSION['role']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-8 w-full">