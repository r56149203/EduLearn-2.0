<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quiz_id'])) {
    $quiz_id = intval($_POST['quiz_id']);
    $user_id = $_SESSION['user_id'];
    
    // Get answers from session
    $user_answers = isset($_SESSION['quiz_answers'][$quiz_id]) ? $_SESSION['quiz_answers'][$quiz_id] : [];
    
    // Get quiz info first
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        $_SESSION['error'] = "Quiz not found.";
        header("Location: index.php");
        exit();
    }
    
    // Get questions
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
    $total_questions = count($questions);
    
    // Calculate score
    $score = 0;
    foreach ($questions as $question) {
        $answer_key = "answer_" . $question['id'];
        if (isset($user_answers[$answer_key]) && $user_answers[$answer_key] == $question['correct_answer']) {
            $score++;
        }
    }
    
    $percentage = $total_questions > 0 ? ($score / $total_questions) * 100 : 0;
    $passed = $percentage >= $quiz['passing_score'];
    $course_id = $quiz['course_id'];
    
    // ALWAYS INSERT NEW RECORD - No ON DUPLICATE KEY UPDATE
    $stmt = $pdo->prepare("INSERT INTO user_quiz_progress (user_id, quiz_id, course_id, score, total_questions, attempted_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$user_id, $quiz_id, $course_id, $score, $total_questions]);
    
    if (!$result) {
        error_log("Failed to save quiz progress: " . print_r($stmt->errorInfo(), true));
    }
    
    // Also save to quiz_results for detailed history
    try {
        $stmt = $pdo->prepare("INSERT INTO quiz_results (user_id, quiz_id, score, total_questions, percentage, passed, submitted_at) 
                               VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $quiz_id, $score, $total_questions, $percentage, $passed]);
    } catch (PDOException $e) {
        // Create quiz_results table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_results (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            quiz_id INT NOT NULL,
            score INT NOT NULL,
            total_questions INT NOT NULL,
            percentage DECIMAL(5,2) DEFAULT 0,
            passed BOOLEAN DEFAULT FALSE,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
        )");
        // Retry insert
        $stmt = $pdo->prepare("INSERT INTO quiz_results (user_id, quiz_id, score, total_questions, percentage, passed, submitted_at) 
                               VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $quiz_id, $score, $total_questions, $percentage, $passed]);
    }
    
    // Store answers for result page
    $_SESSION['quiz_submitted_answers'] = $user_answers;
    
    // Clear session data for this quiz
    unset($_SESSION['quiz_answers'][$quiz_id]);
    unset($_SESSION['quiz_timer'][$quiz_id]);
    
    // Redirect to result
    header("Location: result.php?quiz_id=$quiz_id&score=$score&total=$total_questions");
    exit();
}

header("Location: index.php");
exit();
?>