<?php
include('../config/db.php');
include_once('../includes/header.php');

// Handle edit mode: fetch category data if edit requested
$edit_mode = false;
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $edit_query = "SELECT * FROM categories WHERE id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    if (mysqli_num_rows($edit_result) > 0) {
        $edit_category = mysqli_fetch_assoc($edit_result);
        $edit_mode = true;
    }
}

// 1. Fetch categories along with their dynamic notice counts using a LEFT JOIN
$cat_query = "SELECT c.*, COUNT(n.id) AS notice_count
              FROM categories c
              LEFT JOIN notices n ON c.id = n.category_id
              GROUP BY c.id";
$cat_result = mysqli_query($conn, $cat_query);

// Category styles with alias matching (handles singular/plural variants)
$category_styles = [
    'general'   => ['icon' => 'fa-bullhorn',      'color' => '#4F46E5', 'bg' => 'bg-indigo-600', 'desc' => 'Standard institutional alerts'],
    'exam'      => ['icon' => 'fa-file-signature', 'color' => '#DC2626', 'bg' => 'bg-red-600',    'desc' => 'Schedules, seating, and updates'],
    'timetables'=> ['icon' => 'fa-calendar-week',  'color' => '#9333EA', 'bg' => 'bg-purple-600', 'desc' => 'Class & routine schedules'],
    'timetable' => ['icon' => 'fa-calendar-week',  'color' => '#9333EA', 'bg' => 'bg-purple-600', 'desc' => 'Class & routine schedules'],
    'schedule'  => ['icon' => 'fa-calendar-week',  'color' => '#9333EA', 'bg' => 'bg-purple-600', 'desc' => 'Class & routine schedules'],
    'event'     => ['icon' => 'fa-calendar-check', 'color' => '#D97706', 'bg' => 'bg-amber-500',  'desc' => 'Seminars, holidays, and activities'],
    'events'    => ['icon' => 'fa-calendar-check', 'color' => '#D97706', 'bg' => 'bg-amber-500',  'desc' => 'Seminars, holidays, and activities'],
];
?>
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manage Categories</h1>
            <p class="text-sm text-slate-500">Organize notice boards and view publication statistics</p>
        </div>
        <span class="bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-blue-100">
            <?php echo mysqli_num_rows($cat_result); ?> Active Categories
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Current System Categories</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php while ($cat = mysqli_fetch_assoc($cat_result)):
                    // Lowercase the name to match our style mapper arrays safely
                    $slug = strtolower($cat['name']);
                    $style = isset($category_styles[$slug]) ? $category_styles[$slug] : ['icon' => 'fa-folder', 'color' => '#64748B', 'bg' => 'bg-slate-500', 'desc' => 'Custom notice collection'];
                    ?>
                    <div
                        class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between hover:shadow-md transition">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 rounded-lg flex items-center justify-center text-white <?php echo $style['bg']; ?> shadow-sm">
                                    <i class="fa-solid <?php echo $style['icon']; ?> text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-base capitalize">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </h3>
                                    <p class="text-xs text-slate-400"><?php echo $style['desc']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-50 pt-4 mt-6">
                            <span class="text-xs text-slate-500 font-medium">
                                <i class="far fa-file-alt mr-1"></i>
                                <?php echo $cat['notice_count']; ?>
                                Notice<?php echo ($cat['notice_count'] != 1) ? 's' : ''; ?>
                            </span>

                            <div class="flex space-x-1">
                                <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>"
                                    class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-slate-50 rounded-md transition"><i
                                        class="fas fa-edit text-sm"></i></a>
                                <button type="button"
                                    onclick="confirmDelete(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>', <?php echo $cat['notice_count']; ?>)"
                                    class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-slate-50 rounded-md transition"><i
                                        class="fas fa-trash text-sm"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="space-y-4">
            <h2 id="form-title" class="text-sm font-bold text-slate-400 uppercase tracking-wider">
                <?php echo $edit_mode ? 'Edit Category' : 'Add New Category'; ?>
            </h2>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 mt-4">
                <?php if (isset($_SESSION['flash_msg'])):
                    $is_error = ($_SESSION['flash_type'] === 'error');
                    $flash_msg = $_SESSION['flash_msg'];
                    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
                ?>
                    <div class="mb-4 p-3 rounded-lg text-sm font-medium flex items-center gap-2 <?php echo $is_error ? 'bg-rose-50 border border-rose-200 text-rose-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'; ?>">
                        <i class="fa-solid <?php echo $is_error ? 'fa-triangle-exclamation text-rose-500' : 'fa-circle-check text-emerald-500'; ?>"></i>
                        <?php echo htmlspecialchars($flash_msg); ?>
                    </div>
                <?php endif; ?>

                <form action="process_category.php" method="POST" class="space-y-4">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">Category
                            Name</label>

                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <span class="text-[11px] font-medium text-slate-400 mr-1 self-center">Presets:</span>
                            <button type="button" onclick="applyPreset('General', '#4F46E5')"
                                class="text-xs bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-600 px-2 py-0.5 rounded-md transition font-medium cursor-pointer">General</button>
                            <button type="button" onclick="applyPreset('Exam', '#DC2626')"
                                class="text-xs bg-slate-100 hover:bg-red-50 hover:text-red-600 text-slate-600 px-2 py-0.5 rounded-md transition font-medium cursor-pointer">Exam</button>
                            <button type="button" onclick="applyPreset('Timetables', '#9333EA')"
                                class="text-xs bg-slate-100 hover:bg-purple-50 hover:text-purple-600 text-slate-600 px-2 py-0.5 rounded-md transition font-medium cursor-pointer">Timetables</button>
                            <button type="button" onclick="applyPreset('Event', '#D97706')"
                                class="text-xs bg-slate-100 hover:bg-amber-50 hover:text-amber-600 text-slate-600 px-2 py-0.5 rounded-md transition font-medium cursor-pointer">Event</button>
                        </div>

                        <input type="text" name="category_name" id="category_name_input" placeholder="e.g., Sports News"
                            value="<?php echo $edit_mode ? htmlspecialchars($edit_category['name']) : ''; ?>"
                            required
                            class="w-full text-sm border border-slate-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">Theme
                            Color
                            Code</label>
                        <div class="flex gap-2">
                            <input type="color" name="bg_color_code" id="category_color"
                                value="<?php echo $edit_mode ? htmlspecialchars($edit_category['bg_color_code']) : '#4F46E5'; ?>"
                                class="w-12 h-10 border border-slate-300 rounded-lg p-1 bg-white cursor-pointer">
                            <input type="text" id="category_color_text"
                                value="<?php echo $edit_mode ? htmlspecialchars($edit_category['bg_color_code']) : '#4F46E5'; ?>"
                                placeholder="#4F46E5" readonly
                                class="w-full text-sm border border-slate-300 rounded-lg p-2.5 uppercase tracking-wider text-slate-500 bg-slate-50/50 outline-none">
                        </div>
                    </div>

                    <div class="pt-2 flex gap-2">
                        <button type="submit"
                            class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg font-medium text-sm hover:bg-blue-700 transition shadow-sm cursor-pointer">
                            <?php echo $edit_mode ? 'Update Category' : 'Save Category'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="categories.php"
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full shadow-xl overflow-hidden">
        <div class="p-6 text-center">
            <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-trash text-xl text-red-600"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Delete Category</h3>
            <p class="text-sm text-slate-500">Are you sure you want to delete <span id="deleteCatName" class="font-semibold text-slate-700"></span>?</p>
        </div>
        <div class="px-6 pb-6 flex gap-3">
            <button onclick="closeDeleteModal()"
                class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-50 transition cursor-pointer">
                Cancel
            </button>
            <a id="deleteModalLink" href="#" class="flex-1 px-4 py-2.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition text-center">
                Delete
            </a>
        </div>
    </div>
</div>

<script>
    // Color picker sync
    const colorPicker = document.getElementById('category_color');
    const colorText = document.getElementById('category_color_text');
    if (colorPicker && colorText) {
        colorPicker.addEventListener('input', function () {
            colorText.value = this.value.toUpperCase();
        });
    }

    // Preset buttons
    function applyPreset(name, color) {
        document.getElementById('category_name_input').value = name;
        document.getElementById('category_color').value = color;
        document.getElementById('category_color_text').value = color.toUpperCase();
    }

    // Delete confirmation modal
    function confirmDelete(id, name, noticeCount) {
        if (noticeCount > 0) {
            alert('Cannot delete "' + name + '": it still has ' + noticeCount + ' notice(s) assigned to it. Reassign or remove those notices first.');
            return;
        }
        document.getElementById('deleteCatName').textContent = name;
        document.getElementById('deleteModalLink').href = 'process_category.php?action=delete&id=' + id;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal on backdrop click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>