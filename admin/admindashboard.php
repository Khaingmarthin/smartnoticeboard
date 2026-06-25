<?php

// 1. Include the structural template (Handles session, security check, PDO connection, and opening tags)
include '../includes/header.php';

// 2. Fetch Dashboard Statistics (Using the $pdo variable created inside header.php)
$totalNotices = $pdo->query("SELECT COUNT(*) FROM notices")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$scheduledNotices = $pdo->query("SELECT COUNT(*) FROM notices WHERE status = 'draft'")->fetchColumn();

// 3. Fetch Recent Notices joined with attachments and metadata
$stmt = $pdo->query("
    SELECT n.*, 
           c.name as category_name, 
           c.bg_color_code, 
           u.name as author_name,
           a.file_path, 
           a.file_type 
    FROM notices n 
    LEFT JOIN categories c ON n.category_id = c.id 
    LEFT JOIN users u ON n.user_id = u.id 
    LEFT JOIN attachments a ON n.id = a.notice_id
    WHERE n.status = 'published' 
    AND n.publish_date <= NOW() 
    ORDER BY n.created_at DESC 
    LIMIT 6
");
$recentNotices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch Featured, Urgent Notices, and Latest Activities
$featuredNotice = $pdo->query("SELECT * FROM notices WHERE is_featured = 1 AND publish_date <= NOW() ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$urgentNotice = $pdo->query("SELECT * FROM notices WHERE is_urgent = 1 ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$activities = $pdo->query("SELECT action, created_at FROM history ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-xl"><i
                class="fas fa-file-alt"></i></div>
        <div>
            <p class="text-sm text-gray-500">Total Notices</p>
            <p class="text-2xl font-bold"><?php echo number_format($totalNotices); ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center text-xl"><i
                class="fas fa-user-graduate"></i></div>
        <div>
            <p class="text-sm text-gray-500">Students</p>
            <p class="text-2xl font-bold"><?php echo number_format($totalStudents); ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-blue-100 text-blue-500 rounded-lg flex items-center justify-center text-xl"><i
                class="fas fa-chalkboard-teacher"></i></div>
        <div>
            <p class="text-sm text-gray-500">Admins</p>
            <p class="text-2xl font-bold"><?php echo number_format($totalAdmins); ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-orange-100 text-orange-500 rounded-lg flex items-center justify-center text-xl"><i
                class="fas fa-calendar-check"></i></div>
        <div>
            <p class="text-sm text-gray-500">Scheduled Notices</p>
            <p class="text-2xl font-bold"><?php echo number_format($scheduledNotices); ?></p>
        </div>
    </div>
</div>

<div class="flex flex-col lg:flex-row gap-8">
    <div class="flex-1">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">Recently Posted Notices</h2>
            <a href="manage_notice.php" class="text-sm font-semibold text-blue-600 hover:underline">View All</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($recentNotices)): ?>
                <p class="text-gray-500 col-span-3">No notices found in database.</p>
            <?php else: ?>
                <?php foreach ($recentNotices as $notice): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="h-32 bg-gray-200 relative">
                            <img src="../assets/login page.jpg" class="w-full h-full object-cover" alt="Notice Image">
                            <span class="absolute top-2 left-3 text-xs font-bold text-black px-2 py-1 rounded"
                                style="background-color: <?php echo htmlspecialchars($notice['bg_color_code'] ?? '#4F46E5'); ?>">
                                <?php echo htmlspecialchars($notice['category_name'] ?? 'General'); ?>
                            </span>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-gray-800 text-sm mb-1 line-clamp-2">
                                <?php echo htmlspecialchars($notice['title']); ?></h3>
                            <p class="text-xs text-gray-600 mt-1">
                                <?php echo htmlspecialchars(substr($notice['content'], 0, 50) . '...'); ?></p>

                            <?php if (!empty($notice['file_path'])): ?>
                                <div class="mt-2 text-xs">
                                    <a href="../uploads/<?php echo htmlspecialchars($notice['file_path']); ?>" target="_blank"
                                        class="text-blue-600 hover:underline font-medium">
                                        <i class="fas fa-paperclip mr-1"></i> View File
                                        (<?php echo htmlspecialchars($notice['file_type']); ?>)
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between text-xs text-gray-500 my-3 border-t pt-2">
                                <span><?php echo htmlspecialchars($notice['author_name'] ?? 'Admin'); ?></span>
                                <span><?php echo date('d M Y', strtotime($notice['created_at'])); ?></span>
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    class="flex-1 bg-blue-50 text-blue-600 py-1.5 rounded-lg text-sm hover:bg-blue-100 transition"><i
                                        class="fas fa-eye"></i></button>
                                <button
                                    class="flex-1 bg-gray-50 text-gray-600 py-1.5 rounded-lg text-sm hover:bg-gray-100 transition"><i
                                        class="fas fa-edit"></i></button>
                                <button
                                    class="flex-1 bg-red-50 text-red-600 py-1.5 rounded-lg text-sm hover:bg-red-100 transition"><i
                                        class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="w-full lg:w-80 flex flex-col gap-6">
        <?php if ($featuredNotice): ?>
            <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl p-6 text-white shadow-lg">
                <span
                    class="bg-yellow-400 text-yellow-900 text-[10px] font-bold px-2 py-1 rounded mb-3 inline-block">FEATURED</span>
                <h3 class="font-bold text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($featuredNotice['title']); ?>
                </h3>
                <p class="text-sm text-indigo-100 mb-4 line-clamp-3">
                    <?php echo htmlspecialchars($featuredNotice['content']); ?></p>
                <a href="view_notice.php?id=<?php echo $featuredNotice['id']; ?>"
                    class="bg-white text-indigo-600 font-semibold px-4 py-2 rounded-lg text-sm shadow hover:bg-indigo-50 inline-block">View
                    Notice</a>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4">Latest Activities</h3>
            <div class="space-y-4">
                <?php if (empty($activities)): ?>
                    <p class="text-sm text-gray-500">No recent activities.</p>
                <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                        <div class="flex space-x-3">
                            <div
                                class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-history text-xs"></i></div>
                            <div>
                                <p class="text-sm text-gray-800"><?php echo htmlspecialchars($activity['action']); ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php echo date('d M Y, h:i A', strtotime($activity['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($urgentNotice): ?>
    <div
        class="bg-red-600 text-white px-6 py-3 flex items-center justify-between absolute bottom-0 left-0 right-0 z-20 shadow-lg">
        <div class="flex items-center space-x-3 text-sm overflow-hidden">
            <i class="fas fa-volume-up animate-pulse"></i>
            <span class="font-bold whitespace-nowrap">URGENT NOTICE:</span>
            <span class="truncate"><?php echo htmlspecialchars($urgentNotice['title']); ?></span>
        </div>
        <a href="view_notice.php?id=<?php echo $urgentNotice['id']; ?>"
            class="text-sm font-semibold hover:underline flex items-center space-x-1 flex-shrink-0 ml-4">
            <span>View</span> <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>
<?php endif; ?>

<?php
// 5. Clean layout wrapper closures and Javascript Clock interval engines
include '../includes/footer.php';
?>