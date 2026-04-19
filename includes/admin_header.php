<?php
if (!isAdmin()) {
    header("Location: " . SITE_URL);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .admin-sidebar {
            background: #2c3e50;
            min-height: 100vh;
        }
        .admin-sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: #34495e;
            color: white;
        }
        .admin-sidebar .nav-link i {
            width: 25px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 admin-sidebar">
                <div class="p-3">
                    <h4 class="text-white text-center mb-4">
                        <i class="fas fa-crown me-2"></i>Admin Panel
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="courses.php">
                            <i class="fas fa-book me-2"></i>Courses
                        </a>
                        <a class="nav-link" href="quizzes.php">
                            <i class="fas fa-tasks me-2"></i>Quizzes
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                        <hr class="bg-light">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-globe me-2"></i>View Site
                        </a>
                        <a class="nav-link" href="<?php echo SITE_URL; ?>auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">