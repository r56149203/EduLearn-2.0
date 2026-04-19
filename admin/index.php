<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/admin_header.php';

// Get stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$total_quizzes = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
$total_questions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$total_enrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();

// Recent users
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent enrollments
$recent_enrollments = $pdo->prepare("
    SELECT e.*, u.name as user_name, c.title as course_title 
    FROM enrollments e 
    JOIN users u ON e.user_id = u.id 
    JOIN courses c ON e.course_id = c.id 
    ORDER BY e.enrolled_at DESC LIMIT 5
");
$recent_enrollments->execute();
$recent_enrollments = $recent_enrollments->fetchAll();
?>

<h2 class="mb-4">Admin Dashboard</h2>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Users</h6>
                        <h2 class="mb-0"><?php echo $total_users; ?></h2>
                    </div>
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Courses</h6>
                        <h2 class="mb-0"><?php echo $total_courses; ?></h2>
                    </div>
                    <i class="fas fa-book fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Quizzes</h6>
                        <h2 class="mb-0"><?php echo $total_quizzes; ?></h2>
                    </div>
                    <i class="fas fa-tasks fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Enrollments</h6>
                        <h2 class="mb-0"><?php echo $total_enrollments; ?></h2>
                    </div>
                    <i class="fas fa-graduation-cap fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Recent Users</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($recent_users as $user): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo sanitize($user['name']); ?></strong><br>
                                <small class="text-muted"><?php echo sanitize($user['email']); ?></small>
                            </div>
                            <span class="badge <?php echo $user['email_verified'] ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $user['email_verified'] ? 'Verified' : 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Enrollments</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($recent_enrollments as $enrollment): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo sanitize($enrollment['user_name']); ?></strong><br>
                                <small><?php echo sanitize($enrollment['course_title']); ?></small>
                            </div>
                            <small class="text-muted"><?php echo date('M d', strtotime($enrollment['enrolled_at'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>