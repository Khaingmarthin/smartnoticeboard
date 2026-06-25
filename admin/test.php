
<?php
// 1. Set Timezone (Crucial for scheduled notices)
date_default_timezone_set('Asia/Yangon');

// 2. Database Connection
$host = 'localhost';
$dbname = 'smart_notice_board'; // Change if your DB name is different
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 3. Fetch LIVE PUBLISHED Notices (For the main feed)
$liveQuery = "
    SELECT n.*, c.name as category_name, u.name as author_name 
    FROM notices n 
    LEFT JOIN categories c ON n.category_id = c.id 
    LEFT JOIN users u ON n.user_id = u.id 
    WHERE n.status = 'published' 
    ORDER BY n.created_at DESC 
    LIMIT 6
";
$liveStmt = $pdo->query($liveQuery);
$liveNotices = $liveStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch UPCOMING SCHEDULED Notices (Waiting for Cron Job)
$scheduledQuery = "
    SELECT n.id, n.title, n.publish_date, c.name as category_name, u.name as author_name 
    FROM notices n 
    LEFT JOIN categories c ON n.category_id = c.id 
    LEFT JOIN users u ON n.user_id = u.id 
    WHERE n.status = 'draft' AND n.publish_date IS NOT NULL 
    ORDER BY n.publish_date ASC 
    LIMIT 5
