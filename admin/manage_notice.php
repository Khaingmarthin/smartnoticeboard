<?php
include ('../includes/header.php');

include('../config/db.php');

// JOIN both categories and users tables to fetch actual names instead of IDs
$query = "SELECT notices.*, 
                 categories.name, 
                 users.name AS author_name 
          FROM notices 
          INNER JOIN categories ON notices.category_id = categories.id 
          INNER JOIN users ON notices.user_id = users.id 
          ORDER BY notices.publish_date DESC";

$result = mysqli_query($conn, $query);

$notices = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notices[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'category' => $row['name'] ?? 'General',
            // Map the joined author name here
            'author' => $row['author_name'] ?? 'Unknown Admin',
            // Map your actual publish_date column
            'created_at'=> $row['created_at'],
            'date' => isset($row['publish_date']) ? date('d M Y', strtotime($row['publish_date'])) : 'N/A'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notices - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-slate-50 text-slate-800 p-5 font-sans">

    <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow-sm border border-slate-100">

        <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-200">
            <h2 class="text-2xl font-bold text-slate-900">Manage Notices</h2>
            <a href="addnotice.php"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200">
                <i class="fa-solid fa-plus"></i> Add Notice
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b-2 border-slate-200">
                        <th class="p-4">Title</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Author</th>
                        <th class="p-4">Date Posted</th>
                        <th class="p-4 w-28 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (!empty($notices)): ?>
                        <?php foreach ($notices as $notice): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="p-4 font-semibold text-slate-900"><?php echo htmlspecialchars($notice['title']); ?>
                                </td>
                                <td class="p-4"><span
                                        class="inline-block bg-slate-100 text-slate-700 text-xs font-medium px-2.5 py-1 rounded-md"><?php echo htmlspecialchars($notice['category']); ?></span>
                                </td>
                                <td class="p-4 text-slate-600"><?php echo htmlspecialchars($notice['author']); ?></td>
                                <td class="p-4 text-slate-600"><?php echo date('d M Y, h:i A', strtotime($notice['created_at'])); ?></td>
                                <td class="p-4">
                                    <div class="flex justify-center gap-2">
                                        <a href="editnotice.php?id=<?php echo $notice['id']; ?>&from=manage"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-200"
                                            title="Edit Notice">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="deletenotice.php?id=<?php echo $notice['id']; ?>"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all duration-200"
                                            title="Delete Notice"
                                            onclick="return confirm('Are you sure you want to delete this notice?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-slate-400 py-8">No notices found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>