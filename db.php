<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'sql308.infinityfree.com';
$db   = 'if0_43150604_document_tracker';
$user = 'if0_43150604';
$pass = ''; // Leave this blank for GitHub security!

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>