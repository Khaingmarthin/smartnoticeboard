<?php
include('../config/db.php');

// 1. Determine where the user came from BEFORE doing anything else
$from = isset($_GET['from']) ? $_GET['from'] : 'manage';
$redirect_to = ($from === 'dashboard') ? 'admindashboard.php' : 'manage_notice.php';

// 2. Form Submission Handling Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notice_id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category_id = intval($_POST['category_id']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $target_role = mysqli_real_escape_string($conn, $_POST['target_role']);
    $target_year_id = $_POST['target_year_id'] === 'all' ? 'NULL' : intval($_POST['target_year_id']);

    // Checkboxes default to 0 if unchecked
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Convert DateTime HTML local string formats safely to MySQL standards
    $publish_date = !empty($_POST['publish_date']) ? "'" . str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['publish_date'])) . "'" : "NOW()";
    $expire_date = !empty($_POST['expire_date']) ? "'" . str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['expire_date'])) . "'" : "NULL";

    // Update the master notice record fields
    $update_query = "UPDATE notices SET 
                        title = '$title', 
                        category_id = $category_id, 
                        content = '$content', 
                        is_urgent = $is_urgent, 
                        is_featured = $is_featured, 
                        publish_date = $publish_date, 
                        expire_date = $expire_date,
                        target_role = '$target_role',
                        target_year_id = $target_year_id
                     WHERE id = $notice_id";

    if (mysqli_query($conn, $update_query)) {
        // Handle new file attachments if uploaded
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['attachment']['name'];
            $file_tmp = $_FILES['attachment']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = '../uploads/' . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Remove older structural logs first
                mysqli_query($conn, "DELETE FROM attachments WHERE notice_id = $notice_id");
                // Insert the replacement details record
                mysqli_query($conn, "INSERT INTO attachments (notice_id, file_path, file_type) VALUES ($notice_id, '$new_file_name', '$file_ext')");
            }
        }

        // REDIRECT SUCCESS ROUTE: Returns back to originating page with alert flags
        header("Location: " . $redirect_to . "?updated=1");
        exit();
    } else {
        die("Failed to update notice database records: " . mysqli_error($conn));
    }
}

// 3. Regular GET Request Page Data Setup (When page is just opening)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: " . $redirect_to);
    exit();
}

$notice_id = intval($_GET['id']);

// Fetch active notice data rows
$query = "SELECT n.*, c.name AS category_name, a.file_path, a.file_type 
          FROM notices n 
          LEFT JOIN categories c ON n.category_id = c.id
          LEFT JOIN attachments a ON n.id = a.notice_id 
          WHERE n.id = $notice_id";

$result = mysqli_query($conn, $query);
$notice = mysqli_fetch_assoc($result);

if (!$notice) {
    die("Notice data not found within systems query parameters.");
}

$cat_result = mysqli_query($conn, "SELECT * FROM categories");
$year_result = mysqli_query($conn, "SELECT * FROM academic_years");

