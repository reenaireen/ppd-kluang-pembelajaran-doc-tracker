<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.html");
    exit;
}

$doc_id = $_GET['id'];
$stmt = $conn->prepare("SELECT title, sample_file FROM documents WHERE id = ?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result && !empty($result['sample_file']) && file_exists($result['sample_file'])) {
    $filepath = $result['sample_file'];
    $ext = pathinfo($filepath, PATHINFO_EXTENSION);
    
    // Clean the database title to make it a safe filename
    $safe_title = preg_replace('/[^A-Za-z0-9-_]/', '_', $result['title']);
    $download_filename = $safe_title . "." . $ext;

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $download_filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    flush();
    readfile($filepath);
    exit;
} else {
    echo "File not found.";
}
?>