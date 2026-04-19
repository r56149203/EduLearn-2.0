<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Check for errors
    if ($fileError !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload failed']);
        exit();
    }
    
    // Check file size
    if ($fileSize > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'error' => 'File too large. Max 5MB']);
        exit();
    }
    
    // Check file type
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit();
    }
    
    // Generate unique filename
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $uploadPath = '../uploads/' . $newFileName;
    
    // Create uploads directory if not exists
    if (!file_exists('../uploads')) {
        mkdir('../uploads', 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        echo json_encode([
            'success' => true,
            'url' => SITE_URL . 'uploads/' . $newFileName
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
}
?>