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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen flex items-center justify-center overflow-hidden">

    <div class="min-h-screen w-full flex flex-col md:flex-row">

        <div class="hidden md:flex md:w-1/2 bg-[#E1F0FF] p-12 flex-col justify-between relative overflow-hidden">
            <div class="absolute -top-16 -left-16 w-64 h-64 bg-blue-200/30 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-blue-300/20 rounded-full blur-3xl"></div>

            <div class="flex-1 flex flex-col justify-center items-center px-4 z-10">
                <div
                    class="w-4/5 max-w-[380px] h-auto drop-shadow-xl transform hover:scale-105 transition-transform duration-500">
                    <img src="../assets/ucsmtlalogin.webp" alt="UCSMTLA Campus Illustration"
                        class="w-full h-auto object-contain">
                </div>
            </div>

            <div class="text-center z-10">
                <p class="text-xs text-blue-900/60 font-semibold tracking-wider uppercase">© 2026 University of Computer
                    Studies, Meiktila</p>
            </div>
        </div>

        <div
            class="w-full md:w-1/2 flex flex-col justify-between items-center p-8 sm:p-12 md:p-16 bg-white shadow-2xl md:shadow-none z-10 overflow-y-auto">
            <div class="w-full max-w-md my-auto space-y-8">

                <div class="flex flex-col items-center text-center">
                    <div class="w-24 h-24 mb-4 drop-shadow-md">
                        <img src="../assets/ucsmtlalogo.png" alt="UCSMTLA Seal Logo"
                            class="w-full h-full object-contain">
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight leading-snug max-w-sm">
                        University of Computer Studies<br><span
                            class="text-xl font-semibold text-slate-700">Meiktila</span>
                    </h1>
                    <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full bg-blue-50 border border-blue-100">
                        <span class="text-xs font-bold text-[#2B82C9] uppercase tracking-widest">Smart Notice
                            Board</span>
                    </div>
                </div>

                <?php if ($success_msg): ?>
                    <div
                        class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl text-sm text-emerald-800 flex items-center space-x-3 shadow-sm animate-fade-in">
                        <i class="fas fa-check-circle text-base text-emerald-500"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($success_msg); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div
                        class="bg-rose-50 border border-rose-200 p-4 rounded-xl text-sm text-rose-800 flex items-center space-x-3 shadow-sm">
                        <i class="fas fa-exclamation-circle text-base text-rose-500"></i>
                        <span class="font-medium"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form id="login-form" class="space-y-5" action="login.php" method="POST">

                    <div>
                        <label for="email"
                            class="text-xs font-bold text-slate-500 block mb-2 uppercase tracking-wider">Email
                            Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fas fa-envelope text-sm"></i>
                            </span>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:border-[#2B82C9] focus:ring-4 focus:ring-blue-100 transition-all placeholder-slate-400"
                                placeholder="username@ucsmtla.edu.mm"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div>
                        <label for="password"
                            class="text-xs font-bold text-slate-500 block mb-2 uppercase tracking-wider">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input id="password" name="password" type="password" autocomplete="current-password"
                                required
                                class="w-full pl-10 pr-11 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:border-[#2B82C9] focus:ring-4 focus:ring-blue-100 transition-all placeholder-slate-400"
                                placeholder="••••••••">

                            <button type="button" id="toggle-password" aria-label="Toggle password visibility"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 focus:outline-none bg-transparent border-0 cursor-pointer transition-colors">
                                <svg id="icon-eye-off" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                                <svg id="icon-eye" class="w-5 h-5 hidden" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1 text-sm">
                        <label class="flex items-center cursor-pointer select-none group">
                            <input id="remember-me" name="remember-me" type="checkbox"
                                class="h-4 w-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500/20 accent-[#2B82C9]">
                            <span
                                class="ml-2 text-xs font-medium text-slate-600 group-hover:text-slate-800 transition-colors">Remember
                                me</span>
                        </label>
                        <a href="#"
                            class="text-xs font-semibold text-[#2B82C9] hover:text-[#1d5d93] hover:underline transition-colors">Forgot
                            Password?</a>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full py-3 px-4 text-sm font-bold rounded-xl text-white bg-[#2B82C9] hover:bg-[#226ba6] active:bg-[#1b5585] focus:outline-none focus:ring-4 focus:ring-blue-500/20 shadow-lg shadow-blue-500/10 hover:shadow-blue-500/20 transition-all tracking-wide uppercase">
                            Sign In
                        </button>
                    </div>
                </form>

            </div>

            <div
                class="block md:hidden text-[10px] text-slate-400 mt-12 text-center font-medium tracking-wider uppercase">
                © 2026 University of Computer Studies, Meiktila
            </div>
        </div>
    </div>

    <script>
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