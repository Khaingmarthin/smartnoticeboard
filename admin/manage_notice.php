<?php
include ('../includes/header.php');
include('../config/db.php');

// Read filter parameters
$search   = isset($_GET['q']) ? trim($_GET['q']) : '';
$status   = isset($_GET['status']) ? $_GET['status'] : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$role     = isset($_GET['role']) ? $_GET['role'] : '';
$type     = isset($_GET['type']) ? $_GET['type'] : '';

// Build query dynamically
$conditions = [];
$params     = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $conditions[] = "(n.title LIKE ? OR n.content LIKE ?)";
    $params[] = $like;
    $params[] = $like;
}
if ($status !== '' && in_array($status, ['published', 'draft', 'expired'])) {
    $conditions[] = "n.status = ?";
    $params[] = $status;
}
if ($category > 0) {
    $conditions[] = "n.category_id = ?";
    $params[] = $category;
}
if ($role !== '' && in_array($role, ['student', 'admin', 'all'])) {
    $conditions[] = "n.target_role = ?";
    $params[] = $role;
}
if ($type === 'urgent') {
    $conditions[] = "n.is_urgent = 1";
} elseif ($type === 'featured') {
    $conditions[] = "n.is_featured = 1";
}

$where = '';
if (!empty($conditions)) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}

$query = "SELECT n.*,
                 c.name AS category_name,
                 c.bg_color_code,
                 u.name AS author_name
          FROM notices n
          LEFT JOIN categories c ON n.category_id = c.id
          LEFT JOIN users u ON n.user_id = u.id
          $where
          ORDER BY n.created_at DESC";

// Use prepared statements for safety
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    // Integers need 'i' type
    $types = '';
    foreach ($params as $p) {
        $types .= is_int($p) ? 'i' : 's';
    }
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$notices = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }
}
$total_count = count($notices);

// Fetch categories for filter dropdown
$cat_result = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
$categories = [];
if ($cat_result) {
    while ($c = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $c;
    }
}

// Count by status for filter badges
$count_all      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notices"))['cnt'];
$count_published= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notices WHERE status='published'"))['cnt'];
$count_draft    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notices WHERE status='draft'"))['cnt'];
$count_expired  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notices WHERE status='expired'"))['cnt'];

function statusBadge($status) {
    $map = [
        'published' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-circle-check'],
        'draft'     => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'fa-clock'],
        'expired'   => ['bg' => 'bg-slate-100 text-slate-500 border-slate-200', 'icon' => 'fa-circle-xmark'],
    ];
    $s = $map[$status] ?? $map['draft'];
    return '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full border ' . $s['bg'] . '"><i class="fa-solid ' . $s['icon'] . ' text-[9px]"></i> ' . ucfirst($status) . '</span>';
}

function isActive($param, $value, $current) {
    if ($param === 'status') return $current === $value;
    if ($param === 'role') return $current === $value;
    if ($param === 'type') return $current === $value;
    return false;
}
?>

