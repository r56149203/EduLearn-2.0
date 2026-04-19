<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/admin_header.php';

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$action = $_GET['action'] ?? 'list';
$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get course info
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course && $action != 'list') {
    header("Location: courses.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $video_url = trim($_POST['video_url']);
    $duration = intval($_POST['duration']);
    $order_number = intval($_POST['order_number']);
    
    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO lessons (course_id, title, content, video_url, duration, order_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$course_id, $title, $content, $video_url, $duration, $order_number]);
        $_SESSION['success'] = "Lesson added successfully!";
        header("Location: lessons.php?course_id=$course_id");
        exit();
    } elseif ($action == 'edit' && $lesson_id > 0) {
        $stmt = $pdo->prepare("UPDATE lessons SET title = ?, content = ?, video_url = ?, duration = ?, order_number = ? WHERE id = ?");
        $stmt->execute([$title, $content, $video_url, $duration, $order_number, $lesson_id]);
        $_SESSION['success'] = "Lesson updated successfully!";
        header("Location: lessons.php?course_id=$course_id");
        exit();
    }
}

// Handle delete
if ($action == 'delete' && $lesson_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $_SESSION['success'] = "Lesson deleted successfully!";
    header("Location: lessons.php?course_id=$course_id");
    exit();
}

// Get lesson for editing
$lesson = null;
if ($action == 'edit' && $lesson_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch();
}

// Get all lessons
$lessons = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number");
$lessons->execute([$course_id]);
$lessons = $lessons->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Lessons: <?php echo sanitize($course['title'] ?? ''); ?></h2>
    <div>
        <a href="courses.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Back to Courses
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lessonModal" onclick="resetLessonForm()">
            <i class="fas fa-plus me-2"></i>Add Lesson
        </button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (count($lessons) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">Order</th>
                            <th>Title</th>
                            <th>Duration</th>
                            <th>Video</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lessons as $l): ?>
                        <tr>
                            <td><?php echo $l['order_number']; ?></td>
                            <td><strong><?php echo sanitize($l['title']); ?></strong><br>
                                <small class="text-muted"><?php echo substr(strip_tags($l['content']), 0, 100); ?>...</small>
                             </td>
                            <td><?php echo $l['duration'] > 0 ? $l['duration'] . ' min' : '-'; ?></td>
                            <td><?php echo $l['video_url'] ? '<i class="fas fa-video text-success"></i>' : '-'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editLesson(<?php echo htmlspecialchars(json_encode($l)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="lessons.php?course_id=<?php echo $course_id; ?>&action=delete&id=<?php echo $l['id']; ?>" 
                                   class="btn btn-sm btn-danger" onclick="return confirm('Delete this lesson?')">
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
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <p>No lessons yet. Click "Add Lesson" to get started.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Lesson Modal -->
<div class="modal fade" id="lessonModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Add New Lesson</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="lessonForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Lesson Title *</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Duration (min)</label>
                            <input type="number" name="duration" id="duration" class="form-control" value="0">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Order</label>
                            <input type="number" name="order_number" id="order_number" class="form-control" value="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Video URL (YouTube embed)</label>
                        <input type="text" name="video_url" id="video_url" class="form-control" placeholder="https://www.youtube.com/embed/...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Lesson Content</label>
                        <textarea name="content" id="content" class="form-control" rows="8"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Lesson</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetLessonForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerHTML = 'Add New Lesson';
    document.getElementById('lessonForm').action = 'lessons.php?course_id=<?php echo $course_id; ?>&action=add';
    document.getElementById('title').value = '';
    document.getElementById('duration').value = '0';
    document.getElementById('order_number').value = '0';
    document.getElementById('video_url').value = '';
    document.getElementById('content').value = '';
}

function editLesson(lesson) {
    resetLessonForm();
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerHTML = 'Edit Lesson';
    document.getElementById('lessonForm').action = 'lessons.php?course_id=<?php echo $course_id; ?>&action=edit&id=' + lesson.id;
    document.getElementById('title').value = lesson.title;
    document.getElementById('duration').value = lesson.duration;
    document.getElementById('order_number').value = lesson.order_number;
    document.getElementById('video_url').value = lesson.video_url || '';
    document.getElementById('content').value = lesson.content || '';
    
    new bootstrap.Modal(document.getElementById('lessonModal')).show();
}
</script>

<?php include '../includes/admin_footer.php'; ?>