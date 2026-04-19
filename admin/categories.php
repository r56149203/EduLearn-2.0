<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/admin_header.php';

$action = $_GET['action'] ?? 'list';
$cat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $slug = createSlug($name);
    $description = $_POST['description'];
    $icon = $_POST['icon'];
    
    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $icon]);
        $_SESSION['success'] = "Category added successfully!";
        header("Location: categories.php");
        exit();
    } elseif ($action == 'edit' && $cat_id > 0) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $icon, $cat_id]);
        $_SESSION['success'] = "Category updated successfully!";
        header("Location: categories.php");
        exit();
    }
}

// Handle delete
if ($action == 'delete' && $cat_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$cat_id]);
    $_SESSION['success'] = "Category deleted successfully!";
    header("Location: categories.php");
    exit();
}

// Get category for editing
$category = null;
if ($action == 'edit' && $cat_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$cat_id]);
    $category = $stmt->fetch();
}

// Get all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Category Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetCategoryForm()">
        <i class="fas fa-plus me-2"></i>Add New Category
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (count($categories) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td><i class="fas fa-<?php echo $cat['icon'] ?? 'book'; ?> fa-lg text-primary"></i></td>
                            <td><strong><?php echo sanitize($cat['name']); ?></strong></td>
                            <td><code><?php echo $cat['slug']; ?></code></td>
                            <td><?php echo sanitize(substr($cat['description'], 0, 50)); ?>...</td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">
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
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <p>No categories yet. Click "Add New Category" to get started.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Add New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Icon (FontAwesome class)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-book" id="iconPreview"></i></span>
                            <input type="text" name="icon" id="icon" class="form-control" placeholder="book, code, chart-line, etc.">
                        </div>
                        <small class="text-muted">Enter FontAwesome icon name (e.g., "book", "code", "chart-line")</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetCategoryForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerHTML = 'Add New Category';
    document.getElementById('categoryForm').action = 'categories.php?action=add';
    document.getElementById('name').value = '';
    document.getElementById('icon').value = '';
    document.getElementById('description').value = '';
}

function editCategory(category) {
    resetCategoryForm();
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerHTML = 'Edit Category';
    document.getElementById('categoryForm').action = 'categories.php?action=edit&id=' + category.id;
    document.getElementById('name').value = category.name;
    document.getElementById('icon').value = category.icon || '';
    document.getElementById('description').value = category.description || '';
    
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

// Preview icon
document.getElementById('icon').addEventListener('input', function() {
    const iconName = this.value;
    if (iconName) {
        document.getElementById('iconPreview').className = 'fas fa-' + iconName;
    } else {
        document.getElementById('iconPreview').className = 'fas fa-book';
    }
});
</script>

<?php include '../includes/admin_footer.php'; ?>