<?php
require_once 'config/database.php';
require_once 'config/functions.php';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold">Learn Anything, Anytime</h1>
                <p class="lead">Join thousands of students learning with our comprehensive courses and mock tests.</p>
                <a href="courses/" class="btn btn-light btn-lg mt-3">Explore Courses</a>
                <a href="quizzes/" class="btn btn-outline-light btn-lg mt-3 ms-2">Take Mock Tests</a>
            </div>
            <div class="col-lg-6">
                <img src="https://via.placeholder.com/600x400" alt="Learning" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- Featured Courses -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Featured Courses</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT c.*, cat.name as category_name 
                                FROM courses c 
                                JOIN categories cat ON c.category_id = cat.id 
                                WHERE c.is_featured = 1 
                                LIMIT 3");
            $featured = $stmt->fetchAll();
            
            foreach ($featured as $course):
            ?>
            <div class="col-md-4 mb-4">
                <div class="card course-card h-100">
                    <img src="<?php echo $course['image_url'] ?? 'https://via.placeholder.com/300x200'; ?>" 
                         class="card-img-top" alt="<?php echo sanitize($course['title']); ?>">
                    <div class="card-body">
                        <span class="badge bg-primary"><?php echo sanitize($course['category_name']); ?></span>
                        <h5 class="card-title mt-2"><?php echo sanitize($course['title']); ?></h5>
                        <p class="card-text"><?php echo substr(strip_tags($course['description']), 0, 100); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0">₹<?php echo number_format($course['price'], 2); ?></span>
                            <a href="courses/detail.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($featured)): ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No featured courses yet.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="courses/" class="btn btn-outline-primary">View All Courses</a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Browse by Category</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT * FROM categories LIMIT 4");
            $categories = $stmt->fetchAll();
            
            foreach ($categories as $cat):
            ?>
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-<?php echo $cat['icon'] ?? 'book'; ?> fa-3x text-primary mb-3"></i>
                        <h5 class="card-title"><?php echo sanitize($cat['name']); ?></h5>
                        <p class="card-text"><?php echo sanitize(substr($cat['description'], 0, 60)); ?></p>
                        <a href="courses/?category=<?php echo $cat['id']; ?>" class="btn btn-outline-primary">Explore</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>