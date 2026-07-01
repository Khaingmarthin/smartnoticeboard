<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../aunth/login.php");
    exit();
}

$adminName = $_SESSION['user_name'] ?? 'Admin';

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

$current_page = basename($_SERVER['PHP_SELF']);
function is_active($page_name, $current_page) {
    return $page_name === $current_page ? 'bg-white/15 font-medium text-white' : 'text-white/70 hover:bg-white/10';
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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#eff6ff', 100:'#dbeafe', 200:'#bfdbfe', 300:'#93c5fd', 400:'#60a5fa', 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8' },
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .sidebar { background: linear-gradient(180deg, #1e3a5f 0%, #1a2d47 100%); }
        @media (max-width: 767px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; position: fixed; z-index: 50; height: 100vh; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 40; }
            .sidebar-overlay.open { display: block; }
        }
    </style>
</head>

<body class="flex h-screen overflow-hidden text-gray-800">

    <!-- Mobile overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar w-64 text-white flex flex-col justify-between h-full flex-shrink-0">
        <div>
            <div class="p-5 flex items-center space-x-3 border-b border-white/10">
                <img src="../assets/ucsmtlalogo.png" alt="UCSMTLA" class="w-10 h-10 rounded-lg">
                <div>
                    <span class="text-sm font-bold leading-tight block">Smart University</span>
                    <span class="text-[11px] font-medium text-white/60">Notice Board</span>
                </div>
                <!-- Mobile close button -->
                <button onclick="toggleSidebar()" class="ml-auto md:hidden text-white/60 hover:text-white p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <nav class="mt-4 px-3 space-y-1">
                <a href="../admin/admindashboard.php"
                    class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition <?php echo is_active('admindashboard.php', $current_page); ?>">
                    <i class="fas fa-home w-5 text-center"></i><span>Dashboard</span>
                </a>
                <a href="../admin/manage_notice.php"
                    class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition <?php echo is_active('manage_notice.php', $current_page); ?>">
                    <i class="fas fa-file-alt w-5 text-center"></i><span>Manage Notices</span>
                </a>
                <a href="../admin/categories.php"
                    class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition <?php echo is_active('categories.php', $current_page); ?>">
                    <i class="fas fa-list w-5 text-center"></i><span>Categories</span>
                </a>
                <a href="../admin/scheduled_notice.php"
                    class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition <?php echo is_active('scheduled_notice.php', $current_page); ?>">
                    <i class="fas fa-calendar-alt w-5 text-center"></i><span>Scheduled</span>
                </a>
                <a href="../admin/users.php"
                    class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm transition <?php echo is_active('users.php', $current_page); ?>">
                    <i class="fas fa-users w-5 text-center"></i><span>Users</span>
                </a>
            </nav>
        </div>

        <div class="p-3 border-t border-white/10">
            <a href="../aunth/logout.php" onclick="return confirm('Log out?');"
                class="flex items-center space-x-3 px-3 py-2.5 text-white/60 hover:bg-white/10 hover:text-white rounded-lg transition text-sm">
                <i class="fas fa-sign-out-alt w-5 text-center"></i><span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden min-w-0">
        <header class="bg-white px-4 md:px-8 py-3 flex justify-between items-center shadow-sm z-10">
            <div class="flex items-center gap-3">
                <!-- Hamburger menu (mobile only) -->
                <button onclick="toggleSidebar()" class="md:hidden text-slate-600 hover:text-slate-900 p-2 -ml-2">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h1 class="text-lg font-bold text-gray-800 truncate">Smart University Notice Board</h1>
            </div>
            <div class="flex items-center space-x-4">
                <?php date_default_timezone_set('Asia/Yangon'); ?>
                <div class="text-right hidden sm:block">
                    <p id="realTimeClock" class="text-sm font-bold text-gray-800"><?php echo date('h:i A'); ?></p>
                    <p class="text-[10px] text-gray-400"><?php echo date('l, d M Y'); ?></p>
                </div>
                <div class="flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($adminName); ?>&background=3b82f6&color=fff&size=36"
                        alt="Admin" class="w-8 h-8 rounded-full">
                    <div class="hidden sm:block">
                        <p class="text-sm font-semibold text-gray-800 leading-tight"><?php echo htmlspecialchars($adminName); ?></p>
                        <p class="text-[10px] text-gray-400 capitalize"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                    </div>
                </div>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-4 md:p-8 w-full">

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}

// Real-time clock
setInterval(function() {
    const el = document.getElementById('realTimeClock');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }
}, 1000);
</script>
