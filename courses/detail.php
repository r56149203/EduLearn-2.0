<?php
require_once '../config/database.php';
require_once '../config/functions.php';
include '../includes/header.php';

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get course details
$stmt = $pdo->prepare("SELECT c.*, cat.name as category_name FROM courses c JOIN categories cat ON c.category_id = cat.id WHERE c.id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Course not found.";
    header("Location: index.php");
    exit();
}

// Check enrollment
$is_enrolled = false;
$progress = 0;

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $is_enrolled = $stmt->fetch();
    
    if ($is_enrolled) {
        $progress = getUserProgress($_SESSION['user_id'], $course_id, $pdo);
    }
}

// Get lessons
$lessons = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number");
$lessons->execute([$course_id]);
$lessons = $lessons->fetchAll();

// Get quizzes
$quizzes = $pdo->prepare("SELECT * FROM quizzes WHERE course_id = ?");
$quizzes->execute([$course_id]);
$quizzes = $quizzes->fetchAll();

// Get completed lessons
$completed_lessons = [];
if ($is_enrolled) {
    $stmt = $pdo->prepare("SELECT lesson_id FROM user_lesson_progress WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $completed_lessons = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<div class="row">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Courses</a></li>
                <li class="breadcrumb-item active"><?php echo sanitize($course['title']); ?></li>
            </ol>
        </nav>
        
        <div class="card shadow-sm mb-4">
            <?php if ($course['image_url']): ?>
                <img src="<?php echo $course['image_url']; ?>" class="card-img-top" alt="<?php echo sanitize($course['title']); ?>" style="max-height: 400px; object-fit: cover;">
            <?php endif; ?>
            <div class="card-body">
                <span class="badge bg-primary mb-2"><?php echo sanitize($course['category_name']); ?></span>
                <h1 class="card-title"><?php echo sanitize($course['title']); ?></h1>
                <div class="rich-text-content mt-3">
                    <?php echo displayRichText($course['description']); ?>
                </div>
            </div>
        </div>
        
        <!-- Lessons Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-book me-2"></i>Course Lessons (<?php echo count($lessons); ?>)</h4>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($lessons as $index => $lesson): 
                    $completed = in_array($lesson['id'], $completed_lessons);
                ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($is_enrolled): ?>
                                    <i class="fas fa-<?php echo $completed ? 'check-circle text-success' : 'circle text-muted'; ?> me-2"></i>
                                <?php endif; ?>
                                <strong>Lesson <?php echo $index + 1; ?>:</strong> <?php echo sanitize($lesson['title']); ?>
                            </div>
                            <?php if ($is_enrolled): ?>
                                <a href="../lessons/view.php?id=<?php echo $lesson['id']; ?>" class="btn btn-sm btn-primary">
                                    <?php echo $completed ? 'Review' : 'Start'; ?>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>Enroll to Access</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($lessons)): ?>
                    <div class="list-group-item text-center text-muted py-4">
                        <i class="fas fa-info-circle me-2"></i>No lessons added yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quizzes Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-tasks me-2"></i>Quizzes (<?php echo count($quizzes); ?>)</h4>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-question-circle text-info me-2"></i>
                                <?php echo sanitize($quiz['title']); ?>
                                <small class="text-muted ms-2">(<?php echo $quiz['duration']; ?> mins)</small>
                            </div>
                            <?php if ($is_enrolled): ?>
                                <a href="../quizzes/take.php?id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-success">Take Quiz</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>Enroll to Access</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($quizzes)): ?>
                    <div class="list-group-item text-center text-muted py-4">
                        <i class="fas fa-info-circle me-2"></i>No quizzes added yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Course Info</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Price:</strong>
                    <h3 class="text-primary">₹<?php echo number_format($course['price'], 2); ?></h3>
                </div>
                <div class="mb-3">
                    <strong>Level:</strong>
                    <span class="badge bg-info"><?php echo ucfirst($course['level']); ?></span>
                </div>
                <div class="mb-3">
                    <strong>Lessons:</strong> <?php echo count($lessons); ?>
                </div>
                <hr>
                
                <?php if ($is_enrolled): ?>
                    <div class="mb-3">
                        <strong>Your Progress:</strong>
                        <div class="progress mt-2">
                            <div class="progress-bar" style="width: <?php echo $progress; ?>%"><?php echo $progress; ?>%</div>
                        </div>
                    </div>
                    <a href="../lessons/view.php?id=<?php echo $lessons[0]['id'] ?? '#'; ?>" class="btn btn-success w-100">
                        <i class="fas fa-play me-2"></i>Continue Learning
                    </a>
                <?php else: ?>
                    <form method="POST" action="enroll.php">
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-graduation-cap me-2"></i>
                            <?php echo $course['price'] > 0 ? 'Enroll Now' : 'Enroll for Free'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>