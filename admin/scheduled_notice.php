<?php
include '../includes/header.php';
include('../config/db.php');

// Scheduled notices = featured notices that haven't been published yet
// (is_featured = 1 AND publish_date is in the future)
$query = "SELECT notices.*,
                 categories.name AS category_name,
                 categories.bg_color_code,
                 users.name AS author_name
          FROM notices
          LEFT JOIN categories ON notices.category_id = categories.id
          LEFT JOIN users ON notices.user_id = users.id
          WHERE notices.is_featured = 1
            AND notices.publish_date IS NOT NULL
            AND notices.publish_date > NOW()
          ORDER BY notices.publish_date ASC";

$result = mysqli_query($conn, $query);

$notices = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notices[] = $row;
    }
}
?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Scheduled Notices</h1>
            <p class="text-sm text-slate-500">Featured notices queued for future publication</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-amber-50 text-amber-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-amber-100">
                <i class="fa-solid fa-clock mr-1"></i>
                <?php echo count($notices); ?> Pending
            </span>
            <a href="addnotice.php"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-sm">
                <i class="fa-solid fa-plus"></i> New Notice
            </a>
        </div>
    </div>

    <?php if (!empty($notices)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($notices as $notice):
                $publish = strtotime($notice['publish_date']);
                $now = time();
                $diff = $publish - $now;
                $days = floor($diff / 86400);
                $hours = floor(($diff % 86400) / 3600);
                $minutes = floor(($diff % 3600) / 60);

                if ($days > 0) {
                    $time_left = $days . 'd ' . $hours . 'h';
                } elseif ($hours > 0) {
                    $time_left = $hours . 'h ' . $minutes . 'm';
                } else {
                    $time_left = $minutes . 'm';
                }

                $cat_color = htmlspecialchars($notice['bg_color_code'] ?? '#64748B');
            ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition">
                    <div class="h-2" style="background-color: <?php echo $cat_color; ?>"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <span class="text-[11px] font-bold text-white px-2.5 py-0.5 rounded shadow-sm"
                                style="background-color: <?php echo $cat_color; ?>">
                                <?php echo htmlspecialchars($notice['category_name'] ?? 'General'); ?>
                            </span>
                            <span class="text-[11px] font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">
                                <i class="fa-solid fa-hourglass-half mr-1"></i> <?php echo $time_left; ?> left
                            </span>
                        </div>

                        <h3 class="font-bold text-slate-800 text-base mb-2 line-clamp-2">
                            <?php echo htmlspecialchars($notice['title']); ?>
                        </h3>
                        <p class="text-xs text-slate-500 mb-4 line-clamp-2">
                            <?php echo htmlspecialchars(substr($notice['content'], 0, 100)); ?><?php echo strlen($notice['content']) > 100 ? '...' : ''; ?>
                        </p>

                        <div class="flex items-center text-xs text-slate-400 gap-3 mb-4">
                            <span><i class="far fa-user mr-1"></i><?php echo htmlspecialchars($notice['author_name'] ?? 'Admin'); ?></span>
                            <span><i class="far fa-calendar mr-1"></i><?php echo date('d M Y', $publish); ?></span>
                            <span><i class="far fa-clock mr-1"></i><?php echo date('h:i A', $publish); ?></span>
                        </div>

                        <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                            <a href="editnotice.php?id=<?php echo $notice['id']; ?>&from=scheduled"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 bg-blue-50 text-blue-600 py-2 rounded-lg text-xs font-medium hover:bg-blue-600 hover:text-white transition">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </a>
                            <a href="deletenotice.php?id=<?php echo $notice['id']; ?>"
                                onclick="return confirm('Delete this scheduled notice?');"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 bg-red-50 text-red-600 py-2 rounded-lg text-xs font-medium hover:bg-red-600 hover:text-white transition">
                                <i class="fa-solid fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-calendar-check text-2xl text-slate-300"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-700 mb-1">No Scheduled Notices</h3>
            <p class="text-sm text-slate-400 mb-4">No featured notices are queued for future publication.</p>
            <a href="addnotice.php"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fa-solid fa-plus"></i> Create Featured Notice
            </a>
        </div>
    <?php endif; ?>
</div>
</div>
