<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth_check.php';

$quiz_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get quiz details
$stmt = $pdo->prepare("SELECT q.*, c.id as course_id, c.title as course_title 
                       FROM quizzes q 
                       JOIN courses c ON q.course_id = c.id 
                       WHERE q.id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    $_SESSION['error'] = "Quiz not found.";
    header("Location: index.php");
    exit();
}

// Check enrollment
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->execute([$_SESSION['user_id'], $quiz['course_id']]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Please enroll in the course first.";
    header("Location: ../courses/detail.php?id=" . $quiz['course_id']);
    exit();
}

// Get questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    $_SESSION['error'] = "This quiz has no questions yet.";
    header("Location: index.php");
    exit();
}

$current_index = isset($_GET['q']) ? intval($_GET['q']) : 0;
if ($current_index < 0) $current_index = 0;
if ($current_index >= count($questions)) $current_index = count($questions) - 1;

$current_question = $questions[$current_index];

// Initialize session for answers if not exists
if (!isset($_SESSION['quiz_answers'])) {
    $_SESSION['quiz_answers'] = [];
}
if (!isset($_SESSION['quiz_answers'][$quiz_id])) {
    $_SESSION['quiz_answers'][$quiz_id] = [];
}
$saved_answers = $_SESSION['quiz_answers'][$quiz_id];

