<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/admin_header.php';

$action = $_GET['action'] ?? 'list';
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle role update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['role'])) {
    $role = $_POST['role'];
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $user_id]);
    $_SESSION['success'] = "User role updated successfully!";
    header("Location: users.php");
    exit();
}

// Handle delete
if ($action == 'delete' && $user_id > 0 && $user_id != $_SESSION['user_id']) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['success'] = "User deleted successfully!";
    header("Location: users.php");
    exit();
}

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>User Management</h2>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Verified</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><strong><?php echo sanitize($user['name']); ?></strong></td>
                        <td><?php echo sanitize($user['email']); ?></td>
                        <td>
                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                            <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="role" value="<?php echo $user['role'] == 'admin' ? 'user' : 'admin'; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $user['role'] == 'admin' ? 'btn-danger' : 'btn-success'; ?>" 
                                            onclick="return confirm('Change user role?')">
                                        <?php echo ucfirst($user['role']); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                         </td>
                        <td>
                            <?php if ($user['email_verified']): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> Verified</span>
                            <?php else: ?>
                                <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                         </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Current</span>
                            <?php endif; ?>
                         </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>