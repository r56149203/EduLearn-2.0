<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if already enrolled
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $course_id]);
        $_SESSION['success'] = "Successfully enrolled in the course!";
    } else {
        $_SESSION['info'] = "You are already enrolled in this course.";
    }
    
    header("Location: detail.php?id=$course_id");
    exit();
}

header("Location: index.php");
exit();
?>