// Calculate answered count
$answered_count = 0;
foreach ($questions as $q) {
    if (isset($saved_answers["answer_{$q['id']}"]) && !empty($saved_answers["answer_{$q['id']}"])) {
        $answered_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($quiz['title']); ?> - Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .quiz-option {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .quiz-option:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .quiz-option.selected {
            border-color: #28a745;
            background-color: #d4edda;
        }
        .quiz-option input {
            margin-right: 10px;
            transform: scale(1.2);
        }
        .timer-box {
            font-size: 2rem;
            font-weight: bold;
            font-family: monospace;
            background: #2d3748;
            color: #fbbf24;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
        }
        .question-palette .btn {
            margin: 3px;
            width: 45px;
        }
        .question-content img {
            max-width: 100%;
            height: auto;
        }
        .answered-count {
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Question Palette</h5>
                    </div>
                    <div class="card-body">
                        <div class="question-palette" id="questionPalette">
                            <?php foreach ($questions as $index => $q): 
                                $isAnswered = isset($saved_answers["answer_{$q['id']}"]) && !empty($saved_answers["answer_{$q['id']}"]);
                            ?>
                                <a href="javascript:void(0)" 
                                   onclick="navigateToQuestion(<?php echo $index; ?>)" 
                                   class="btn btn-sm <?php echo $index == $current_index ? 'btn-primary' : ($isAnswered ? 'btn-success' : 'btn-outline-secondary'); ?>"
                                   data-qid="<?php echo $q['id']; ?>"
                                   data-index="<?php echo $index; ?>">
                                    <?php echo $index + 1; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <hr>
                        <div class="timer-box" id="timer">00:00:00</div>
                        <div class="answered-count text-center mt-3">
                            <span class="badge bg-success" id="answeredCount">Answered: <?php echo $answered_count; ?></span>
                            <span class="badge bg-danger" id="unansweredCount">Unanswered: <?php echo count($questions) - $answered_count; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo sanitize($quiz['title']); ?></h4>
                            <span>Question <?php echo $current_index + 1; ?> of <?php echo count($questions); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="quizForm" method="POST" action="submit.php">
                            <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                            
                            <div class="question-content mb-4">
                                <h5><?php echo displayRichText($current_question['question_text']); ?></h5>
                            </div>
                            
                            <?php
                            $options = [
                                'a' => $current_question['option_a'],
                                'b' => $current_question['option_b'],
                                'c' => $current_question['option_c'],
                                'd' => $current_question['option_d']
                            ];
                            
                            $current_answer = $saved_answers["answer_{$current_question['id']}"] ?? '';
                            
                            foreach ($options as $key => $option):
                                if (empty($option)) continue;
                                $checked = ($current_answer == $key);
                            ?>
                            <div class="quiz-option <?php echo $checked ? 'selected' : ''; ?>" data-value="<?php echo $key; ?>" onclick="selectOption(this, '<?php echo $key; ?>', <?php echo $current_question['id']; ?>, <?php echo $current_index; ?>)">
                                <input type="radio" name="answer_<?php echo $current_question['id']; ?>" 
                                       value="<?php echo $key; ?>" id="opt_<?php echo $key; ?>" 
                                       <?php echo $checked ? 'checked' : ''; ?> 
                                       onchange="saveAnswer(<?php echo $current_question['id']; ?>, <?php echo $current_index; ?>)">
                                <label for="opt_<?php echo $key; ?>" class="fw-bold"><?php echo strtoupper($key); ?>.</label>
                                <span><?php echo displayRichText($option); ?></span>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <?php if ($current_index > 0): ?>
                                    <a href="javascript:void(0)" onclick="navigateToQuestion(<?php echo $current_index - 1; ?>)" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Previous
                                    </a>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-warning" onclick="clearCurrentResponse(<?php echo $current_question['id']; ?>, <?php echo $current_index; ?>)">
                                    <i class="fas fa-times me-2"></i>Clear Response
                                </button>
                                
                                <?php if ($current_index < count($questions) - 1): ?>
                                    <a href="javascript:void(0)" onclick="navigateToQuestion(<?php echo $current_index + 1; ?>)" class="btn btn-primary">
                                        Next<i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Submit quiz? You cannot change answers after submission.')">
                                        <i class="fas fa-check-circle me-2"></i>Submit Quiz
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Timer functionality
    const duration = <?php echo $quiz['duration'] * 60; ?>;
    let timeLeft = localStorage.getItem('quiz_time_<?php echo $quiz_id; ?>') || duration;
    
    function updateTimer() {
        const hours = Math.floor(timeLeft / 3600);
        const minutes = Math.floor((timeLeft % 3600) / 60);
        const seconds = timeLeft % 60;
        document.getElementById('timer').textContent = 
            `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        localStorage.setItem('quiz_time_<?php echo $quiz_id; ?>', timeLeft);
        
        if (timeLeft <= 0) {
            localStorage.removeItem('quiz_time_<?php echo $quiz_id; ?>');
            document.getElementById('quizForm').submit();
        } else {
            timeLeft--;
            setTimeout(updateTimer, 1000);
        }
    }
    updateTimer();
    
    // Update the palette button style
    function updatePaletteButton(questionIndex, isAnswered) {
        const palette = document.getElementById('questionPalette');
        const buttons = palette.querySelectorAll('.btn');
        if (buttons[questionIndex]) {
            if (isAnswered) {
                buttons[questionIndex].classList.remove('btn-outline-secondary', 'btn-primary');
                buttons[questionIndex].classList.add('btn-success');
            } else {
                if (questionIndex == <?php echo $current_index; ?>) {
                    buttons[questionIndex].classList.remove('btn-outline-secondary', 'btn-success');
                    buttons[questionIndex].classList.add('btn-primary');
                } else {
                    buttons[questionIndex].classList.remove('btn-success', 'btn-primary');
                    buttons[questionIndex].classList.add('btn-outline-secondary');
                }
            }
        }
    }
    
    // Update answer counts display
    function updateAnswerCounts() {
        const totalQuestions = <?php echo count($questions); ?>;
        let answered = 0;
        
        <?php foreach ($questions as $q): ?>
            const answer_<?php echo $q['id']; ?> = document.querySelector('input[name="answer_<?php echo $q['id']; ?>"]:checked');
            if (answer_<?php echo $q['id']; ?>) answered++;
        <?php endforeach; ?>
        
        document.getElementById('answeredCount').innerHTML = 'Answered: ' + answered;
        document.getElementById('unansweredCount').innerHTML = 'Unanswered: ' + (totalQuestions - answered);
    }
    
    // Save answer via AJAX
    function saveAnswer(questionId, questionIndex) {
        const formData = new FormData(document.getElementById('quizForm'));
        const answers = {};
        
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('answer_')) {
                answers[key] = value;
            }
        }
        
        fetch('../api/save-answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                quiz_id: <?php echo $quiz_id; ?>,
                answers: answers
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Check if this question is now answered
                const isAnswered = document.querySelector('input[name="answer_' + questionId + '"]:checked') !== null;
                updatePaletteButton(questionIndex, isAnswered);
                updateAnswerCounts();
            }
        })
        .catch(err => console.error('Error saving answer:', err));
    }
    
    // Clear ONLY the current question's response
    function clearCurrentResponse(questionId, questionIndex) {
        // Clear the radio button for this specific question
        const radioButtons = document.querySelectorAll('input[name="answer_' + questionId + '"]');
        radioButtons.forEach(radio => {
            radio.checked = false;
        });
        
        // Remove selected class from options for this question
        document.querySelectorAll('.quiz-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        // Save the cleared state
        saveAnswer(questionId, questionIndex);
    }
    
    function selectOption(card, value, questionId, questionIndex) {
        const radio = card.querySelector('input[type="radio"]');
        radio.checked = true;
        
        // Remove selected class from all options for this question
        document.querySelectorAll('.quiz-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        card.classList.add('selected');
        
        saveAnswer(questionId, questionIndex);
    }
    
    // Navigate to another question
    function navigateToQuestion(index) {
        // Save current answers before navigating
        const formData = new FormData(document.getElementById('quizForm'));
        const answers = {};
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('answer_')) {
                answers[key] = value;
            }
        }
        
        fetch('../api/save-answer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ quiz_id: <?php echo $quiz_id; ?>, answers: answers })
        })
        .then(() => {
            // Navigate to the new question
            window.location.href = 'take.php?id=<?php echo $quiz_id; ?>&q=' + index;
        })
        .catch(err => console.error('Error:', err));
    }
    
    // Initial update of counts
    setTimeout(updateAnswerCounts, 100);
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>