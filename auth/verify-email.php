<?php
require_once '../config/database.php';
require_once '../config/functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    // Show resend form
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
        $email = trim($_POST['email']);
        
        $stmt = $pdo->prepare("SELECT id, name, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && !$user['email_verified']) {
            $new_token = generateToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRY_HOURS . ' hours'));
            
            $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires_at = ? WHERE id = ?");
            $stmt->execute([$new_token, $expiry, $user['id']]);
            
            $verify_link = SITE_URL . "auth/verify-email.php?token=" . $new_token;
            $subject = "Verify Your Email - " . SITE_NAME;
            $body = "<p>Click <a href='{$verify_link}'>here</a> to verify your email.</p>";
            
            if (sendEmail($email, $subject, $body)) {
                $success = "Verification email sent to {$email}";
            } else {
                $error = "Failed to send email. Please try again.";
            }
        } else {
            $error = "Email not found or already verified.";
        }
    }
} else {
    // Verify token
    $stmt = $pdo->prepare("SELECT id, email_verified, token_expires_at FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        if ($user['email_verified']) {
            $success = "Email already verified. You can now login.";
        } elseif (strtotime($user['token_expires_at']) < time()) {
            $error = "Verification link has expired. Please request a new one.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            $success = "Email verified successfully! You can now login.";
        }
    } else {
        $error = "Invalid verification token.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Email Verification</h4>
            </div>
            <div class="card-body text-center">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                    <a href="login.php" class="btn btn-primary mt-3">Go to Login</a>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#resendModal">
                        Resend Verification Email
                    </button>
                <?php else: ?>
                    <p>Enter your email to receive a verification link.</p>
                    <form method="POST" class="mt-3">
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Your email address" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Verification Email</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Resend Modal -->
<div class="modal fade" id="resendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resend Verification Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>