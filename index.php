<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

// If a staff member tries to access requester portal, redirect them to staff dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Auto-purge 'Done' tasks older than 7 days
$purge_query = "SELECT id, sample_file FROM documents WHERE status = 'Done' AND completed_at IS NOT NULL AND completed_at < NOW() - INTERVAL 7 DAY";
$purge_res = $conn->query($purge_query);
if ($purge_res && $purge_res->num_rows > 0) {
    while ($row = $purge_res->fetch_assoc()) {
        if (!empty($row['sample_file']) && file_exists($row['sample_file'])) {
            unlink($row['sample_file']);
        }
        $del_stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
        $del_stmt->bind_param("i", $row['id']);
        $del_stmt->execute();
        $del_stmt->close();
    }
}

// Manual Delete Task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_task'])) {
    $doc_id = $_POST['doc_id'];
    $stmt = $conn->prepare("SELECT sample_file FROM documents WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $doc_id, $user_id);
    $stmt->execute();
    $file_res = $stmt->get_result()->fetch_assoc();
    
    if ($file_res) {
        if (!empty($file_res['sample_file']) && file_exists($file_res['sample_file'])) {
            unlink($file_res['sample_file']);
        }
        $del_stmt = $conn->prepare("DELETE FROM documents WHERE id = ? AND user_id = ?");
        $del_stmt->bind_param("ii", $doc_id, $user_id);
        $del_stmt->execute();
        $del_stmt->close();
        $success_msg = "Task successfully cleaned up and deleted.";
    }
}

