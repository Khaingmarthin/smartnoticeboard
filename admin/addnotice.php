<form action="process_notice.php" method="POST" enctype="multipart/form-data"
    class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Notice Title</label>
        <input type="text" name="title" required class="w-full border-gray-300 rounded-lg p-2 border">
    </div>

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Attachment (Optional)</label>
        <input type="file" name="attachment" accept="image/*,.pdf,.doc,.docx"
            class="w-full border-gray-300 rounded-lg p-2 border">
    </div>

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Content</label>
        <textarea name="content" required class="w-full border-gray-300 rounded-lg p-2 border"></textarea>
    </div>

    <div class="flex space-x-6 mb-6">
        <label class="flex items-center space-x-2">
            <input type="checkbox" name="is_urgent" value="1" class="w-4 h-4 text-red-600">
            <span class="text-sm font-medium text-gray-700">Urgent</span>
        </label>
        <label class="flex items-center space-x-2">
            <input type="checkbox" name="is_featured" value="1" class="w-4 h-4 text-indigo-600">
            <span class="text-sm font-medium text-gray-700">Featured</span>
        </label>
    </div>

    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Publish
        Notice</button>
</form>