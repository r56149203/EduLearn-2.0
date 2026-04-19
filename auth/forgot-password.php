<?php
require_once '../config/database.php';
require_once '../config/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && $user['email_verified']) {
            $reset_token = generateToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+' . RESET_TOKEN_EXPIRY_MINUTES . ' minutes'));
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            $stmt->execute([$reset_token, $expiry, $user['id']]);
            
            $reset_link = SITE_URL . "auth/reset-password.php?token=" . $reset_token;
            $subject = "Password Reset Request - " . SITE_NAME;
            $body = "
                <html>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>Hello {$user['name']},</p>
                    <p>Click the link below to reset your password:</p>
                    <p><a href='{$reset_link}'>Reset Password</a></p>
                    <p>This link expires in " . RESET_TOKEN_EXPIRY_MINUTES . " minutes.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                </body>
                </html>
            ";
            
            if (sendEmail($email, $subject, $body)) {
                $success = "Password reset instructions sent to your email.";
            } else {
                $error = "Failed to send email. Please try again.";
            }
        } else {
            // Don't reveal if email exists or not for security
            $success = "If an account exists and is verified, you will receive reset instructions.";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-key me-2"></i>Forgot Password</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <a href="login.php" class="btn btn-primary w-100">Back to Login</a>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php">Back to Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>