// Check Task Limit
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM documents WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$current_task_count = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Handle Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_task'])) {
    if ($current_task_count >= 5) {
        $error_msg = "Limit reached! You have 5 active tasks. Please delete completed tasks to upload new ones.";
    } else {
        $title = trim($_POST['title']);
        $doc_type = $_POST['doc_type'];
        $description = trim($_POST['description'] ?? '');

        $file_path = NULL;
        $upload_ok = true;

        if (isset($_FILES['sample_file']) && $_FILES['sample_file']['error'] == 0) {
            if ($_FILES['sample_file']['size'] > 3 * 1024 * 1024) {
                $error_msg = "File is too large! Maximum allowed file size is 3MB.";
                $upload_ok = false;
            } else {
                $target_dir = "uploads/";
                $file_extension = pathinfo($_FILES["sample_file"]["name"], PATHINFO_EXTENSION);
                $new_filename = "doc_" . $user_id . "_" . time() . "." . $file_extension;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES["sample_file"]["tmp_name"], $target_file)) {
                    $file_path = $target_file;
                } else {
                    $error_msg = "Error moving uploaded file to server.";
                    $upload_ok = false;
                }
            }
        }

        if ($upload_ok) {
            $stmt = $conn->prepare("INSERT INTO documents (user_id, title, doc_type, sample_file, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $title, $doc_type, $file_path, $description);

            if ($stmt->execute()) {
                $success_msg = "Task submitted successfully!";
                $current_task_count++;
            } else {
                $error_msg = "Failed to submit task. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Fetch tasks
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requester Portal - Document Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    function updateDescriptionPrompt() {
        var docType = document.getElementById('doc_type_select').value;
        var promptNote = document.getElementById('description_prompt');
        if (docType === 'Surat Rasmi') {
            promptNote.classList.remove('hidden');
        } else {
            promptNote.classList.add('hidden');
        }
    }
    </script>
</head>
<body class="bg-[#FDFBF7] text-gray-800 min-h-screen">

    <header class="bg-white border-b border-[#E5E0D5] px-6 py-4 flex justify-between items-center shadow-sm">
        <div class="flex items-center gap-3">
            <img src="logoppd-removebg-preview.jpg" alt="PPD Kluang Logo" class="h-12 object-contain">
            <h1 class="text-xl font-bold text-[#6A1B29]">REQUESTER DASHBOARD</h1>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
            <a href="login.html" class="bg-red-50 text-red-600 px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-red-100 transition">Logout</a>
        </div>
    </header>

    <main class="w-full px-6 py-6 space-y-8">
        <?php if ($success_msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-800 p-4 rounded-lg"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-800 p-4 rounded-lg"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Task Capacity Indicator -->
        <div class="bg-white border border-[#E5E0D5] p-4 rounded-xl flex justify-between items-center shadow-sm">
            <div>
                <h3 class="font-bold text-gray-700">Account Task Limit</h3>
                <p class="text-xs text-gray-500">Tasks are automatically purged 7 days after completion. Max file size: 3MB.</p>
            </div>
            <span class="text-sm font-bold px-3 py-1 rounded-full <?php echo ($current_task_count >= 5) ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                <?php echo $current_task_count; ?> / 5 Active Tasks
            </span>
        </div>

        <!-- Upload Form -->
        <section class="bg-white border border-[#E5E0D5] p-6 rounded-xl shadow-sm">
            <h2 class="text-lg font-bold text-[#6A1B29] mb-4">📤 Upload New Task</h2>
            <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="submit_task" value="1">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Document Type</label>
                        <select name="doc_type" id="doc_type_select" onchange="updateDescriptionPrompt()" required class="w-full px-4 py-2 border border-[#E5E0D5] rounded-lg bg-white">
                            <option value="Surat Rasmi">📜 Surat Rasmi</option>
                            <option value="Sijil">🎓 Sijil</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Document / Task Title</label>
                        <input type="text" name="title" required placeholder="e.g. Request Approval Letter" class="w-full px-4 py-2 border border-[#E5E0D5] rounded-lg">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Upload Sample Letter / Document (Max 3MB)</label>
                    <input type="file" name="sample_file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-[#6A1B29] file:text-white file:cursor-pointer">
                </div>
                
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-sm font-medium text-gray-700">  📌 Task Description : Please include: Letter Title, Place, and Date inside the description below. / Notes</label>
                        <span id="description_prompt" class="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200"></span>
                    </div>
                    <textarea name="description" rows="4" maxlength="1000" class="mt-1 block w-full rounded-lg border border-[#E5E0D5] px-3 py-2 shadow-sm" placeholder="Add instructions or detailed notes (e.g., Title, Place, and Date for Surat Rasmi)..."></textarea>
                </div>

                <button type="submit" <?php if($current_task_count >= 5) echo 'disabled class="opacity-50 cursor-not-allowed bg-gray-400 text-white px-6 py-2 rounded-lg font-semibold"'; else echo 'class="bg-[#6A1B29] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#52131F] transition"'; ?>>
                    Submit Task
                </button>
            </form>
        </section>

        <!-- Tasks Table -->
        <section class="bg-white border border-[#E5E0D5] p-6 rounded-xl shadow-sm">
            <h2 class="text-lg font-bold text-[#6A1B29] mb-4">📋 My Uploaded Tasks</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#E5E0D5] bg-[#FDFBF7] text-xs text-gray-500 uppercase">
                            <th class="p-3">Type</th>
                            <th class="p-3">Title</th>
                            <th class="p-3">Handled By</th>
                            <th class="p-3">Sample File</th>
                            <th class="p-3">Description / Notes</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Final Letter (Drive)</th>
                            <th class="p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="taskTableBody" class="divide-y divide-[#E5E0D5] text-sm">
                        <?php if ($user_tasks->num_rows > 0): ?>
                            <?php while ($row = $user_tasks->fetch_assoc()): ?>
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
                                        <?php 
                                            $status = $row['status'];
                                            $badge = "bg-yellow-100 text-yellow-800";
                                            if ($status === 'Pending Signature') $badge = "bg-orange-100 text-orange-800";
                                            if ($status === 'Done') $badge = "bg-green-100 text-green-800";
                                        ?>
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
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="p-4 text-center text-gray-500">You haven't uploaded any tasks yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
    
<script>
function refreshTasks() {
    fetch('fetch_tasks.php')
        .then(response => {
            if (response.ok) return response.text();
            throw new Error('Network response was not ok');
        })
        .then(data => {
            if (data.trim() !== '') {
                document.getElementById('taskTableBody').innerHTML = data;
            }
        })
        .catch(error => console.error('AJAX Refresh Error:', error));
}

setInterval(refreshTasks, 3000);
</script>

</body>
</html>