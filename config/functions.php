<?php
require_once __DIR__ . '/constants.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to access this page.';
        header("Location: " . SITE_URL . "auth/login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        header("Location: " . SITE_URL . "index.php");
        exit();
    }
}

// Display rich text content safely
function displayRichText($content) {
    if (empty($content)) return '';
    
    $allowed_tags = [
        'b' => [], 'strong' => [], 'i' => [], 'em' => [], 'u' => [],
        'br' => [], 'p' => ['class' => true], 'span' => ['class' => true, 'style' => true],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'ul' => ['class' => true], 'ol' => ['class' => true], 'li' => ['class' => true],
        'a' => ['href' => true, 'target' => true, 'title' => true],
        'img' => ['src' => true, 'alt' => true, 'width' => true, 'height' => true, 'style' => true],
        'pre' => ['class' => true], 'code' => ['class' => true],
        'blockquote' => ['class' => true], 'hr' => [],
        'table' => ['class' => true, 'border' => true], 'tr' => [], 'td' => ['colspan' => true], 'th' => []
    ];
    
    return stripslashes(html_entity_decode(strip_tags($content, array_keys($allowed_tags))));
}

// Generate slug
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Send email using PHPMailer
function sendEmail($to, $subject, $body) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // SMTP configuration (update with your credentials)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';
        $mail->Password   = 'your-app-password';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom(SITE_EMAIL, SITE_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Generate verification token
function generateToken() {
    return bin2hex(random_bytes(32));
}

// Pagination helper
function paginate($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . ($current_page - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $current_page ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . ($current_page + 1) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Get user progress for course
function getUserProgress($user_id, $course_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT l.id) as total_lessons,
               COUNT(DISTINCT ulp.lesson_id) as completed_lessons
        FROM lessons l
        LEFT JOIN user_lesson_progress ulp ON l.id = ulp.lesson_id AND ulp.user_id = ?
        WHERE l.course_id = ?
    ");
    $stmt->execute([$user_id, $course_id]);
    $result = $stmt->fetch();
    
    $total = $result['total_lessons'];
    $completed = $result['completed_lessons'];
    
    return $total > 0 ? round(($completed / $total) * 100) : 0;
}
?>