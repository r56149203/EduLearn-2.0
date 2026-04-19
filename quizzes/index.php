<?php
require_once '../config/database.php';
require_once '../config/functions.php';
include '../includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * QUIZZES_PER_PAGE;

// Build query
$where = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (q.title LIKE ? OR c.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM quizzes q JOIN courses c ON q.course_id = c.id $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / QUIZZES_PER_PAGE);

// Get quizzes
$sql = "SELECT q.*, c.title as course_title 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.id 
        $where 
        ORDER BY q.created_at DESC 
        LIMIT ? OFFSET ?";
$params[] = QUIZZES_PER_PAGE;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quizzes = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tasks me-2"></i>Mock Tests</h2>
        </div>
        
        <!-- Search -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-9">
                        <input type="text" name="search" class="form-control" placeholder="Search quizzes..." value="<?php echo sanitize($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (count($quizzes) > 0): ?>
            <div class="row">
                <?php foreach ($quizzes as $quiz):
                    // Count questions
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE quiz_id = ?");
                    $stmt->execute([$quiz['id']]);
                    $q_count = $stmt->fetch()['count'];
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card quiz-card h-100 shadow-sm">
                        <div class="card-body">
                            <span class="badge bg-info"><?php echo sanitize($quiz['course_title']); ?></span>
                            <h4 class="card-title mt-2"><?php echo sanitize($quiz['title']); ?></h4>
                            <p class="card-text"><?php echo sanitize(substr($quiz['description'], 0, 100)); ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <span class="badge bg-secondary me-2"><i class="fas fa-clock me-1"></i><?php echo $quiz['duration']; ?> min</span>
                                    <span class="badge bg-secondary"><i class="fas fa-question-circle me-1"></i><?php echo $q_count; ?> questions</span>
                                </div>
                                <a href="take.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary">Start Quiz</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <?php echo paginate($page, $total_pages, "index.php?search=" . urlencode($search) . "&page="); ?>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h4>No quizzes found</h4>
                <p>Try a different search term or check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>