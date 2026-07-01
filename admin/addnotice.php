<?php
// 1. Include the header
include '../includes/header.php';
?>

<div class="max-w-4xl  ml-6 my-6">
    <div class="mb-4">
        <h2 class="text-xl font-bold text-gray-800">Add New Notice</h2>
        <p class="text-xs text-gray-500">Create and publish an announcement to the notice board.</p>
    </div>

    <form action="process_notice.php" method="POST" enctype="multipart/form-data"
        class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-4">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notice Title</label>
            <input type="text" name="title" required
                class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select name="category_id" required
                class="w-full border border-gray-300 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                <option value="1">General</option>
                <option value="2">Exam</option>
                <option value="3">Timetables</option>
                <option value="4">Event</option>
            </select>
        </div>

        <div class="flex space-x-6 py-2">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" name="is_urgent" id="is_urgent" value="1"
                    onclick="handleCheckboxToggle('urgent')"
                    class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-slate-300">
                <span class="text-sm font-medium text-slate-700">Urgent</span>
            </label>

            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" name="is_featured" id="is_featured" value="1"
                    onclick="handleCheckboxToggle('featured')"
                    class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                <span class="text-sm font-medium text-slate-700">Featured</span>
            </label>
        </div>

        <div id="featured_options"
            class="hidden p-4 bg-slate-50 border border-slate-200 rounded-lg space-y-2 transition-all">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Featured Timing</h4>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Publish Date & Time</label>
                <input type="datetime-local" name="publish_date"
                    class="w-full border border-gray-300 rounded-lg p-2 bg-white text-sm">
            </div>
        </div>

        <div class="p-4 bg-gray-50/50 border border-gray-100 rounded-lg space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Expire Date (Optional)</label>
                    <input type="datetime-local" name="expire_date" id="expire_date"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <div
                        class="w-full border border-slate-200 rounded-lg p-2.5 bg-slate-50 text-slate-500 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-robot text-blue-500"></i>
                        <span>Managed automatically based on dates</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Role</label>
                    <select name="target_role" class="w-full border border-gray-300 rounded-lg p-2 bg-white text-sm">
                        <option value="all">All</option>
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Year</label>
                    <select name="target_year_id" class="w-full border border-gray-300 rounded-lg p-2 bg-white text-sm">
                        <option value="">All Academic Years</option>
                        <option value="1">First Year</option>
                        <option value="2">Second Year</option>
                        <option value="3">Third Year</option>
                        <option value="4">Fourth Year</option>
                        <option value="4">Fifth Year</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (Optional)</label>
            <input type="file" name="attachment" accept="image/*,.pdf,.doc,.docx"
                class="w-full border border-gray-300 rounded-lg p-2 bg-white file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
            <textarea name="content" required rows="4"
                class="w-full border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>

        <button type="submit"
            class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium text-sm shadow-sm cursor-pointer">
            Publish Notice
        </button>
    </form>
</div>

<script>
    document.getElementById('featured_checkbox').addEventListener('change', function () {
        const optionsBlock = document.getElementById('featured_options');
        if (this.checked) {
            optionsBlock.classList.remove('hidden');
        } else {
            optionsBlock.classList.add('hidden');
        }
    });
</script>

<script>
    function toggleCheckboxes(clickedType) {
        const urgentBox = document.getElementById('is_urgent');
        const featuredBox = document.getElementById('is_featured');

        if (clickedType === 'urgent' && urgentBox.checked) {
            featuredBox.checked = false;
        } else if (clickedType === 'featured' && featuredBox.checked) {
            urgentBox.checked = false;
        }
    }
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Enforce minimum expiration date to present time
        const expireInput = document.getElementById('expire_date');
        if (expireInput) {
            const now = new Date();

            // Format current local time to match YYYY-MM-DDTHH:MM format required by HTML5 min attribute
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            expireInput.min = minDateTime;
        }
    });

    // Mutually exclusive checkbox logic
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