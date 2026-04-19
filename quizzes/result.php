<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth_check.php';

$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
$score = isset($_GET['score']) ? intval($_GET['score']) : 0;
$total = isset($_GET['total']) ? intval($_GET['total']) : 0;

if (!$quiz_id) {
    header("Location: index.php");
    exit();
}

// Get quiz details
$stmt = $pdo->prepare("SELECT q.*, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

// Get questions with answers for review
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

$user_answers = $_SESSION['quiz_submitted_answers'] ?? [];
$percentage = $total > 0 ? ($score / $total) * 100 : 0;
$passing_score = $quiz['passing_score'] ?? 70;
$passed = $percentage >= $passing_score;

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-sm mb-4">
            <div class="card-header <?php echo $passed ? 'bg-success' : 'bg-warning'; ?> text-white text-center">
                <h2 class="mb-0">Quiz Results</h2>
            </div>
            <div class="card-body text-center">
                <h3><?php echo sanitize($quiz['title']); ?></h3>
                
                <div class="my-4">
                    <div class="display-1 <?php echo $passed ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $score; ?>/<?php echo $total; ?>
                    </div>
                    <h4><?php echo number_format($percentage, 1); ?>%</h4>
                    
                    <?php if ($passed): ?>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-trophy me-2"></i>Congratulations! You passed the quiz.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>Keep practicing! You need <?php echo $passing_score; ?>% to pass.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-center gap-3">
                    <a href="../courses/detail.php?id=<?php echo $quiz['course_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-book me-2"></i>Back to Course
                    </a>
                    <a href="take.php?id=<?php echo $quiz_id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-redo me-2"></i>Retry Quiz
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Review Questions -->
        <?php if (!empty($questions)): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-search me-2"></i>Review Answers</h4>
            </div>
            <div class="card-body">
                <?php foreach ($questions as $index => $q):
                    $user_answer = $user_answers["answer_{$q['id']}"] ?? 'not_answered';
                    $is_correct = $user_answer == $q['correct_answer'];
                ?>
                    <div class="review-question mb-4 p-3 rounded <?php echo $is_correct ? 'bg-light' : 'bg-danger bg-opacity-10'; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6>Question <?php echo $index + 1; ?>:</h6>
                            <span class="badge <?php echo $is_correct ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $is_correct ? 'Correct' : 'Incorrect'; ?>
                            </span>
                        </div>
                        <div class="mb-3"><?php echo displayRichText($q['question_text']); ?></div>
                        
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <small class="text-muted">Your answer:</small>
                                <div class="p-2 rounded <?php echo $is_correct ? 'bg-success bg-opacity-25' : 'bg-danger bg-opacity-25'; ?>">
                                    <?php
                                    $answer_text = 'Not answered';
                                    switch($user_answer) {
                                        case 'a': $answer_text = $q['option_a']; break;
                                        case 'b': $answer_text = $q['option_b']; break;
                                        case 'c': $answer_text = $q['option_c']; break;
                                        case 'd': $answer_text = $q['option_d']; break;
                                    }
                                    echo displayRichText($answer_text);
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Correct answer:</small>
                                <div class="p-2 rounded bg-success bg-opacity-25">
                                    <?php
                                    $correct_text = '';
                                    switch($q['correct_answer']) {
                                        case 'a': $correct_text = $q['option_a']; break;
                                        case 'b': $correct_text = $q['option_b']; break;
                                        case 'c': $correct_text = $q['option_c']; break;
                                        case 'd': $correct_text = $q['option_d']; break;
                                    }
                                    echo displayRichText($correct_text);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($index < count($questions) - 1): ?><hr><?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
unset($_SESSION['quiz_submitted_answers']);
include '../includes/footer.php';
?>