// Structural layout templates inside header
include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Notice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-slate-50 p-6">

    <div class="max-w-4xl ml-6 bg-white p-8 rounded-xl shadow-sm border border-slate-100">
        <h2 class="text-2xl font-bold text-slate-950 mb-6">Edit Notice</h2>

        <form action="editnotice.php?id=<?php echo $notice_id; ?>&from=<?php echo $from; ?>" method="POST"
            enctype="multipart/form-data" class="space-y-5">

            <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notice Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($notice['title']); ?>" required
                    class="w-full border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category_id" required
                    class="w-full border border-gray-300 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select a Category --</option>
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
                        onclick="handleCheckboxToggle('urgent')" <?php echo (isset($notice['is_urgent']) && (int) $notice['is_urgent'] === 1) ? 'checked' : ''; ?>
                        class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-slate-300">
                    <span class="text-sm font-medium text-slate-700">Mark as Urgent</span>
                </label>

                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_featured" id="is_featured" value="1"
                        onclick="handleCheckboxToggle('featured')" <?php echo (isset($notice['is_featured']) && (int) $notice['is_featured'] === 1) ? 'checked' : ''; ?>
                        class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                    <span class="text-sm font-medium text-slate-700">Featured Post</span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Publish Date & Time</label>
                    <input type="datetime-local" name="publish_date" id="publish_date"
                        value="<?php echo !empty($notice['publish_date']) ? date('Y-m-d\TH:i', strtotime($notice['publish_date'])) : ''; ?>"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Expiration Date & Time</label>
                    <input type="datetime-local" name="expire_date" id="expire_date"
                        value="<?php echo !empty($notice['expire_date']) ? date('Y-m-d\TH:i', strtotime($notice['expire_date'])) : ''; ?>"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Target Role</label>
                    <select name="target_role" required
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo (isset($notice['target_role']) && $notice['target_role'] == 'all') ? 'selected' : ''; ?>>All Roles</option>
                        <option value="student" <?php echo (isset($notice['target_role']) && $notice['target_role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="admin" <?php echo (isset($notice['target_role']) && $notice['target_role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Target Year (Optional)</label>
                    <select name="target_year_id"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo (empty($notice['target_year_id'])) ? 'selected' : ''; ?>>All
                            Academic Years</option>
                        <?php
                        mysqli_data_seek($year_result, 0);
                        while ($year = mysqli_fetch_assoc($year_result)): ?>
                            <option value="<?php echo $year['id']; ?>" <?php echo (isset($notice['target_year_id']) && $year['id'] == $notice['target_year_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year['year_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Attachment (Image or Document)</label>
                <?php if (!empty($notice['file_path'])): ?>
                    <div class="mb-3 p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-3 text-sm text-slate-600">
                            <i class="fa-solid fa-file-shield text-blue-500 text-lg"></i>
                            <span
                                class="font-medium truncate max-w-xs"><?php echo htmlspecialchars($notice['file_path']); ?></span>
                            <span
                                class="text-xs bg-slate-200 text-slate-700 px-2 py-0.5 rounded-md uppercase"><?php echo htmlspecialchars($notice['file_type']); ?></span>
                        </div>
                        <a href="../uploads/<?php echo $notice['file_path']; ?>" target="_blank"
                            class="text-blue-600 hover:underline text-sm font-medium flex items-center gap-1">
                            <i class="fa-solid fa-eye text-xs"></i> View Current File
                        </a>
                    </div>
                <?php endif; ?>

                <div
                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-lg bg-slate-50 hover:bg-slate-100 transition duration-150 relative">
                    <div class="space-y-1 text-center">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-400 mb-2 block"></i>
                        <div class="flex text-sm text-slate-600 justify-center">
                            <label for="attachment"
                                class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                <span id="upload-label-text">Upload a new file replacement</span>
                                <input id="attachment" name="attachment" type="file" accept="image/*,.pdf,.doc,.docx"
                                    class="sr-only">
                            </label>
                        </div>
                        <p class="text-xs text-slate-400">PNG, JPG, PDF, DOCX up to 10MB</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Content</label>
                <textarea name="content" required rows="5"
                    class="w-full border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none"><?php echo htmlspecialchars($notice['content']); ?></textarea>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium text-sm shadow-sm cursor-pointer">
                        Save Changes
                    </button>
                    <a href="<?php echo $redirect_to; ?>"
                        class="bg-slate-100 text-slate-600 px-6 py-2.5 rounded-lg hover:bg-slate-200 transition font-medium text-sm text-center">
                        Cancel
                    </a>
                </div>
                <!-- <div>
                    <a href="<?php echo $redirect_to; ?>"
                        class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div> -->
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const expireInput = document.getElementById('expire_date');
            const fileInput = document.getElementById('attachment');
            const labelText = document.getElementById('upload-label-text');

            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const currentMinDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;

            if (expireInput) {
                expireInput.min = currentMinDateTime;
                expireInput.addEventListener('change', function () {
                    if (this.value && this.value < currentMinDateTime) {
                        alert("Error: The expiration date cannot be set in the past.");
                        this.value = "";
                    }
                });
            }

            if (fileInput && labelText) {
                fileInput.addEventListener('change', function () {
                    if (this.files.length > 0) {
                        labelText.innerText = "Selected: " + this.files[0].name;
                        labelText.className = "text-emerald-600 font-semibold";
                    }
                });
            }
        });

        function handleCheckboxToggle(clickedType) {
            const urgentBox = document.getElementById('is_urgent');
            const featuredBox = document.getElementById('is_featured');

            if (clickedType === 'urgent' && urgentBox.checked) {
                featuredBox.checked = false;
            } else if (clickedType === 'featured' && featuredBox.checked) {
                urgentBox.checked = false;
            }
        }
    </script>
</body>

</html>