<div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manage Notices</h1>
            <p class="text-sm text-slate-500">View, edit, and organize all system notices</p>
        </div>
        <a href="addnotice.php"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-sm">
            <i class="fa-solid fa-plus"></i> Add Notice
        </a>
    </div>

    <!-- Search & Filters -->
    <form method="GET" class="mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <!-- Search bar -->
            <div class="flex gap-3 mb-4">
                <div class="flex-1 relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search notices by title or content..."
                        class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                </div>
                <button type="submit"
                    class="px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition cursor-pointer">
                    <i class="fa-solid fa-search mr-1"></i> Search
                </button>
                <?php if ($search !== '' || $status !== '' || $category > 0 || $role !== '' || $type !== ''): ?>
                    <a href="manage_notice.php"
                        class="px-4 py-2.5 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                        <i class="fa-solid fa-xmark mr-1"></i> Clear
                    </a>
                <?php endif; ?>
            </div>

            <!-- Filter row -->
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Status filters -->
                <div class="flex items-center gap-1.5">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => ''])); ?>"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition <?php echo $status === '' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                        All <span class="ml-1 opacity-70"><?php echo $count_all; ?></span>
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'published'])); ?>"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition <?php echo $status === 'published' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'; ?>">
                        Published <span class="ml-1 opacity-70"><?php echo $count_published; ?></span>
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'draft'])); ?>"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition <?php echo $status === 'draft' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'; ?>">
                        Draft <span class="ml-1 opacity-70"><?php echo $count_draft; ?></span>
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'expired'])); ?>"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition <?php echo $status === 'expired' ? 'bg-slate-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                        Expired <span class="ml-1 opacity-70"><?php echo $count_expired; ?></span>
                    </a>
                </div>

                <div class="w-px h-6 bg-slate-200"></div>

                <!-- Category filter -->
                <select name="category" onchange="this.form.submit()"
                    class="border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 bg-white outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Target role filter -->
                <select name="role" onchange="this.form.submit()"
                    class="border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 bg-white outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">All Roles</option>
                    <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>All Users</option>
                    <option value="student" <?php echo $role === 'student' ? 'selected' : ''; ?>>Students</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admins</option>
                </select>

                <!-- Type filter -->
                <select name="type" onchange="this.form.submit()"
                    class="border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 bg-white outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">All Types</option>
                    <option value="urgent" <?php echo $type === 'urgent' ? 'selected' : ''; ?>>Urgent Only</option>
                    <option value="featured" <?php echo $type === 'featured' ? 'selected' : ''; ?>>Featured Only</option>
                </select>
            </div>
        </div>
    </form>

    <!-- Results count -->
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-slate-500">
            Showing <span class="font-semibold text-slate-700"><?php echo $total_count; ?></span>
            <?php echo $total_count === 1 ? 'notice' : 'notices'; ?>
            <?php if ($search !== ''): ?>
                for "<span class="font-medium text-slate-700"><?php echo htmlspecialchars($search); ?></span>"
            <?php endif; ?>
        </p>
    </div>

    <!-- Notice list -->
    <?php if (!empty($notices)): ?>
        <div class="space-y-3">
            <?php foreach ($notices as $notice):
                $cat_color = htmlspecialchars($notice['bg_color_code'] ?? '#64748B');
                $has_attachment = !empty($notice['file_path']);
                $is_urgent = !empty($notice['is_urgent']);
                $is_featured = !empty($notice['is_featured']);
            ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition">
                    <div class="flex">
                        <div class="w-1.5 flex-shrink-0" style="background-color: <?php echo $cat_color; ?>"></div>
                        <div class="flex-1 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                        <h3 class="font-bold text-slate-900 text-base truncate">
                                            <?php echo htmlspecialchars($notice['title']); ?>
                                        </h3>
                                        <?php echo statusBadge($notice['status']); ?>
                                        <?php if ($is_urgent): ?>
                                            <span class="text-[10px] font-bold text-white bg-red-500 px-1.5 py-0.5 rounded">URGENT</span>
                                        <?php endif; ?>
                                        <?php if ($is_featured): ?>
                                            <span class="text-[10px] font-bold text-white bg-indigo-500 px-1.5 py-0.5 rounded">FEATURED</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-slate-500 line-clamp-1 mb-3">
                                        <?php echo htmlspecialchars(substr($notice['content'], 0, 150)); ?><?php echo strlen($notice['content']) > 150 ? '...' : ''; ?>
                                    </p>
                                    <div class="flex items-center text-xs text-slate-400 gap-4 flex-wrap">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white" style="background-color: <?php echo $cat_color; ?>">
                                                <i class="fa-solid fa-tag text-[7px]"></i>
                                            </span>
                                            <?php echo htmlspecialchars($notice['category_name'] ?? 'General'); ?>
                                        </span>
                                        <span><i class="far fa-user mr-1"></i><?php echo htmlspecialchars($notice['author_name'] ?? 'Admin'); ?></span>
                                        <span><i class="far fa-calendar mr-1"></i><?php echo date('d M Y', strtotime($notice['created_at'])); ?></span>
                                        <?php if (!empty($notice['publish_date'])): ?>
                                            <span><i class="far fa-clock mr-1"></i><?php echo date('d M Y, h:i A', strtotime($notice['publish_date'])); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($notice['target_role']) && $notice['target_role'] !== 'all'): ?>
                                            <span class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded text-[10px] font-medium uppercase"><?php echo $notice['target_role']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($has_attachment): ?>
                                            <span class="inline-flex items-center gap-1 text-blue-500">
                                                <i class="fa-solid fa-paperclip"></i><?php echo strtoupper(htmlspecialchars($notice['file_type'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <a href="editnotice.php?id=<?php echo $notice['id']; ?>&from=manage"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition"
                                        title="Edit Notice">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </a>
                                    <a href="deletenotice.php?id=<?php echo $notice['id']; ?>"
                                        onclick="return confirm('Are you sure you want to delete this notice?');"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition"
                                        title="Delete Notice">
                                        <i class="fa-solid fa-trash text-sm"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-search text-2xl text-slate-300"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-700 mb-1">
                <?php echo ($search !== '' || $status !== '' || $category > 0 || $role !== '' || $type !== '') ? 'No Matching Notices' : 'No Notices Yet'; ?>
            </h3>
            <p class="text-sm text-slate-400 mb-4">
                <?php echo ($search !== '' || $status !== '' || $category > 0 || $role !== '' || $type !== '') ? 'Try adjusting your search or filters.' : 'Create your first notice to get started.'; ?>
            </p>
            <?php if ($search !== '' || $status !== '' || $category > 0 || $role !== '' || $type !== ''): ?>
                <a href="manage_notice.php"
                    class="inline-flex items-center gap-2 bg-slate-100 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-200 transition">
                    <i class="fa-solid fa-xmark"></i> Clear Filters
                </a>
            <?php else: ?>
                <a href="addnotice.php"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    <i class="fa-solid fa-plus"></i> Create Notice
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</div>
