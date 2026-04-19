<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (isLoggedIn()) {
    header("Location: " . SITE_URL . "dashboard/");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            // Create user
            $verification_token = generateToken();
            $token_expiry = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRY_HOURS . ' hours'));
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, verification_token, token_expires_at) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $hashed_password, $verification_token, $token_expiry])) {
                // Send verification email
                $verify_link = SITE_URL . "auth/verify-email.php?token=" . $verification_token;
                $subject = "Verify Your Email - " . SITE_NAME;
                $body = "
                    <html>
                    <body style='font-family: Arial, sans-serif;'>
                        <h2>Welcome to " . SITE_NAME . "!</h2>
                        <p>Hello {$name},</p>
                        <p>Please click the link below to verify your email address:</p>
                        <p><a href='{$verify_link}'>Verify Email Address</a></p>
                        <p>This link expires in " . TOKEN_EXPIRY_HOURS . " hours.</p>
                        <p>If you didn't create an account, please ignore this email.</p>
                        <br>
                        <p>Best regards,<br>" . SITE_NAME . " Team</p>
                    </body>
                    </html>
                ";
                
                sendEmail($email, $subject, $body);
                $success = 'Registration successful! Please check your email to verify your account.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create Account</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-envelope me-2"></i><?php echo $success; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                            <small class="text-muted">We'll send a verification link to this email.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                            <small class="text-muted">Minimum 6 characters.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>