<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth_check.php';
include '../includes/header.php';

$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get lesson details
$stmt = $pdo->prepare("SELECT l.*, c.title as course_title, c.id as course_id 
                       FROM lessons l 
                       JOIN courses c ON l.course_id = c.id 
                       WHERE l.id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    $_SESSION['error'] = "Lesson not found.";
    header("Location: ../courses/");
    exit();
}

// Check enrollment
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->execute([$_SESSION['user_id'], $lesson['course_id']]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Please enroll in the course first.";
    header("Location: ../courses/detail.php?id=" . $lesson['course_id']);
    exit();
}

// Mark as completed
$stmt = $pdo->prepare("INSERT INTO user_lesson_progress (user_id, lesson_id, course_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE completed_at = NOW()");
$stmt->execute([$_SESSION['user_id'], $lesson_id, $lesson['course_id']]);

// Get all lessons for navigation
$lessons = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number");
$lessons->execute([$lesson['course_id']]);
$all_lessons = $lessons->fetchAll();

// Find current position
$current_index = -1;
foreach ($all_lessons as $i => $l) {
    if ($l['id'] == $lesson_id) {
        $current_index = $i;
        break;
    }
}

$prev_lesson = $all_lessons[$current_index - 1] ?? null;
$next_lesson = $all_lessons[$current_index + 1] ?? null;
?>

<div class="row">
    <div class="col-md-9">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../courses/">Courses</a></li>
                <li class="breadcrumb-item"><a href="../courses/detail.php?id=<?php echo $lesson['course_id']; ?>"><?php echo sanitize($lesson['course_title']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo sanitize($lesson['title']); ?></li>
            </ol>
        </nav>
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><?php echo sanitize($lesson['title']); ?></h2>
            </div>
            <div class="card-body">
                <?php if ($lesson['video_url']): ?>
                    <div class="ratio ratio-16x9 mb-4">
                        <iframe src="<?php echo $lesson['video_url']; ?>" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>
                
                <div class="rich-text-content">
                    <?php echo displayRichText($lesson['content']); ?>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <?php if ($prev_lesson): ?>
                        <a href="view.php?id=<?php echo $prev_lesson['id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Previous
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    
                    <?php if ($next_lesson): ?>
                        <a href="view.php?id=<?php echo $next_lesson['id']; ?>" class="btn btn-primary">
                            Next<i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    <?php else: ?>
                        <a href="../courses/detail.php?id=<?php echo $lesson['course_id']; ?>" class="btn btn-success">
                            <i class="fas fa-check-circle me-2"></i>Complete Course
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Course Lessons</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($all_lessons as $index => $l): ?>
                    <a href="view.php?id=<?php echo $l['id']; ?>" class="list-group-item list-group-item-action <?php echo $l['id'] == $lesson_id ? 'active' : ''; ?>">
                        <small><?php echo $index + 1; ?>. <?php echo sanitize($l['title']); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card shadow-sm mt-4">
            <div class="card-body text-center">
                <h5>Progress</h5>
                <div class="progress mb-2">
                    <div class="progress-bar" style="width: <?php echo round(($current_index + 1) / count($all_lessons) * 100); ?>%"></div>
                </div>
                <small><?php echo $current_index + 1; ?> of <?php echo count($all_lessons); ?> lessons completed</small>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>