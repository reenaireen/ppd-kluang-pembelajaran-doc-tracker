<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "
    SELECT documents.*, staff.full_name AS handler_name 
    FROM documents 
    LEFT JOIN profiles AS staff ON documents.handler_id = staff.id 
    WHERE documents.user_id = ? 
    ORDER BY documents.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_tasks = $stmt->get_result();

if ($user_tasks && $user_tasks->num_rows > 0) {
    while ($row = $user_tasks->fetch_assoc()) {
        $status = $row['status'];
        $badge = "bg-yellow-100 text-yellow-800";
        if ($status === 'Pending Signature') $badge = "bg-orange-100 text-orange-800";
        if ($status === 'Done') $badge = "bg-green-100 text-green-800";

        ?>
        <tr>
            <td class="p-3 font-semibold text-gray-600 whitespace-nowrap">
                <span class="px-2 py-0.5 rounded text-xs bg-gray-100 border border-gray-300">
                    <?php echo htmlspecialchars($row['doc_type']); ?>
                </span>
            </td>
            <td class="p-3 font-semibold text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($row['title']); ?></td>
            <td class="p-3 text-gray-700 font-medium whitespace-nowrap">
                <?php if (!empty($row['handler_name'])): ?>
                    <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-md font-bold border border-blue-200">
                        👤 <?php echo htmlspecialchars($row['handler_name']); ?>
                    </span>
                <?php else: ?>
                    <span class="text-xs text-gray-400 font-normal">Unassigned</span>
                <?php endif; ?>
            </td>
            <td class="p-3 whitespace-nowrap">
                <?php if (!empty($row['sample_file'])): ?>
                    <a href="<?php echo htmlspecialchars($row['sample_file']); ?>" target="_blank" class="text-blue-600 underline">View File</a>
                <?php else: ?>
                    <span class="text-gray-400">None</span>
                <?php endif; ?>
            </td>
            <td class="p-3 text-gray-600 italic text-xs max-w-xs whitespace-normal break-words" title="<?php echo htmlspecialchars($row['description'] ?? ''); ?>">
                <?php echo !empty($row['description']) ? htmlspecialchars($row['description']) : '<span class="text-gray-400 not-italic">No notes</span>'; ?>
            </td>
            <td class="p-3 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold <?php echo $badge; ?>">
                    <?php echo htmlspecialchars($status); ?>
                </span>
            </td>
            <td class="p-3 whitespace-nowrap">
                <?php if (!empty($row['final_link'])): ?>
                    <a href="<?php echo htmlspecialchars($row['final_link']); ?>" target="_blank" class="inline-block bg-green-50 text-green-700 border border-green-200 px-3 py-1 rounded text-xs font-bold hover:bg-green-100 transition">
                        📥 Download Final Letter
                    </a>
                <?php else: ?>
                    <span class="text-xs text-gray-400 italic">Not ready yet</span>
                <?php endif; ?>
            </td>
            <td class="p-3 whitespace-nowrap">
                <form action="index.php" method="POST" onsubmit="return confirm('Delete this task and clear storage?');">
                    <input type="hidden" name="delete_task" value="1">
                    <input type="hidden" name="doc_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="text-xs text-red-600 border border-red-200 bg-red-50 hover:bg-red-100 px-2.5 py-1 rounded font-semibold transition">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        <?php
    }
} else {
    echo '<tr><td colspan="8" class="p-4 text-center text-gray-500">You haven\'t uploaded any tasks yet.</td></tr>';
}
?>