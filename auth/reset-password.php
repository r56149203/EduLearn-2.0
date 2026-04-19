<?php
require_once '../config/database.php';
require_once '../config/functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: forgot-password.php");
    exit();
}

// Validate token
$stmt = $pdo->prepare("SELECT id, name, reset_token_expires_at FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = 'Invalid reset token.';
} elseif (strtotime($user['reset_token_expires_at']) < time()) {
    $error = 'Reset link has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        
        if ($stmt->execute([$hashed, $user['id']])) {
            $success = 'Password reset successfully! You can now login.';
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-lock me-2"></i>Reset Password</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <a href="login.php" class="btn btn-primary w-100">Login Now</a>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <a href="forgot-password.php" class="btn btn-primary w-100">Request New Reset Link</a>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>