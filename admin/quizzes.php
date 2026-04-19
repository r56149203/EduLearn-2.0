<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/admin_header.php';

$action = $_GET['action'] ?? 'list';
$quiz_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = intval($_POST['course_id']);
    $title = trim($_POST['title']);
    $description = $_POST['description'];
    $duration = intval($_POST['duration']);
    $passing_score = intval($_POST['passing_score']);
    
    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO quizzes (course_id, title, description, duration, passing_score) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$course_id, $title, $description, $duration, $passing_score]);
        $_SESSION['success'] = "Quiz added successfully!";
        header("Location: quizzes.php");
        exit();
    } elseif ($action == 'edit' && $quiz_id > 0) {
        $stmt = $pdo->prepare("UPDATE quizzes SET course_id = ?, title = ?, description = ?, duration = ?, passing_score = ? WHERE id = ?");
        $stmt->execute([$course_id, $title, $description, $duration, $passing_score, $quiz_id]);
        $_SESSION['success'] = "Quiz updated successfully!";
        header("Location: quizzes.php");
        exit();
    }
}

// Handle delete
if ($action == 'delete' && $quiz_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $_SESSION['success'] = "Quiz deleted successfully!";
    header("Location: quizzes.php");
    exit();
}

// Get quiz for editing
$quiz = null;
if ($action == 'edit' && $quiz_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
}

// Get all quizzes
$quizzes = $pdo->query("SELECT q.*, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id = c.id ORDER BY q.created_at DESC")->fetchAll();
$courses = $pdo->query("SELECT id, title FROM courses ORDER BY title")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Quiz Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quizModal" onclick="resetQuizForm()">
        <i class="fas fa-plus me-2"></i>Add New Quiz
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (count($quizzes) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Course</th>
                            <th>Duration</th>
                            <th>Passing Score</th>
                            <th>Questions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $q): 
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE quiz_id = ?");
                            $stmt->execute([$q['id']]);
                            $q_count = $stmt->fetch()['count'];
                        ?>
                        <tr>
                            <td><?php echo $q['id']; ?></td>
                            <td><strong><?php echo sanitize($q['title']); ?></strong><br>
                                <small class="text-muted"><?php echo substr(strip_tags($q['description']), 0, 50); ?></small>
                             </td>
                            <td><?php echo sanitize($q['course_title']); ?></td>
                            <td><?php echo $q['duration']; ?> min</td>
                            <td><?php echo $q['passing_score']; ?>%</td>
                            <td>
                                <a href="questions.php?quiz_id=<?php echo $q['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-question-circle me-1"></i><?php echo $q_count; ?> Questions
                                </a>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editQuiz(<?php echo htmlspecialchars(json_encode($q)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="quizzes.php?action=delete&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this quiz? All questions will be deleted too.')">
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
                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                <p>No quizzes yet. Click "Add New Quiz" to get started.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quiz Modal -->
<div class="modal fade" id="quizModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Add New Quiz</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="quizForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Course *</label>
                        <select name="course_id" id="course_id" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo sanitize($c['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quiz Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (minutes) *</label>
                            <input type="number" name="duration" id="duration" class="form-control" value="30" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Passing Score (%) *</label>
                            <input type="number" name="passing_score" id="passing_score" class="form-control" value="70" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Quiz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetQuizForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerHTML = 'Add New Quiz';
    document.getElementById('quizForm').action = 'quizzes.php?action=add';
    document.getElementById('course_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('description').value = '';
    document.getElementById('duration').value = '30';
    document.getElementById('passing_score').value = '70';
}

function editQuiz(quiz) {
    resetQuizForm();
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerHTML = 'Edit Quiz';
    document.getElementById('quizForm').action = 'quizzes.php?action=edit&id=' + quiz.id;
    document.getElementById('course_id').value = quiz.course_id;
    document.getElementById('title').value = quiz.title;
    document.getElementById('description').value = quiz.description || '';
    document.getElementById('duration').value = quiz.duration;
    document.getElementById('passing_score').value = quiz.passing_score;
    
    new bootstrap.Modal(document.getElementById('quizModal')).show();
}
</script>

<?php include '../includes/admin_footer.php'; ?>