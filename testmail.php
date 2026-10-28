<?php
$to = "aireenamelyn17@gmail.com"; // Change to your personal email
$subject = "Test Email from Document Tracker";
$message = "If you receive this, server mail is working.";
$headers = "From: no-reply@ppd-kluang.infinityfreeapp.com";

if (mail($to, $subject, $message, $headers)) {
    echo "Test email sent successfully!";
} else {
    echo "Failed to send test email.";
}
?>