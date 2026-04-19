<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth_check.php';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get enrolled courses count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE user_id = ?");
$stmt->execute([$user_id]);
$enrolled_count = $stmt->fetch()['count'];

// Get completed courses count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_count = $stmt->fetch()['count'];

// Get quizzes taken count from user_quiz_progress
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_quiz_progress WHERE user_id = ?");
$stmt->execute([$user_id]);
$quiz_count = $stmt->fetch()['count'];

// Get recent courses
$stmt = $pdo->prepare("SELECT c.*, e.enrolled_at, e.status 
                       FROM courses c 
                       JOIN enrollments e ON c.id = e.course_id 
                       WHERE e.user_id = ? 
                       ORDER BY e.enrolled_at DESC 
                       LIMIT 5");
$stmt->execute([$user_id]);
$recent_courses = $stmt->fetchAll();

// Get recent quiz results from user_quiz_progress (since quiz_results might not exist)
$stmt = $pdo->prepare("SELECT qz.*, q.title as quiz_title, c.title as course_title,
                       (SELECT COUNT(*) FROM questions WHERE quiz_id = qz.quiz_id) as total_q
                       FROM user_quiz_progress qz 
                       JOIN quizzes q ON qz.quiz_id = q.id 
                       JOIN courses c ON qz.course_id = c.id 
                       WHERE qz.user_id = ? 
                       ORDER BY qz.attempted_at DESC 
                       LIMIT 5");
$stmt->execute([$user_id]);
$recent_quizzes = $stmt->fetchAll();
?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 3rem;">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                </div>
                <h4><?php echo sanitize($user['name']); ?></h4>
                <p class="text-muted"><?php echo sanitize($user['email']); ?></p>
                <hr>
                <p><strong>Member since:</strong> <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                <a href="../auth/change-password.php" class="btn btn-outline-primary btn-sm w-100">
                    <i class="fas fa-key me-2"></i>Change Password
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="col-md-9">
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="fas fa-book-open"></i>
                    <h3><?php echo $enrolled_count; ?></h3>
                    <p>Enrolled Courses</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $completed_count; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-tasks"></i>
                    <h3><?php echo $quiz_count; ?></h3>
                    <p>Quizzes Taken</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Courses -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Courses</h5>
                <a href="../courses/" class="text-white">View All</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if (count($recent_courses) > 0): ?>
                    <?php foreach ($recent_courses as $course): ?>
                        <a href="../courses/detail.php?id=<?php echo $course['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo sanitize($course['title']); ?></h6>
                                    <small class="text-muted">Enrolled: <?php echo date('M d, Y', strtotime($course['enrolled_at'])); ?></small>
                                </div>
                                <span class="badge <?php echo $course['status'] == 'completed' ? 'bg-success' : 'bg-primary'; ?>">
                                    <?php echo ucfirst($course['status']); ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item text-center text-muted py-4">
                        <i class="fas fa-info-circle me-2"></i>You haven't enrolled in any courses yet.
                        <a href="../courses/" class="d-block mt-2">Browse Courses</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Quiz Results -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Recent Quiz Results</h5>
                <a href="../quizzes/" class="text-white">Take More</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Course</th>
                            <th>Score</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_quizzes) > 0): ?>
                            <?php foreach ($recent_quizzes as $result): 
                                $total_q = $result['total_q'] > 0 ? $result['total_q'] : 1;
                                $percentage = ($result['score'] / $total_q) * 100;
                            ?>
                                <tr>
                                    <td><?php echo sanitize($result['quiz_title']); ?></td>
                                    <td><?php echo sanitize($result['course_title']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $percentage >= 70 ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $result['score']; ?>/<?php echo $total_q; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($result['attempted_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-2"></i>You haven't taken any quizzes yet.
                                    <a href="../quizzes/" class="d-block mt-2">Browse Quizzes</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.stat-card i {
    font-size: 2rem;
    margin-bottom: 10px;
}
.stat-card h3 {
    font-size: 2rem;
    margin: 10px 0;
}
.stat-card p {
    margin: 0;
    opacity: 0.9;
}
</style>

<?php include '../includes/footer.php'; ?>