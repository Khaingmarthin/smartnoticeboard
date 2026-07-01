<?php
include ('../includes/header.php');
include('../config/db.php');

$query = "SELECT notices.*,
                 categories.name AS category_name,
                 categories.bg_color_code,
                 users.name AS author_name
          FROM notices
          LEFT JOIN categories ON notices.category_id = categories.id
          LEFT JOIN users ON notices.user_id = users.id
          ORDER BY notices.created_at DESC";

$result = mysqli_query($conn, $query);

$notices = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notices[] = $row;
    }
}

// Status badge helper
function statusBadge($status) {
    $map = [
        'published' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-circle-check'],
        'draft'     => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'fa-clock'],
        'expired'   => ['bg' => 'bg-slate-100 text-slate-500 border-slate-200', 'icon' => 'fa-circle-xmark'],
    ];
    $s = $map[$status] ?? $map['draft'];
    return '<span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full border ' . $s['bg'] . '"><i class="fa-solid ' . $s['icon'] . ' text-[9px]"></i> ' . ucfirst($status) . '</span>';
}
?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manage Notices</h1>
            <p class="text-sm text-slate-500">View, edit, and organize all system notices</p>
        </div>
        <a href="addnotice.php"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-sm">
            <i class="fa-solid fa-plus"></i> Add Notice
        </a>
    </div>

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
                        <!-- Color bar -->
                        <div class="w-1.5 flex-shrink-0" style="background-color: <?php echo $cat_color; ?>"></div>

                        <div class="flex-1 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <!-- Left: Notice info -->
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

                                <!-- Right: Actions -->
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
                <i class="fa-solid fa-file-circle-plus text-2xl text-slate-300"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-700 mb-1">No Notices Yet</h3>
            <p class="text-sm text-slate-400 mb-4">Create your first notice to get started.</p>
            <a href="addnotice.php"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fa-solid fa-plus"></i> Create Notice
            </a>
        </div>
    <?php endif; ?>
</div>
</div>
