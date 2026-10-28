<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, department, password, role FROM profiles WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables correctly
        // Inside your login validation PHP logic after checking password:
$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'timbalan') {
    header("Location: timbalan_dashboard.php");
} elseif ($user['role'] === 'staff') {
    header("Location: dashboard.php");
} else {
    header("Location: index.php"); // Default Requester Portal
}
exit();
        } else {
            echo "<script>alert('Invalid password.'); window.location.href='login.html';</script>";
        }
    } else {
        echo "<script>alert('No account found with this email.'); window.location.href='login.html';</script>";
    }

    $stmt->close();
}
?>