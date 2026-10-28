<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

// Ensure only timbalan / management role can access
if (isset($_SESSION['role']) && $_SESSION['role'] === 'requester') {
    header("Location: index.php");
    exit;
}

// Handle Timbalan Review Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $doc_id = $_POST['doc_id'];
    $timbalan_status = $_POST['timbalan_status'];
    $timbalan_comment = trim($_POST['timbalan_comment'] ?? '');

    $stmt = $conn->prepare("UPDATE documents SET timbalan_status = ?, timbalan_comment = ? WHERE id = ?");
    $stmt->bind_param("ssi", $timbalan_status, $timbalan_comment, $doc_id);
    $stmt->execute();
    $stmt->close();

    header("Location: timbalan_dashboard.php");
    exit();
}

// Fetch documents: Prioritize items that are NOT 'Approved / Done' first, 
// then sort by created_at ASC (oldest first) so first-come, first-served appears on top.
$query = "
    SELECT id, title, doc_type, final_link, timbalan_status, timbalan_comment, created_at
    FROM documents 
    ORDER BY CASE 
        WHEN timbalan_status = 'Approved / Done' THEN 2 
        ELSE 1 
    END ASC, 
    created_at ASC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timbalan Dashboard - Document Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#FDFBF7] min-h-screen text-gray-800">

    <nav class="bg-white border-b border-[#E5E0D5] px-6 py-4 flex justify-between items-center shadow-sm">
        <div class="flex items-center gap-3">
            <img src="logoppd-removebg-preview.jpg" alt="PPD Kluang Logo" class="h-12 object-contain">
            <h1 class="text-xl font-bold text-[#6A1B29]">Timbalan Dashboard</h1>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
            <a href="login.html" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-100 transition">Logout</a>
        </div>
    </nav>

    <main class="w-full px-6 py-6 space-y-6">

        <section class="bg-white border border-[#E5E0D5] rounded-xl p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-[#6A1B29]">Document Verification</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#E5E0D5] bg-[#FDFBF7] text-xs text-gray-500 uppercase">
                            <th class="p-3">Date Uploaded</th>
                            <th class="p-3">Title</th>
                            <th class="p-3">Final Letter (Drive)</th>
                            <th class="p-3">Review Action & Comments</th>
                            <th class="p-3">Current Review Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E5E0D5] text-sm">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php 
                                    $t_status = $row['timbalan_status'] ?? 'Pending Review';
                                    $is_done = ($t_status === 'Approved / Done');
                                    
                                    $badge = "bg-yellow-100 text-yellow-800 border-yellow-300";
                                    if ($t_status === 'Requires Correction') $badge = "bg-red-100 text-red-800 border-red-300";
                                    if ($is_done) $badge = "bg-green-100 text-green-800 border-green-300";
                                ?>
                                <tr class="<?php echo $is_done ? 'bg-gray-50' : ''; ?>">
                                    <!-- 1. DATE -->
                                    <td class="p-3 whitespace-nowrap text-xs text-gray-500">
                                        <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?>
                                    </td>

                                    <!-- 2. TITLE -->
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-xs bg-gray-100 border border-gray-300 font-semibold inline-block mb-1">
                                            <?php echo htmlspecialchars($row['doc_type']); ?>
                                        </span><br>
                                        <strong class="text-gray-900 text-base"><?php echo htmlspecialchars($row['title']); ?></strong>
                                    </td>

                                    <!-- 3. FINAL LETTER (DRIVE) -->
                                    <td class="p-3 whitespace-nowrap">
                                        <?php if (!empty($row['final_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['final_link']); ?>" target="_blank" class="inline-block bg-green-50 text-green-700 border border-green-200 px-3 py-1.5 rounded text-xs font-bold hover:bg-green-100 transition">
                                                🔗 Open Drive Letter
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">Not uploaded by staff yet</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- 4. REVIEW ACTION & COMMENTS -->
                                    <td class="p-3">
                                        <form action="timbalan_dashboard.php" method="POST" class="space-y-2 max-w-sm">
                                            <input type="hidden" name="submit_review" value="1">
                                            <input type="hidden" name="doc_id" value="<?php echo $row['id']; ?>">

                                            <select name="timbalan_status" class="w-full px-2 py-1 border border-[#E5E0D5] rounded bg-white text-xs font-semibold focus:ring-2 focus:ring-[#6A1B29]">
                                                <option value="Pending Review" <?php if($t_status == 'Pending Review') echo 'selected'; ?>>🟡 Pending Review</option>
                                                <option value="Requires Correction" <?php if($t_status == 'Requires Correction') echo 'selected'; ?>>🔴 Requires Correction</option>
                                                <option value="Approved / Done" <?php if($t_status == 'Approved / Done') echo 'selected'; ?>>🟢 Approved / Done</option>
                                            </select>

                                            <textarea name="timbalan_comment" rows="2" placeholder="Add feedback or corrections..." class="w-full px-2 py-1 border border-[#E5E0D5] rounded bg-white text-xs"><?php echo htmlspecialchars($row['timbalan_comment'] ?? ''); ?></textarea>

                                            <button type="submit" class="text-xs bg-[#6A1B29] text-white px-3 py-1 rounded font-semibold hover:bg-[#52131F] transition">
                                                Save Verification
                                            </button>
                                        </form>
                                    </td>

                                    <!-- 5. CURRENT REVIEW STATUS -->
                                    <td class="p-3 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold border <?php echo $badge; ?>">
                                            <?php echo htmlspecialchars($t_status); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="p-4 text-center text-gray-500">No documents found for review.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

</body>
</html>