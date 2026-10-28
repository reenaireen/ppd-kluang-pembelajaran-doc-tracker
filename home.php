<?php
session_start();

// If the user IS logged in, route them instantly to their respective dashboard
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
        header("Location: dashboard.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}

// If NOT logged in, show the public HTML landing page
include('home.html');
exit;
?>