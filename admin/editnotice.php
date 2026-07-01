<?php
include('../config/db.php');

$from = isset($_GET['from']) ? $_GET['from'] : 'manage';
$redirect_to = ($from === 'dashboard') ? 'admindashboard.php' : ($from === 'scheduled' ? 'scheduled_notice.php' : 'manage_notice.php');

// Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notice_id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category_id = intval($_POST['category_id']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $target_role = isset($_POST['target_role']) ? mysqli_real_escape_string($conn, $_POST['target_role']) : 'all';
    $target_year_id = !empty($_POST['target_year_id']) && $_POST['target_year_id'] !== 'all' ? intval($_POST['target_year_id']) : "NULL";

    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Date handling — only set publish_date if featured, otherwise preserve existing
    if ($is_featured && !empty($_POST['publish_date'])) {
        $publish_date = "'" . str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['publish_date'])) . "'";
    } else {
        $publish_date = "publish_date"; // keep existing value
    }

    $expire_date = !empty($_POST['expire_date']) ? "'" . str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['expire_date'])) . "'" : "NULL";

    // Auto-determine status
    $current_timestamp = time();
    $publish_raw = $is_featured && !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
    if ($publish_raw && strtotime($publish_raw) > $current_timestamp) {
        $status = 'draft';
    } elseif (!empty($_POST['expire_date']) && strtotime($_POST['expire_date']) <= $current_timestamp) {
        $status = 'expired';
    } else {
        $status = 'published';
    }

    $update_query = "UPDATE notices SET
                        title = '$title',
                        category_id = $category_id,
                        content = '$content',
                        is_urgent = $is_urgent,
                        is_featured = $is_featured,
                        publish_date = $publish_date,
                        expire_date = $expire_date,
                        status = '$status',
                        target_role = '$target_role',
                        target_year_id = $target_year_id
                     WHERE id = $notice_id";

    if (mysqli_query($conn, $update_query)) {
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['attachment']['tmp_name'];
            $file_ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = '../uploads/' . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                mysqli_query($conn, "DELETE FROM attachments WHERE notice_id = $notice_id");
                $file_clean = mysqli_real_escape_string($conn, $new_file_name);
                $type_clean = mysqli_real_escape_string($conn, $file_ext);
                mysqli_query($conn, "INSERT INTO attachments (notice_id, file_path, file_type) VALUES ($notice_id, '$file_clean', '$type_clean')");
            }
        }

        header("Location: " . $redirect_to);
        exit();
    } else {
        die("Failed to update: " . mysqli_error($conn));
    }
}

// GET — load notice data
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: " . $redirect_to);
    exit();
}

$notice_id = intval($_GET['id']);
$query = "SELECT n.*, c.name AS category_name, a.file_path, a.file_type
          FROM notices n
          LEFT JOIN categories c ON n.category_id = c.id
          LEFT JOIN attachments a ON n.id = a.notice_id
          WHERE n.id = $notice_id";

$result = mysqli_query($conn, $query);
$notice = mysqli_fetch_assoc($result);

if (!$notice) {
    die("Notice not found.");
}

$cat_result = mysqli_query($conn, "SELECT * FROM categories");
$year_result = mysqli_query($conn, "SELECT * FROM academic_years");

include('../includes/header.php');
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="<?php echo $redirect_to; ?>" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition">
            <i class="fa-solid fa-arrow-left text-xs"></i> Back
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-100">
        <h2 class="text-2xl font-bold text-slate-900 mb-6">Edit Notice</h2>

        <form action="editnotice.php?id=<?php echo $notice_id; ?>&from=<?php echo htmlspecialchars($from); ?>" method="POST"
            enctype="multipart/form-data" class="space-y-5">

            <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notice Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($notice['title']); ?>" required
                    class="w-full border border-slate-200 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                <select name="category_id" required
                    class="w-full border border-slate-200 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select --</option>
                    <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $notice['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="flex space-x-6 py-2">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_urgent" id="is_urgent" value="1"
                        onchange="handleCheckboxToggle('urgent')" <?php echo !empty($notice['is_urgent']) ? 'checked' : ''; ?>
                        class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-slate-300">
                    <span class="text-sm font-medium text-slate-700">Mark as Urgent</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_featured" id="is_featured" value="1"
                        onchange="handleCheckboxToggle('featured')" <?php echo !empty($notice['is_featured']) ? 'checked' : ''; ?>
                        class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                    <span class="text-sm font-medium text-slate-700">Featured (Scheduled)</span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div id="featured_publish_section" class="<?php echo !empty($notice['is_featured']) ? '' : 'hidden'; ?>">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Publish Date & Time</label>
                    <input type="datetime-local" name="publish_date" id="publish_date"
                        value="<?php echo !empty($notice['publish_date']) ? date('Y-m-d\TH:i', strtotime($notice['publish_date'])) : ''; ?>"
                        class="w-full border border-slate-200 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-slate-400 mt-1">Notice publishes automatically at this time.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Expiration Date & Time</label>
                    <input type="datetime-local" name="expire_date" id="expire_date"
                        value="<?php echo !empty($notice['expire_date']) ? date('Y-m-d\TH:i', strtotime($notice['expire_date'])) : ''; ?>"
                        class="w-full border border-slate-200 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Attachment</label>
                <?php if (!empty($notice['file_path'])): ?>
                    <div class="mb-3 p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-3 text-sm text-slate-600">
                            <i class="fa-solid fa-file text-blue-500"></i>
                            <span class="font-medium truncate max-w-xs"><?php echo htmlspecialchars($notice['file_path']); ?></span>
                            <span class="text-xs bg-slate-200 text-slate-600 px-2 py-0.5 rounded uppercase"><?php echo htmlspecialchars($notice['file_type']); ?></span>
                        </div>
                        <a href="../uploads/<?php echo htmlspecialchars($notice['file_path']); ?>" target="_blank"
                            class="text-blue-600 hover:underline text-sm font-medium flex items-center gap-1">
                            <i class="fa-solid fa-eye text-xs"></i> View
                        </a>
                    </div>
                <?php endif; ?>
                <input type="file" name="attachment" accept="image/*,.pdf,.doc,.docx"
                    class="w-full border border-slate-200 rounded-lg p-2 bg-white text-sm file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Content</label>
                <textarea name="content" required rows="5"
                    class="w-full border border-slate-200 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none"><?php echo htmlspecialchars($notice['content']); ?></textarea>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium text-sm shadow-sm cursor-pointer">
                    Save Changes
                </button>
                <a href="<?php echo $redirect_to; ?>"
                    class="bg-slate-100 text-slate-600 px-6 py-2.5 rounded-lg hover:bg-slate-200 transition font-medium text-sm">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const expireInput = document.getElementById('expire_date');
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const minDT = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;

    if (expireInput) {
        expireInput.min = minDT;
        expireInput.addEventListener('change', function () {
            if (this.value && this.value < minDT) {
                alert("Expiration date cannot be set in the past.");
                this.value = "";
            }
        });
    }
});

function handleCheckboxToggle(type) {
    const u = document.getElementById('is_urgent');
    const f = document.getElementById('is_featured');
    const p = document.getElementById('featured_publish_section');
    if (type === 'urgent' && u.checked) { f.checked = false; if(p) p.classList.add('hidden'); }
    else if (type === 'featured' && f.checked) { u.checked = false; if(p) p.classList.remove('hidden'); }
    else if (type === 'featured' && !f.checked) { if(p) p.classList.add('hidden'); }
}
</script>
