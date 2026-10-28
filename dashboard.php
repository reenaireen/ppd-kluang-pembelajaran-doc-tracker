<?php
session_start();
include 'db.php';

if (file_exists('phpmailer/PHPMailer.php')) {
    require_once 'phpmailer/Exception.php';
    require_once 'phpmailer/PHPMailer.php';
    require_once 'phpmailer/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$staff_id = $_SESSION['user_id'];

// Handle Task Assignment (Staff claiming a task)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_task'])) {
    $doc_id = $_POST['doc_id'];
    $stmt = $conn->prepare("UPDATE documents SET handler_id = ? WHERE id = ? AND (handler_id IS NULL OR handler_id = 0)");
    $stmt->bind_param("ii", $staff_id, $doc_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: dashboard.php");
    exit();
}

// Handle Status Updates & Final Google Drive Link
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $doc_id = $_POST['doc_id'];
    $new_status = $_POST['status'];
    $final_link = trim($_POST['final_link'] ?? '');

    $check_stmt = $conn->prepare("
        SELECT documents.*, profiles.email AS requester_email, profiles.full_name AS requester_name 
        FROM documents 
        JOIN profiles ON documents.user_id = profiles.id 
        WHERE documents.id = ?
    ");
    $check_stmt->bind_param("i", $doc_id);
    $check_stmt->execute();
    $task_data = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($task_data && ($task_data['handler_id'] == NULL || $task_data['handler_id'] == $staff_id)) {
        if ($final_link === '' && isset($task_data['final_link'])) {
            $final_link = $task_data['final_link'];
        }

        if ($new_status === 'Done') {
            $stmt = $conn->prepare("UPDATE documents SET status = ?, final_link = ?, completed_at = NOW(), handler_id = IF(handler_id IS NULL OR handler_id = 0, ?, handler_id) WHERE id = ?");
            $stmt->bind_param("ssii", $new_status, $final_link, $staff_id, $doc_id);
        } else {
            $stmt = $conn->prepare("UPDATE documents SET status = ?, final_link = ?, completed_at = NULL, handler_id = IF(handler_id IS NULL OR handler_id = 0, ?, handler_id) WHERE id = ?");
            $stmt->bind_param("ssii", $new_status, $final_link, $staff_id, $doc_id);
        }
        $stmt->execute();
        $stmt->close();

        // Send Email Notification if status is Pending Signature or Done
        if (($new_status === 'Pending Signature' || $new_status === 'Done') && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'aireenamelyn17@gmail.com';
                $mail->Password   = 'ektk fwge tcia neai';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('aireenamelyn17@gmail.com', 'PPD Kluang Document Tracker');
                $mail->addAddress($task_data['requester_email'], $task_data['requester_name']);

                $mail->isHTML(true);
                $mail->Subject = "Task Status Update: " . $task_data['title'];
                $mail->Body    = "Hello " . htmlspecialchars($task_data['requester_name']) . ",<br><br>" .
                                 "Your task titled <b>" . htmlspecialchars($task_data['title']) . "</b> has been updated to: <b>" . htmlspecialchars($new_status) . "</b>.<br>" .
                                 (!empty($final_link) ? "Final Document Link: <a href='" . htmlspecialchars($final_link) . "'>Download / View Final Letter</a><br><br>" : "<br>") .
                                 "Regards,<br>PPD Kluang Team";

                $mail->send();
            } catch (Exception $e) {
                // Silently catch errors
            }
        }
    }

    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Document Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    function toggleEditLink(id) {
        var displayDiv = document.getElementById('link-display-' + id);
        var formDiv = document.getElementById('link-form-' + id);
        if (displayDiv && formDiv) {
            displayDiv.classList.add('hidden');
            formDiv.classList.remove('hidden');
        }
    }
    </script>
</head>
<body class="bg-[#FDFBF7] min-h-screen text-gray-800">

    <nav class="bg-white border-b border-[#E5E0D5] px-6 py-4 flex justify-between items-center shadow-sm">
        <div class="flex items-center gap-3">
            <img src="logoppd-removebg-preview.jpg" alt="PPD Kluang Logo" class="h-12 object-contain">
            <h1 class="text-xl font-bold text-[#6A1B29]">Staff Management Dashboard</h1>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
            <a href="login.html" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-100 transition">Logout</a>
        </div>
    </nav>

    <main class="w-full px-6 py-6 space-y-8">

        <?php 
        function renderTaskTable($conn, $staff_id, $category_name) {
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

            $table_id = 'table-' . strtolower(str_replace(' ', '-', $category_name));
            ?>
            <div class="bg-white border border-[#E5E0D5] rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-[#6A1B29]">Category: <?php echo htmlspecialchars($category_name); ?></h2>
                    <span class="text-xs bg-[#6A1B29] text-white px-3 py-1 rounded-full font-semibold">Active Tasks</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-[#E5E0D5] bg-[#FDFBF7] text-xs text-gray-500 uppercase">
                                <th class="p-3">Title</th>
                                <th class="p-3">Submitted By</th>
                                <th class="p-3">Assigned Handler</th>
                                <th class="p-3">Sample File</th>
                                <th class="p-3">Description / Notes</th>
                                <th class="p-3">Final Letter Link</th>
                                <th class="p-3">Timbalan Review & Notes</th>
                                <th class="p-3">Requester Status</th>
                            </tr>
                        </thead>
                        <tbody id="<?php echo $table_id; ?>" class="divide-y divide-[#E5E0D5] text-sm">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php 
                                        $is_my_task = ($row['handler_id'] == $staff_id);
                                        $is_claimed_by_other = (!empty($row['handler_id']) && $row['handler_id'] != $staff_id);
                                        
                                        $t_status = $row['timbalan_status'] ?? 'Pending Review';
                                        $t_badge = "bg-yellow-50 text-yellow-700 border-yellow-200";
                                        if ($t_status === 'Requires Correction') $t_badge = "bg-red-50 text-red-700 border-red-200";
                                        if ($t_status === 'Approved / Done') $t_badge = "bg-green-50 text-green-700 border-green-200";
                                    ?>
                                    <tr class="<?php echo $is_my_task ? 'bg-amber-50/20' : ''; ?>">
                                        <!-- 1. TITLE -->
                                        <td class="p-3 font-semibold text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($row['title']); ?></td>
                                        
                                        <!-- 2. SUBMITTED BY -->
                                        <td class="p-3 whitespace-nowrap">
                                            <strong class="text-gray-900"><?php echo htmlspecialchars($row['requester_name']); ?></strong><br>
                                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($row['requester_dept']); ?></span>
                                        </td>
                                        
                                        <!-- 3. ASSIGNED HANDLER -->
                                        <td class="p-3 whitespace-nowrap">
                                            <?php if (!empty($row['handler_name'])): ?>
                                                <span class="text-xs <?php echo $is_my_task ? 'bg-green-100 text-green-800 border-green-300' : 'bg-gray-100 text-gray-700 border-gray-300'; ?> px-2 py-1 rounded font-bold border">
                                                    👤 <?php echo htmlspecialchars($row['handler_name']); ?> <?php echo $is_my_task ? '(You)' : ''; ?>
                                                </span>
                                            <?php else: ?>
                                                <form action="dashboard.php" method="POST">
                                                    <input type="hidden" name="handle_task" value="1">
                                                    <input type="hidden" name="doc_id" value="<?php echo $row['id']; ?>">
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
                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                        <?php else: ?>
                                            <!-- FULL VIEW FOR MY TASKS & UNCLAIMED TASKS -->
                                            <!-- 4. SAMPLE FILE -->
                                            <td class="p-3 whitespace-nowrap">
                                                <?php if (!empty($row['sample_file'])): ?>
                                                    <a href="<?php echo htmlspecialchars($row['sample_file']); ?>" target="_blank" class="text-blue-600 font-semibold underline">View File</a>
                                                <?php else: ?>
                                                    <span class="text-gray-400">None</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- 5. DESCRIPTION / NOTES -->
                                            <td class="p-3 text-gray-600 italic text-xs max-w-xs whitespace-normal break-words" title="<?php echo htmlspecialchars($row['description'] ?? ''); ?>">
                                                <?php echo !empty($row['description']) ? htmlspecialchars($row['description']) : '<span class="text-gray-400 not-italic">No notes</span>'; ?>
                                            </td>
                                            
                                            <!-- 6. FINAL LETTER LINK -->
                                            <td class="p-3 whitespace-nowrap">
                                                <?php $has_link = !empty($row['final_link']); ?>
                                                <div id="link-display-<?php echo $row['id']; ?>" class="<?php echo $has_link ? '' : 'hidden'; ?>">
                                                    <?php if ($has_link): ?>
                                                        <div class="flex items-center gap-2">
                                                            <a href="<?php echo htmlspecialchars($row['final_link']); ?>" target="_blank" class="text-xs bg-green-50 text-green-700 border border-green-200 px-2.5 py-1 rounded font-bold underline">🔗 Open Link</a>
                                                            <button type="button" onclick="toggleEditLink(<?php echo $row['id']; ?>)" class="text-xs text-gray-500 hover:text-gray-700 underline">Edit</button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div id="link-form-<?php echo $row['id']; ?>" class="<?php echo $has_link ? 'hidden' : ''; ?>">
                                                    <form action="dashboard.php" method="POST" class="flex items-center gap-2">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <input type="hidden" name="doc_id" value="<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($row['status']); ?>">
                                                        
                                                        <input type="url" name="final_link" placeholder="Paste Drive link..." value="<?php echo htmlspecialchars($row['final_link'] ?? ''); ?>" class="px-2 py-1 border border-[#E5E0D5] rounded bg-white text-xs w-36" required>
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
                                                    <input type="hidden" name="doc_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="final_link" value="<?php echo htmlspecialchars($row['final_link'] ?? ''); ?>">
                                                    <select name="status" onchange="this.form.submit()" class="px-2 py-1 border border-[#E5E0D5] rounded bg-white text-xs font-semibold focus:ring-2 focus:ring-[#6A1B29]">
                                                        <option value="In Progress" <?php if($row['status'] == 'In Progress') echo 'selected'; ?>>🟡 In Progress</option>
                                                        <option value="Pending Signature" <?php if($row['status'] == 'Pending Signature') echo 'selected'; ?>>🟠 Pending Signature</option>
                                                        <option value="Done" <?php if($row['status'] == 'Done') echo 'selected'; ?>>🟢 Done</option>
                                                    </select>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="p-4 text-center text-gray-500">No tasks currently queued in this category.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
            $stmt->close();
        }

        renderTaskTable($conn, $staff_id, 'Sijil');
        renderTaskTable($conn, $staff_id, 'Surat Rasmi');
        ?>

    </main>

    <script>
    function refreshCategory(categoryName, elementId) {
        if (document.activeElement && (document.activeElement.tagName === 'SELECT' || document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'BUTTON' || document.activeElement.tagName === 'TEXTAREA')) {
            return;
        }

        fetch('fetch_staff_tasks.php?category=' + encodeURIComponent(categoryName))
            .then(response => response.text())
            .then(data => {
                if (data.trim() !== '') {
                    document.getElementById(elementId).innerHTML = data;
                }
            })
            .catch(error => console.error('Refresh Error:', error));
    }

    setInterval(() => {
        refreshCategory('Sijil', 'table-sijil');
        refreshCategory('Surat Rasmi', 'table-surat-rasmi');
    }, 3000);
    </script>
</body>
</html>