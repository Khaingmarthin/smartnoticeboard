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
$featuredNotice = $pdo->query("SELECT * FROM notices WHERE is_featured = 1  AND status = 'published' ORDER BY publish_date DESC LIMIT 2;")->fetch(PDO::FETCH_ASSOC);
$urgentNotice = $pdo->query("
    SELECT n.*, c.name as category_name, c.bg_color_code 
    FROM notices n 
    LEFT JOIN categories c ON n.category_id = c.id 
    WHERE n.is_urgent = 1 
    ORDER BY n.created_at DESC LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$activities = $pdo->query("SELECT action, created_at FROM history ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-xl"><i
                class="fas fa-file-alt"></i></div>
        <div>
            <p class="text-sm text-gray-500">Total Notices</p>
            <p class="text-2xl font-bold">
                <?php echo number_format($totalNotices); ?>
            </p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center text-xl"><i
                class="fas fa-user-graduate"></i></div>
        <div>
            <p class="text-sm text-gray-500">Students</p>
            <p class="text-2xl font-bold">
                <?php echo number_format($totalStudents); ?>
            </p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-blue-100 text-blue-500 rounded-lg flex items-center justify-center text-xl"><i
                class="fas fa-chalkboard-teacher"></i></div>
        <div>
            <p class="text-sm text-gray-500">Admins</p>
            <p class="text-2xl font-bold">
                <?php echo number_format($totalAdmins); ?>
            </p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 bg-orange-100 text-orange-500 rounded-lg flex items-center justify-center text-xl"><i
                class="fas fa-calendar-check"></i></div>
        <div>
            <p class="text-sm text-gray-500">Scheduled Notices</p>
            <p class="text-2xl font-bold">
                <?php echo number_format($scheduledNotices); ?>
            </p>
        </div>
    </div>
</div>

<div class="flex flex-col lg:flex-row gap-6">
    <div class="flex-1 lg:w-2/3">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">Recently Posted Notices</h2>
            <a href="manage_notice.php" class="text-sm font-semibold text-blue-600 hover:underline">View All</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (empty($recentNotices)): ?>
                <p class="text-gray-500 col-span-3">No notices found in database.</p>
            <?php else: ?>
                <?php foreach ($recentNotices as $notice): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div
                            class="h-32 bg-slate-50 relative flex items-center justify-center overflow-hidden border-b border-gray-100">
                            <span class="absolute top-2 left-3 text-xs font-bold text-white px-2.5 py-1 rounded shadow-sm z-10"
                                style="background-color: <?php echo htmlspecialchars($notice['bg_color_code'] ?? '#4F46E5'); ?>">
                                <?php echo htmlspecialchars($notice['category_name'] ?? 'General'); ?>
                            </span>

                            <?php
                            // 1. Identify if a valid image upload exists
                            $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            $file_ext = !empty($notice['file_type']) ? strtolower($notice['file_type']) : '';
                            $is_image = !empty($notice['file_path']) && in_array($file_ext, $allowed_images);

                            if ($is_image): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($notice['file_path']); ?>"
                                    class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                    alt="<?php echo htmlspecialchars($notice['title']); ?>">

                            <?php else: ?>
                                <div class=" text-center flex flex-col items-center justify-center opacity-75">
                                    <?php
                                    $cat_lower = strtolower($notice['category_name'] ?? '');
                                    if (strpos($cat_lower, 'exam') !== false):
                                        ?>
                                        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mb-1">
                                            <i class="fa-solid fa-file-signature text-xl text-red-600"></i>
                                        </div>
                                    <?php elseif (strpos($cat_lower, 'timetable') !== false || strpos($cat_lower, 'schedule') !== false): ?>
                                        <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center mb-1">
                                            <i class="fa-solid fa-calendar-week text-xl text-purple-600"></i>
                                        </div>
                                    <?php elseif (strpos($cat_lower, 'event') !== false): ?>
                                        <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center mb-1">
                                            <i class="fa-solid fa-calendar-check text-xl text-amber-600"></i>
                                        </div>
                                    <?php else: // General or default fallback info icon ?>
                                        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center mb-1">
                                            <i class="fa-solid fa-bullhorn text-xl text-blue-600"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-gray-800 text-sm mb-1 line-clamp-2">
                                <?php echo htmlspecialchars($notice['title']); ?>
                            </h3>
                            <p class="text-xs text-gray-600 mt-1">
                                <?php echo htmlspecialchars(substr($notice['content'], 0, 50) . '...'); ?>
                            </p>

                            <?php if (!empty($notice['file_path'])): ?>
                                <div class="mt-2 text-xs">
                                    <a href="../uploads/<?php echo htmlspecialchars($notice['file_path']); ?>" target="_blank"
                                        class="text-blue-600 hover:underline font-medium">
                                        <i class="fas fa-paperclip mr-1"></i> View File
                                        (
                                        <?php echo htmlspecialchars($notice['file_type']); ?>)
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between text-xs text-gray-500 my-3 border-t pt-2">
                                <span>
                                    <?php echo htmlspecialchars($notice['author_name'] ?? 'Admin'); ?>
                                </span>
                                <span>
                                    <?php echo date('d M Y', strtotime($notice['created_at'])); ?>
                                </span>
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    onclick="openNoticeModal(<?php echo htmlspecialchars(json_encode($notice), ENT_QUOTES, 'UTF-8'); ?>)"
                                    class="flex-1 bg-blue-50 text-blue-600 py-1.5 rounded-lg text-sm hover:bg-blue-100 transition flex items-center justify-center cursor-pointer">
                                    <i class=" fas fa-eye"></i>
                                </button>

                                <a href="editnotice.php?id=<?php echo $notice['id']; ?>"
                                    class="flex-1 bg-gray-50 text-gray-600 py-1.5 rounded-lg text-sm hover:bg-gray-100 transition flex items-center justify-center">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <a href="deletenotice.php?id=<?php echo $notice['id']; ?>"
                                    onclick="return confirm('Are you sure you want to permanently delete this notice? This action cannot be undone.');"
                                    class="flex-1 bg-red-50 text-red-600 py-1.5 rounded-lg text-sm hover:bg-red-100 transition flex items-center justify-center">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="w-full lg:w-80 flex flex-col gap-6 mx-auto">
        <?php if ($featuredNotice): ?>
            <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl p-6 text-white shadow-lg">
                <span
                    class="bg-yellow-400 text-yellow-900 text-[10px] font-bold px-2 py-1 rounded mb-3 inline-block">FEATURED</span>
                <h3 class="font-bold text-lg mb-2 line-clamp-2">
                    <?php echo htmlspecialchars($featuredNotice['title']); ?>
                </h3>
                <p class="text-sm text-indigo-100 mb-4 line-clamp-3">
                    <?php echo htmlspecialchars($featuredNotice['content']); ?>
                </p>
                <button type="button"
                    class="open-notice-btn bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                    data-title="<?php echo htmlspecialchars($featuredNotice['title']); ?>"
                    data-content="<?php echo htmlspecialchars($featuredNotice['content']); ?>"
                    data-category="<?php echo htmlspecialchars($featuredNotice['category_name'] ?? 'General'); ?>"
                    data-author="<?php echo htmlspecialchars($featuredNotice['author_name'] ?? 'Admin'); ?>"
                    data-date="<?php echo date('d M Y', strtotime($featuredNotice['publish_date'])); ?>">
                    View Notice
                </button>
            </div>
        <?php endif; ?>

        <!-- <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4">Latest Activities</h3>
            <div class="space-y-4">
                <?php if (empty($activities)): ?>
                    <p class="text-sm text-gray-500">No recent activities.</p>
                <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                        <div class="flex space-x-3">
                            <div
                                class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-history text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800"><?php echo htmlspecialchars($activity['action']); ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php echo date('d M Y, h:i A', strtotime($activity['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div> -->
    </div>
</div>

<?php if ($urgentNotice): ?>
    <div
        class="bg-red-600 text-white px-6 py-3 flex items-center justify-between absolute bottom-0 left-0 right-0 z-20 shadow-lg">
        <div class="flex items-center space-x-3 text-sm overflow-hidden">
            <i class="fas fa-volume-up animate-pulse"></i>
            <span class="font-bold whitespace-nowrap">URGENT NOTICE:</span>
            <span class="truncate">
                <?php echo htmlspecialchars($urgentNotice['title']); ?>
            </span>
        </div>

        <button onclick="openNoticeModal(<?php echo htmlspecialchars(json_encode($urgentNotice), ENT_QUOTES, 'UTF-8'); ?>)"
            class="text-sm font-semibold hover:underline flex items-center space-x-1 flex-shrink-0 ml-4 bg-transparent border-0 cursor-pointer text-white outline-none">
            <span>View</span> <i class="fas fa-arrow-right text-xs"></i>
        </button>
    </div>
<?php endif; ?>

<?php
// 5. Clean layout wrapper closures and Javascript Clock interval engines
include '../includes/footer.php';
?>
<div id="noticeModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden flex items-center justify-center p-4">
    <div
        class="bg-white rounded-xl max-w-2xl w-full shadow-xl relative overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200 max-h-[85vh]">

        <div class="p-6 border-b border-gray-100 flex items-start justify-between">
            <div>
                <span id="modalCategory"
                    class="text-[11px] font-bold text-white px-2.5 py-0.5 rounded shadow-sm inline-block mb-2"></span>
                <h3 id="modalTitle" class="text-xl font-bold text-gray-900 pr-6"></h3>
            </div>
            <button onclick="closeNoticeModal()" class="text-gray-400 hover:text-gray-600 text-lg p-1">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-6 flex-1">
            <div id="modalImageContainer"
                class="hidden w-full h-48 bg-gray-50 rounded-lg overflow-hidden border border-gray-100 mb-4">
                <img id="modalImage" src="" class="w-full h-full object-cover" alt="Notice Attachment">
            </div>

            <div>
                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Announcement Body</h4>
                <p id="modalContent" class="text-gray-700 text-sm leading-relaxed whitespace-pre-line"></p>
            </div>

            <div id="modalDocContainer"
                class="hidden p-3.5 bg-blue-50 rounded-xl border border-blue-100 flex items-center justify-between">
                <div class="flex items-center space-x-3 text-sm text-blue-700 truncate max-w-md">
                    <i class="fas fa-paperclip text-base"></i>
                    <span id="modalDocName" class="font-medium truncate"></span>
                </div>
                <a id="modalDocLink" href="" target="_blank"
                    class="bg-blue-600 text-white text-xs px-4 py-2 rounded-md hover:bg-blue-700 font-semibold transition">
                    View Document
                </a>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
                <div>
                    <p class="text-gray-400 font-medium mb-0.5">Posted By</p>
                    <p id="modalAuthor" class="font-semibold text-gray-800"></p>
                </div>
                <div>
                    <p class="text-gray-400 font-medium mb-0.5">Date Created</p>
                    <p id="modalDate" class="font-semibold text-gray-800"></p>
                </div>
                <div>
                    <p class="text-gray-400 font-medium mb-0.5">Target Audience</p>
                    <p id="modalRole" class="font-semibold text-gray-800 capitalize"></p>
                </div>
                <div>
                    <p class="text-gray-400 font-medium mb-0.5">Urgency Level</p>
                    <p id="modalUrgency" class="font-semibold"></p>
                </div>
            </div>
        </div>

        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
            <button onclick="closeNoticeModal()"
                class="bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium transition">Close</button>
            <a id="modalEditBtn" href=""
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">Edit
                Post</a>
        </div>
    </div>
</div>

<div id="noticeModal"
    class="fixed inset-0 bg-slate-900 bg-opacity-50 hidden items-center justify-center z-50 p-4 transition-opacity duration-300">
    <div
        class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden transform scale-95 transition-transform duration-300">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <span id="modalCategory"
                class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Category</span>
            <button onclick="closeNoticeModal()"
                class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
        </div>
        <div class="p-6">
            <h3 id="modalTitle" class="text-xl font-bold text-slate-800 mb-2">Notice Title</h3>
            <div class="flex items-center gap-4 text-xs text-slate-500 mb-4">
                <span id="modalAuthor">By Admin</span>
                <span id="modalDate">Date</span>
            </div>
            <p id="modalContent" class="text-slate-600 text-sm leading-relaxed whitespace-pre-line">Notice content goes
                here...</p>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
            <button onclick="closeNoticeModal()"
                class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-300 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>
<script>
    function openNoticeModal(notice) {
        // 1. Assign plaintext string metrics
        document.getElementById('modalTitle').innerText = notice.title;
        document.getElementById('modalContent').innerText = notice.content;
        document.getElementById('modalAuthor').innerText = notice.author_name || 'Admin';
        document.getElementById('modalRole').innerText = notice.target_role === 'all' ? 'All System Users' : notice.target_role;

        // 2. Format localized human-readable date 
        const dateObj = new Date(notice.created_at);
        document.getElementById('modalDate').innerText = dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

        // 3. Category Custom CSS Label mapping 
        const catBadge = document.getElementById('modalCategory');
        catBadge.innerText = notice.category_name || 'General';
        catBadge.style.backgroundColor = notice.bg_color_code || '#4F46E5';

        // 4. Evaluate Urgency styling alerts
        const urgencyLabel = document.getElementById('modalUrgency');
        if (parseInt(notice.is_urgent) === 1) {
            urgencyLabel.innerText = '⚠️ Critical / Urgent';
            urgencyLabel.className = 'font-semibold text-red-600';
        } else {
            urgencyLabel.innerText = 'Normal Announcement';
            urgencyLabel.className = 'font-semibold text-gray-700';
        }

        // 5. Update Quick Action Route link redirects
        document.getElementById('modalEditBtn').href = `editnotice.php?id=${notice.id}`;

        // 6. Inspect Dynamic Attachment Formats (Images vs Documents)
        const imgContainer = document.getElementById('modalImageContainer');
        const docContainer = document.getElementById('modalDocContainer');

        imgContainer.classList.add('hidden');
        docContainer.classList.add('hidden');

        if (notice.file_path) {
            const fileExt = notice.file_type ? notice.file_type.toLowerCase() : '';
            const isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt);

            if (isImg) {
                document.getElementById('modalImage').src = `../uploads/${notice.file_path}`;
                imgContainer.classList.remove('hidden');
            } else {
                document.getElementById('modalDocName').innerText = notice.file_path;
                document.getElementById('modalDocLink').href = `../uploads/${notice.file_path}`;
                docContainer.classList.remove('hidden');
            }
        }

        // Show overlay element container window
        document.getElementById('noticeModal').classList.remove('hidden');
    }

    function closeNoticeModal() {
        document.getElementById('noticeModal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('noticeModal');
        const modalBox = modal.querySelector('.transform');
        const buttons = document.querySelectorAll('.open-notice-btn');

        // Open Modal and Populate Data
        buttons.forEach(button => {
            button.addEventListener('click', function () {
                document.getElementById('modalTitle').textContent = this.getAttribute('data-title');
                document.getElementById('modalContent').textContent = this.getAttribute('data-content');
                document.getElementById('modalCategory').textContent = this.getAttribute('data-category');
                document.getElementById('modalAuthor').textContent = 'By ' + this.getAttribute('data-author');
                document.getElementById('modalDate').textContent = this.getAttribute('data-date');

                // Show classes
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                setTimeout(() => {
                    modalBox.classList.remove('scale-95');
                    modalBox.classList.add('scale-100');
                }, 10);
            });
        });

        // Close Modal when clicking outside the content container box
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeNoticeModal();
            }
        });
    });

    function closeNoticeModal() {
        const modal = document.getElementById('noticeModal');
        const modalBox = modal.querySelector('.transform');

        modalBox.classList.remove('scale-100');
        modalBox.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 150);
    }
</script>