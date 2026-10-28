<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$staff_id = $_SESSION['user_id'];
$category_name = isset($_GET['category']) ? $_GET['category'] : '';

if (!empty($category_name)) {
    $query = "
        SELECT documents.*, 
               requester.full_name AS requester_name, 
               requester.department AS requester_dept,
               handler.full_name AS handler_name
        FROM documents 
        INNER JOIN profiles AS requester ON documents.user_id = requester.id 
        LEFT JOIN profiles AS handler ON documents.handler_id = handler.id
        WHERE documents.doc_type = ?
        ORDER BY documents.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "
        SELECT documents.*, 
               requester.full_name AS requester_name, 
               requester.department AS requester_dept,
               handler.full_name AS handler_name
        FROM documents 
        INNER JOIN profiles AS requester ON documents.user_id = requester.id 
        LEFT JOIN profiles AS handler ON documents.handler_id = handler.id
        ORDER BY documents.created_at DESC
    ";
    $result = $conn->query($query);
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $is_my_task = ($row['handler_id'] == $staff_id);
        $is_claimed_by_other = (!empty($row['handler_id']) && $row['handler_id'] != $staff_id);
        $handler_display = !empty($row['handler_name']) ? $row['handler_name'] : '';
        $title = !empty($row['title']) ? $row['title'] : '';
        $description = !empty($row['description']) ? $row['description'] : '';
        $final_link = !empty($row['final_link']) ? $row['final_link'] : '';
        $requester_name = !empty($row['requester_name']) ? $row['requester_name'] : '';
        $requester_dept = !empty($row['requester_dept']) ? $row['requester_dept'] : '';
        $sample_file = !empty($row['sample_file']) ? $row['sample_file'] : '';
        $status = !empty($row['status']) ? $row['status'] : 'In Progress';
        $has_link = !empty($final_link);
        $row_id = $row['id'];

        $t_status = $row['timbalan_status'] ?? 'Pending Review';
        $t_badge = "bg-yellow-50 text-yellow-700 border-yellow-200";
        if ($t_status === 'Requires Correction') $t_badge = "bg-red-50 text-red-700 border-red-200";
        if ($t_status === 'Approved / Done') $t_badge = "bg-green-50 text-green-700 border-green-200";
        ?>
        <tr class="<?php echo $is_my_task ? 'bg-amber-50/20' : ''; ?>">
            <!-- 1. TITLE -->
            <td class="p-3 font-semibold text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($title); ?></td>
            
            <!-- 2. SUBMITTED BY -->
            <td class="p-3 whitespace-nowrap">
                <strong class="text-gray-900"><?php echo htmlspecialchars($requester_name); ?></strong><br>
                <span class="text-xs text-gray-500"><?php echo htmlspecialchars($requester_dept); ?></span>
            </td>
            
            <!-- 3. ASSIGNED HANDLER -->
            <td class="p-3 whitespace-nowrap">
                <?php if (!empty($handler_display)): ?>
                    <span class="text-xs <?php echo $is_my_task ? 'bg-green-100 text-green-800 border-green-300' : 'bg-gray-100 text-gray-700 border-gray-300'; ?> px-2 py-1 rounded font-bold border">
                        👤 <?php echo htmlspecialchars($handler_display); ?> <?php echo $is_my_task ? '(You)' : ''; ?>
                    </span>
                <?php else: ?>
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="handle_task" value="1">
                        <input type="hidden" name="doc_id" value="<?php echo $row_id; ?>">
                        <button type="submit" class="text-xs bg-[#6A1B29] text-white px-3 py-1 rounded font-semibold hover:bg-[#52131F] transition">
                            Claim / Handle
                        </button>
                    </form>
                <?php endif; ?>
            </td>
            
            <?php if ($is_claimed_by_other): ?>
                <!-- RESTRICTED VIEW FOR OTHER STAFF MEMBERS' TASKS -->
                <td class="p-3 text-gray-400 italic text-xs">Restricted</td>
                <td class="p-3 text-gray-400 italic text-xs">Restricted</td>
                <td class="p-3 text-gray-400 italic text-xs">Restricted</td>
                <td class="p-3 text-gray-400 italic text-xs">Restricted</td>
                <td class="p-3 whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border border-gray-300">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </td>
            <?php else: ?>
                <!-- FULL VIEW FOR MY TASKS & UNCLAIMED TASKS -->
                <!-- 4. SAMPLE FILE -->
                <td class="p-3 whitespace-nowrap">
                    <?php if (!empty($sample_file)): ?>
                        <a href="<?php echo htmlspecialchars($sample_file); ?>" target="_blank" class="text-blue-600 font-semibold underline">View File</a>
                    <?php else: ?>
                        <span class="text-gray-400">None</span>
                    <?php endif; ?>
                </td>
                
                <!-- 5. DESCRIPTION / NOTES -->
                <td class="p-3 text-gray-600 italic text-xs max-w-xs whitespace-normal break-words" title="<?php echo htmlspecialchars($description); ?>">
                    <?php echo !empty($description) ? htmlspecialchars($description) : '<span class="text-gray-400 not-italic">No notes</span>'; ?>
                </td>
                
                <!-- 6. FINAL LETTER LINK -->
                <td class="p-3 whitespace-nowrap">
                    <div id="link-display-<?php echo $row_id; ?>" class="<?php echo $has_link ? '' : 'hidden'; ?>">
                        <?php if ($has_link): ?>
                            <div class="flex items-center gap-2">
                                <a href="<?php echo htmlspecialchars($final_link); ?>" target="_blank" class="text-xs bg-green-50 text-green-700 border border-green-200 px-2.5 py-1 rounded font-bold underline">🔗 Open Link</a>
                                <button type="button" onclick="toggleEditLink(<?php echo $row_id; ?>)" class="text-xs text-gray-500 hover:text-gray-700 underline">Edit</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="link-form-<?php echo $row_id; ?>" class="<?php echo $has_link ? 'hidden' : ''; ?>">
                        <form action="dashboard.php" method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="update_status" value="1">
                            <input type="hidden" name="doc_id" value="<?php echo $row_id; ?>">
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                            
                            <input type="url" name="final_link" placeholder="Paste Drive link..." value="<?php echo htmlspecialchars($final_link); ?>" class="px-2 py-1 border border-[#E5E0D5] rounded bg-white text-xs w-36" required>
                            <button type="submit" class="text-xs bg-[#6A1B29] text-white px-2 py-1 rounded font-semibold hover:bg-[#52131F] transition">
                                Save
                            </button>
                        </form>
                    </div>
                </td>

                <!-- 7. TIMBALAN REVIEW & NOTES (Comments hidden when Approved / Done) -->
                <td class="p-3 max-w-xs">
                    <span class="px-2 py-0.5 rounded text-xs font-bold border block w-max mb-1 <?php echo $t_badge; ?>">
                        <?php echo htmlspecialchars($t_status); ?>
                    </span>
                    <?php if ($t_status !== 'Approved / Done' && !empty($row['timbalan_comment'])): ?>
                        <p class="text-xs text-gray-700 bg-gray-50 border border-gray-200 p-1.5 rounded italic whitespace-normal break-words">
                            💬 <?php echo htmlspecialchars($row['timbalan_comment']); ?>
                        </p>
                    <?php elseif ($t_status !== 'Approved / Done'): ?>
                        <span class="text-xs text-gray-400 italic">No comments</span>
                    <?php endif; ?>
                </td>

                <!-- 8. REQUESTER STATUS -->
                <td class="p-3 whitespace-nowrap">
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="doc_id" value="<?php echo $row_id; ?>">
                        <input type="hidden" name="final_link" value="<?php echo htmlspecialchars($final_link); ?>">
                        <select name="status" onchange="this.form.submit()" class="px-2 py-1 border border-[#E5E0D5] rounded bg-white text-xs font-semibold focus:ring-2 focus:ring-[#6A1B29]">
                            <option value="In Progress" <?php if($status == 'In Progress') echo 'selected'; ?>>🟡 In Progress</option>
                            <option value="Pending Signature" <?php if($status == 'Pending Signature') echo 'selected'; ?>>🟠 Pending Signature</option>
                            <option value="Done" <?php if($status == 'Done') echo 'selected'; ?>>🟢 Done</option>
                        </select>
                    </form>
                </td>
            <?php endif; ?>
        </tr>
        <?php
    }
} else {
    echo '<tr><td colspan="8" class="p-4 text-center text-gray-500">No tasks currently queued in this category.</td></tr>';
}
?>