<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/admin_header.php';

$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get quiz info
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz && $action != 'list') {
    $_SESSION['error'] = "Quiz not found.";
    header("Location: quizzes.php");
    exit();
}

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'] ?? '';
    $option_d = $_POST['option_d'] ?? '';
    $correct_answer = $_POST['correct_answer'];
    
    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer])) {
            $_SESSION['success'] = "Question added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add question.";
        }
        header("Location: questions.php?quiz_id=$quiz_id");
        exit();
    } elseif ($action == 'edit' && $question_id > 0) {
        $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ? WHERE id = ?");
        if ($stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $question_id])) {
            $_SESSION['success'] = "Question updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update question.";
        }
        header("Location: questions.php?quiz_id=$quiz_id");
        exit();
    }
}

// Handle DELETE
if ($action == 'delete' && $question_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    if ($stmt->execute([$question_id])) {
        $_SESSION['success'] = "Question deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete question.";
    }
    header("Location: questions.php?quiz_id=$quiz_id");
    exit();
}

// Get question for editing
$edit_question = null;
if ($action == 'edit' && $question_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $edit_question = $stmt->fetch();
}

// Get all questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Questions for: <?php echo sanitize($quiz['title'] ?? ''); ?></h2>
    <div>
        <a href="quizzes.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Back to Quizzes
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal" onclick="resetQuestionForm()">
            <i class="fas fa-plus me-2"></i>Add Question
        </button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (count($questions) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="300">Question</th>
                            <th>Option A</th>
                            <th>Option B</th>
                            <th>Option C</th>
                            <th>Option D</th>
                            <th>Correct</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $index => $q): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo displayRichText(substr($q['question_text'], 0, 100)); ?>...</td>
                            <td><?php echo displayRichText(substr($q['option_a'], 0, 50)); ?></td>
                            <td><?php echo displayRichText(substr($q['option_b'], 0, 50)); ?></td>
                            <td><?php echo $q['option_c'] ? displayRichText(substr($q['option_c'], 0, 50)) : '-'; ?></td>
                            <td><?php echo $q['option_d'] ? displayRichText(substr($q['option_d'], 0, 50)) : '-'; ?></td>
                            <td><span class="badge bg-success"><?php echo strtoupper($q['correct_answer']); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editQuestion(<?php echo htmlspecialchars(json_encode($q)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>&action=delete&id=<?php echo $q['id']; ?>" 
                                   class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                <p>No questions yet. Click "Add Question" to get started.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Question Modal -->
<div class="modal fade" id="questionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Add New Question</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="questionForm" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <!-- Question Text -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Question Text *</label>
                        <textarea name="question_text" id="question_text" class="form-control" rows="4" required></textarea>
                        <small class="text-muted">You can use HTML for formatting (bold, italic, lists, etc.)</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Option A *</label>
                            <textarea name="option_a" id="option_a" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Option B *</label>
                            <textarea name="option_b" id="option_b" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Option C</label>
                            <textarea name="option_c" id="option_c" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Option D</label>
                            <textarea name="option_d" id="option_d" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Correct Answer *</label>
                        <select name="correct_answer" id="correct_answer" class="form-select" required>
                            <option value="">Select correct answer</option>
                            <option value="a">Option A</option>
                            <option value="b">Option B</option>
                            <option value="c">Option C</option>
                            <option value="d">Option D</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetQuestionForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerHTML = 'Add New Question';
    document.getElementById('questionForm').action = 'questions.php?quiz_id=<?php echo $quiz_id; ?>&action=add';
    document.getElementById('question_text').value = '';
    document.getElementById('option_a').value = '';
    document.getElementById('option_b').value = '';
    document.getElementById('option_c').value = '';
    document.getElementById('option_d').value = '';
    document.getElementById('correct_answer').value = '';
}

function editQuestion(question) {
    resetQuestionForm();
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerHTML = 'Edit Question';
    document.getElementById('questionForm').action = 'questions.php?quiz_id=<?php echo $quiz_id; ?>&action=edit&id=' + question.id;
    document.getElementById('question_text').value = question.question_text;
    document.getElementById('option_a').value = question.option_a;
    document.getElementById('option_b').value = question.option_b;
    document.getElementById('option_c').value = question.option_c || '';
    document.getElementById('option_d').value = question.option_d || '';
    document.getElementById('correct_answer').value = question.correct_answer;
    
    new bootstrap.Modal(document.getElementById('questionModal')).show();
}
</script>

<?php include '../includes/admin_footer.php'; ?>