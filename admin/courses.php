<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/admin_header.php';

$action = $_GET['action'] ?? 'list';
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $slug = createSlug($title);
    $category_id = intval($_POST['category_id']);
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $level = $_POST['level'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $image_url = trim($_POST['image_url']);
    
    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO courses (category_id, title, slug, description, image_url, price, level, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $title, $slug, $description, $image_url, $price, $level, $is_featured]);
        $_SESSION['success'] = "Course added successfully!";
        header("Location: courses.php");
        exit();
    } elseif ($action == 'edit' && $course_id > 0) {
        $stmt = $pdo->prepare("UPDATE courses SET category_id = ?, title = ?, slug = ?, description = ?, image_url = ?, price = ?, level = ?, is_featured = ? WHERE id = ?");
        $stmt->execute([$category_id, $title, $slug, $description, $image_url, $price, $level, $is_featured, $course_id]);
        $_SESSION['success'] = "Course updated successfully!";
        header("Location: courses.php");
        exit();
    }
}

// Handle delete
if ($action == 'delete' && $course_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $_SESSION['success'] = "Course deleted successfully!";
    header("Location: courses.php");
    exit();
}

// Get course for editing
$course = null;
if ($action == 'edit' && $course_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
}

// Get all courses
$courses = $pdo->query("SELECT c.*, cat.name as category_name FROM courses c JOIN categories cat ON c.category_id = cat.id ORDER BY c.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Course Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal" onclick="resetCourseForm()">
        <i class="fas fa-plus me-2"></i>Add New Course
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (count($courses) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Level</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td>
                                <?php if ($c['image_url']): ?>
                                    <img src="<?php echo $c['image_url']; ?>" width="50" height="50" style="object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 5px;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                             </td>
                            <td><?php echo sanitize($c['title']); ?></td>
                            <td><?php echo sanitize($c['category_name']); ?></td>
                            <td>₹<?php echo number_format($c['price'], 2); ?></td>
                            <td><span class="badge bg-info"><?php echo ucfirst($c['level']); ?></span></td>
                            <td><?php echo $c['is_featured'] ? '<i class="fas fa-star text-warning"></i>' : '-'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editCourse(<?php echo htmlspecialchars(json_encode($c)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="lessons.php?course_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-book"></i>
                                </a>
                                <a href="courses.php?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this course?')">
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
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <p>No courses yet. Click "Add New Course" to get started.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Add New Course</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="courseForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course Title *</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (₹)</label>
                            <input type="number" name="price" id="price" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Level</label>
                            <select name="level" id="level" class="form-select">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="text" name="image_url" id="image_url" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input" value="1">
                            <label class="form-check-label">Feature this course on homepage</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetCourseForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerHTML = 'Add New Course';
    document.getElementById('courseForm').action = 'courses.php?action=add';
    document.getElementById('title').value = '';
    document.getElementById('category_id').value = '';
    document.getElementById('description').value = '';
    document.getElementById('price').value = '0';
    document.getElementById('level').value = 'beginner';
    document.getElementById('image_url').value = '';
    document.getElementById('is_featured').checked = false;
}

function editCourse(course) {
    resetCourseForm();
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerHTML = 'Edit Course';
    document.getElementById('courseForm').action = 'courses.php?action=edit&id=' + course.id;
    document.getElementById('title').value = course.title;
    document.getElementById('category_id').value = course.category_id;
    document.getElementById('description').value = course.description || '';
    document.getElementById('price').value = course.price;
    document.getElementById('level').value = course.level;
    document.getElementById('image_url').value = course.image_url || '';
    document.getElementById('is_featured').checked = course.is_featured == 1;
    
    new bootstrap.Modal(document.getElementById('courseModal')).show();
}
</script>

<?php include '../includes/admin_footer.php'; ?>