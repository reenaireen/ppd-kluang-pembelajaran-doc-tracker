<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role      = $_POST['role'] ?? 'requester';
    $passcode  = trim($_POST['passcode'] ?? '');

    $SECRET_PASSCODE = "PPDPEMBELAJARAN"; // Staff access passcode

    // Validate Staff Passcode if Staff role was selected
    if ($role === 'staff') {
        if ($passcode !== $SECRET_PASSCODE) {
            die("Error: Invalid Staff Access Passcode. <a href='signup.html'>Try again</a>");
        }
    }

    // Insert user into database with selected role (requester, staff, or timbalan)
    $stmt = $conn->prepare("INSERT INTO profiles (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $full_name, $email, $password, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Account created successfully! Please login.'); window.location.href='login.html';</script>";
    } else {
        echo "Registration failed: Email may already exist. <a href='signup.html'>Try again</a>";
    }

    $stmt->close();
}
?>