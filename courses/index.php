<?php
require_once '../config/database.php';
require_once '../config/functions.php';
include '../includes/header.php';

$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * COURSES_PER_PAGE;

// Build query
$where = "WHERE 1=1";
$params = [];

if ($category_filter > 0) {
    $where .= " AND c.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM courses c $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / COURSES_PER_PAGE);

// Get courses
$sql = "SELECT c.*, cat.name as category_name 
        FROM courses c 
        JOIN categories cat ON c.category_id = cat.id 
        $where 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?";
$params[] = COURSES_PER_PAGE;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="row">
    <!-- Sidebar Filters -->
    <div class="col-md-3">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Courses</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitize($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="<?php echo sanitize($search); ?>" placeholder="Course title...">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Reset</a>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Course List -->
    <div class="col-md-9">
        <h2 class="mb-4">All Courses</h2>
        
        <?php if (!empty($search)): ?>
            <div class="alert alert-info">Search results for: <strong><?php echo sanitize($search); ?></strong> (<?php echo $total; ?> found)</div>
        <?php endif; ?>
        
        <?php if (count($courses) > 0): ?>
            <div class="row">
                <?php foreach ($courses as $course): ?>
                <div class="col-md-6 mb-4">
                    <div class="card course-card h-100 shadow-sm">
                        <img src="<?php echo $course['image_url'] ?? 'https://via.placeholder.com/300x200'; ?>" 
                             class="card-img-top" alt="<?php echo sanitize($course['title']); ?>">
                        <div class="card-body">
                            <span class="badge bg-primary"><?php echo sanitize($course['category_name']); ?></span>
                            <h5 class="card-title mt-2"><?php echo sanitize($course['title']); ?></h5>
                            <p class="card-text"><?php echo substr(strip_tags($course['description']), 0, 100); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0 text-primary">₹<?php echo number_format($course['price'], 2); ?></span>
                                <a href="detail.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php echo paginate($page, $total_pages, "index.php?category=$category_filter&search=" . urlencode($search) . "&page="); ?>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="alert alert-warning text-center py-5">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h4>No courses found</h4>
                <p>Try adjusting your filters or search criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>