<?php
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

            if (password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: ../admin/admindashboard.php");
                } else {
                    header("Location: ../student/studentdashboard.php");
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart University Notice Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-mesh {
            background-color: #0f172a;
            background-image:
                radial-gradient(at 40% 20%, rgba(59,130,246,0.15) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(99,102,241,0.12) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(14,165,233,0.1) 0px, transparent 50%),
                radial-gradient(at 80% 50%, rgba(168,85,247,0.08) 0px, transparent 50%);
        }
        .glass {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
        }
    </style>
</head>
<body class="bg-mesh min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600/20 border border-blue-500/20 mb-4">
                <img src="../assets/ucsmtlalogo.png" alt="Logo" class="w-10 h-10 rounded-lg">
            </div>
            <h1 class="text-2xl font-bold text-white">Smart University</h1>
            <p class="text-sm text-slate-400 mt-1">Notice Board System</p>
        </div>

        <!-- Login Card -->
        <div class="glass rounded-2xl p-8">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-white">Welcome back</h2>
                <p class="text-sm text-slate-400 mt-0.5">Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <div class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 uppercase tracking-wider mb-1.5">Email Address</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="email" name="email" required placeholder="you@university.edu"
                            class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="password" name="password" id="password" required placeholder="Enter your password"
                            class="w-full pl-10 pr-12 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition">
                        <button type="button" onclick="togglePass()" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition">
                            <i class="fa-solid fa-eye text-sm" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-medium text-sm transition shadow-lg shadow-blue-600/20 cursor-pointer mt-2">
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            Smart University Notice Board &copy; <?php echo date('Y'); ?>
        </p>
    </div>

    <script>
    function togglePass() {
        const p = document.getElementById('password');
        const i = document.getElementById('eyeIcon');
        if (p.type === 'password') { p.type = 'text'; i.className = 'fa-solid fa-eye-slash text-sm'; }
        else { p.type = 'password'; i.className = 'fa-solid fa-eye text-sm'; }
    }
    </script>
</body>
</html>