";
$scheduledStmt = $pdo->query($scheduledQuery);
$scheduledNotices = $scheduledStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Notice Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans">

    <div class="max-w-6xl mx-auto p-6">

        <header class="flex justify-between items-center mb-8 bg-white p-4 rounded shadow">
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
            <div class="text-right">
                <p id="realTimeClock" class="text-lg font-bold text-gray-800">
                    <?php echo date('h:i A'); ?>
                </p>
                <p class="text-xs text-gray-500">
                    <?php echo date('l, d M Y'); ?>
                </p>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div class="bg-white rounded shadow p-4">
                <h3 class="text-xl font-bold mb-4 text-green-700">Currently Live Notices</h3>

                <?php if (count($liveNotices) > 0): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($liveNotices as $notice): ?>
                            <li class="py-3">
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($notice['title']); ?></p>
                                <p class="text-xs text-gray-500">
                                    Category: <?php echo htmlspecialchars($notice['category_name'] ?? 'General'); ?> |
                                    Posted: <?php echo date('M d, Y', strtotime($notice['created_at'])); ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500 italic">No active notices.</p>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded shadow p-4 border-l-4 border-blue-500">
                <h3 class="text-xl font-bold mb-4 text-blue-700">Upcoming Scheduled Drafts</h3>

                <?php if (count($scheduledNotices) > 0): ?>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b text-sm text-gray-600">
                                <th class="py-2">Title</th>
                                <th class="py-2">Scheduled For</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduledNotices as $notice): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 font-medium text-gray-800">
                                        <?php echo htmlspecialchars($notice['title']); ?>
                                    </td>
                                    <td class="py-2 text-sm text-blue-600 font-bold">
                                        <?php echo date('M d, h:i A', strtotime($notice['publish_date'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-gray-500 italic">No notices are currently scheduled.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';

            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;

            const timeString = hours + ':' + minutes + ' ' + ampm;
            document.getElementById('realTimeClock').innerText = timeString;
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>

</html>

<!-- // login.php -->
<?php
// auth/login.php
require_once '../config/db.php';
session_start();

$error = '';
$success_msg = '';

if (isset($_SESSION['login_message'])) {
    $success_msg = $_SESSION['login_message'];
    unset($_SESSION['login_message']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password_hash']) || $password === $user['password_hash']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                // fetch user data
                if ($user_password_is_correct) {
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    header("Location: ../admin/admindashboard.php");
                    exit();
                }

                // Route users dynamically based on their specific database role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/admindashboard.php");
                } elseif ($user['role'] === 'student') {
                    header("Location: ../student/studentdashboard.php");
                }
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No account found with that email address.";
        }
    }

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart University Notice Board Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-white min-h-screen">

    <div class="min-h-screen flex flex-col md:flex-row">

        <div class="hidden md:flex md:w-1/2 bg-[#E1F0FF] p-8 flex-col justify-between relative overflow-hidden">
            <!-- <a href="../" class="text-black hover:text-gray-700 transition-colors z-10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a> -->

            <div class="flex-1 flex flex-col justify-center items-center px-4">
                <div class="w-4/5 max-w-[450px] h-auto text-[#1E3A8A]">
                    <img src="../assets/ucsmtlalogin.webp" alt="UCSMTLA"
                        class="flex justify-center items-center mx-auto">
                    </img>
                </div>
                <div class="mt-8 text-center">
                    <p class="text-xs text-gray-400 font-medium tracking-wide">@2026 University of Computer Studies,
                        Meiktila</p>
                </div>
            </div>
        </div>

        <div class="w-full md:w-1/2 flex flex-col justify-between items-center p-6 sm:p-12 md:p-16 relative">
            <div class="w-full max-w-sm my-auto pt-8 md:pt-0">
                <div class="flex flex-col items-center text-center mb-8">
                    <div class="w-20 h-20 mb-3 text-[#2B82C9]">
                        <img src="../assets/ucsmtlalogo.png" alt="UCSMTLA" class="">
                        </img>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900 tracking-wide uppercase">University of Computer Studies
                        Meiktila
                    </h1>
                    <p class="text-sm font-semibold text-[#2B82C9] mt-1 tracking-wider uppercase">Smart Notice Board</p>
                </div>

                <?php if ($success_msg): ?>
                    <div class="bg-green-50 border-b-2 border-green-500 p-3 mb-6 text-sm text-green-700">
                        <?php echo htmlspecialchars($success_msg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-50 border-b-2 border-red-500 p-3 mb-6 text-sm text-red-700">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form id="login-form" class="space-y-6" action="login.php" method="POST">

                    <div class="relative">
                        <label for="email" class="text-xs font-medium text-gray-400 block">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            class="w-full py-2 border-b border-gray-300 placeholder-transparent text-gray-900 focus:outline-none focus:border-[#2B82C9] text-sm transition-colors rounded-none bg-transparent"
                            placeholder="you@gmail.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="password" class="text-xs font-medium text-gray-400 block">Password</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" autocomplete="current-password"
                                required
                                class="w-full py-2 pr-8 border-b border-gray-300 placeholder-transparent text-gray-900 focus:outline-none focus:border-[#2B82C9] text-sm transition-colors rounded-none bg-transparent"
                                placeholder="Password">

                            <button type="button" id="toggle-password" aria-label="Toggle password visibility"
                                class="absolute inset-y-0 right-0 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none bg-transparent border-0 cursor-pointer">
                                <svg id="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                    viewBox="0 0 24 24">
                                    <path fill="currentColor"
                                        d="M2 5.27L3.28 4L20 20.72L18.73 22l-3.08-3.08c-1.15.38-2.37.58-3.65.58c-5 0-9.27-3.11-11-7.5c.69-1.76 1.79-3.31 3.19-4.54zM12 9a3 3 0 0 1 3 3a3 3 0 0 1-.17 1L11 9.17A3 3 0 0 1 12 9m0-4.5c5 0 9.27 3.11 11 7.5a11.8 11.8 0 0 1-4 5.19l-1.42-1.43A9.86 9.86 0 0 0 20.82 12A9.82 9.82 0 0 0 12 6.5c-1.09 0-2.16.18-3.16.5L7.3 5.47c1.44-.62 3.03-.97 4.7-.97M3.18 12A9.82 9.82 0 0 0 12 17.5c.69 0 1.37-.07 2-.21L11.72 15A3.064 3.064 0 0 1 9 12.28L5.6 8.87c-.99.85-1.82 1.91-2.42 3.13" />
                                </svg>
                                <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                    viewBox="0 0 24 24" class="hidden">
                                    <path fill="currentColor"
                                        d="M12 9a3 3 0 0 1 3 3a3 3 0 0 1-3 3a3 3 0 0 1-3-3a3 3 0 0 1 3-3m0-4.5c5 0 9.27 3.11 11 7.5c-1.73 4.39-6 7.5-11 7.5S2.73 16.39 1 12c1.73-4.39 6-7.5 11-7.5M3.18 12a9.821 9.821 0 0 0 17.64 0a9.821 9.821 0 0 0-17.64 0" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox"
                                class="h-4 w-4 text-black border-gray-300 rounded accent-black">
                            <label for="remember-me" class="ml-2 block text-xs font-medium text-gray-700 select-none">
                                Remember me </label>
                        </div>

                        <div class="text-xs">
                            <a href="#" class="font-medium text-gray-800 hover:underline"> Forgot Password? </a>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full flex justify-center py-2.5 px-4 text-sm font-medium rounded text-white bg-[#2B82C9] hover:bg-[#226ba6] focus:outline-none shadow transition-colors font-semibold tracking-wide">
                            Sign in
                        </button>
                    </div>
                </form>

            </div>

            <div class="block md:hidden text-[10px] text-gray-400 mt-8 text-center">
                @2026 University of Computer Studies, Meiktila
            </div>
        </div>
    </div>

    <script>
        // --- Show/Hide password toggle ---
        (function () {
            const toggleBtn = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('password');
            const iconEyeOff = document.getElementById('icon-eye-off');
            const iconEye = document.getElementById('icon-eye');

            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    iconEyeOff.classList.toggle('hidden', isHidden);
                    iconEye.classList.toggle('hidden', !isHidden);
                });
            }
        })();
    </script>

    <script>
        // --- LocalStorage handling ---
        (function () {
            const STORAGE_KEY = 'mobileshop_remember';
            const form = document.getElementById('login-form');
            const emailEl = document.getElementById('email');
            const passEl = document.getElementById('password');
            const rememberEl = document.getElementById('remember-me');

            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved) {
                    const data = JSON.parse(saved);
                    if (emailEl && !emailEl.value && data.email) {
                        emailEl.value = data.email;
                    }
                    if (passEl && !passEl.value && data.p) {
                        passEl.value = atob(data.p);
                    }
                    if (rememberEl) {
                        rememberEl.checked = true;
                    }
                }
            } catch (e) {
                localStorage.removeItem(STORAGE_KEY);
            }

            if (form) {
                form.addEventListener('submit', function () {
                    if (rememberEl && rememberEl.checked) {
                        const payload = {
                            email: emailEl.value,
                            p: btoa(passEl.value)
                        };
                        localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
                    } else {
                        localStorage.removeItem(STORAGE_KEY);
                    }
                });
            }

            if (window.location.search.includes('cleared=1')) {
                localStorage.removeItem(STORAGE_KEY);
            }
        })();
    </script>
</body>

</html>