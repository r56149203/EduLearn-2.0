<?php
// Check authentication on protected pages
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please login to continue.';
    header("Location: " . SITE_URL . "auth/login.php");
    exit();
}

// Check email verification
$stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user['email_verified']) {
    $_SESSION['error'] = 'Please verify your email address first.';
    header("Location: " . SITE_URL . "auth/verify-email.php");
    exit();
}
?>