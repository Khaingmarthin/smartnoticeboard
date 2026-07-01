<?php
include '../includes/header.php';
include('../config/db.php');

// Handle user actions (add/edit/delete) via session flash
$flash_msg = null;
$flash_type = null;
if (isset($_SESSION['flash_msg'])) {
    $flash_msg = $_SESSION['flash_msg'];
    $flash_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// Handle edit mode
$edit_mode = false;
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $edit_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $edit_id");
    if (mysqli_num_rows($edit_result) > 0) {
        $edit_user = mysqli_fetch_assoc($edit_result);
        $edit_mode = true;
    }
}

// Fetch all users
$users_result = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
$users = [];
if ($users_result && mysqli_num_rows($users_result) > 0) {
    while ($row = mysqli_fetch_assoc($users_result)) {
        $users[] = $row;
    }
}
?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manage Users</h1>
            <p class="text-sm text-slate-500">View and manage admin and student accounts</p>
        </div>
        <span class="bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-blue-100">
            <i class="fa-solid fa-users mr-1"></i> <?php echo count($users); ?> Users
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Users Table -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="p-4 font-semibold text-slate-600">User</th>
                                <th class="p-4 font-semibold text-slate-600">Role</th>
                                <th class="p-4 font-semibold text-slate-600">Joined</th>
                                <th class="p-4 font-semibold text-slate-600 text-center w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=<?php echo $user['role'] === 'admin' ? '4F46E5' : '10B981'; ?>&color=fff&size=36"
                                                    alt="<?php echo htmlspecialchars($user['name']); ?>"
                                                    class="w-9 h-9 rounded-full">
                                                <div>
                                                    <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($user['name']); ?></p>
                                                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($user['email']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    <i class="fa-solid fa-shield-halved text-[9px]"></i> Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <i class="fa-solid fa-graduation-cap text-[9px]"></i> Student
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-slate-500 text-xs">
                                            <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td class="p-4">
                                            <div class="flex justify-center gap-1.5">
                                                <a href="users.php?action=edit&id=<?php echo $user['id']; ?>"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition"
                                                    title="Edit User">
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                </a>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="process_user.php?action=delete&id=<?php echo $user['id']; ?>"
                                                        onclick="return confirm('Delete user <?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>?');"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition"
                                                        title="Delete User">
                                                        <i class="fa-solid fa-trash text-xs"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-slate-400 py-8">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit User Form -->
        <div class="space-y-4">
            <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider">
                <?php echo $edit_mode ? 'Edit User' : 'Add New User'; ?>
            </h2>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 mt-4">
                <?php if ($flash_msg): ?>
                    <div class="mb-4 p-3 rounded-lg text-sm font-medium flex items-center gap-2 <?php echo $flash_type === 'error' ? 'bg-rose-50 border border-rose-200 text-rose-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'; ?>">
                        <i class="fa-solid <?php echo $flash_type === 'error' ? 'fa-triangle-exclamation text-rose-500' : 'fa-circle-check text-emerald-500'; ?>"></i>
                        <?php echo htmlspecialchars($flash_msg); ?>
                    </div>
                <?php endif; ?>

                <form action="process_user.php" method="POST" class="space-y-4">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                    <?php endif; ?>
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">Full Name</label>
                        <input type="text" name="name" id="user_name" required
                            value="<?php echo $edit_mode ? htmlspecialchars($edit_user['name']) : ''; ?>"
                            placeholder="e.g., John Doe"
                            class="w-full text-sm border border-slate-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">Email Address</label>
                        <input type="email" name="email" id="user_email" required
                            value="<?php echo $edit_mode ? htmlspecialchars($edit_user['email']) : ''; ?>"
                            placeholder="e.g., user@example.com"
                            class="w-full text-sm border border-slate-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                            <?php echo $edit_mode ? 'New Password (leave blank to keep current)' : 'Password' ?>
                        </label>
                        <input type="password" name="password" id="user_password"
                            <?php echo $edit_mode ? '' : 'required'; ?>
                            placeholder="<?php echo $edit_mode ? '••••••••' : 'Enter password'; ?>"
                            class="w-full text-sm border border-slate-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">Role</label>
                        <select name="role" required
                            class="w-full text-sm border border-slate-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                            <option value="student" <?php echo ($edit_mode && $edit_user['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="admin" <?php echo ($edit_mode && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="pt-2 flex gap-2">
                        <button type="submit"
                            class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg font-medium text-sm hover:bg-blue-700 transition shadow-sm cursor-pointer">
                            <?php echo $edit_mode ? 'Update User' : 'Add User'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="users.php"
                                class="px-4 py-2.5 rounded-lg font-medium text-sm border border-slate-300 text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
