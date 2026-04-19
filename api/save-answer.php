<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Allow POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['quiz_id']) || !isset($data['answers'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit();
}

$quiz_id = intval($data['quiz_id']);

// Initialize session array for this quiz if not exists
if (!isset($_SESSION['quiz_answers'])) {
    $_SESSION['quiz_answers'] = [];
}

if (!isset($_SESSION['quiz_answers'][$quiz_id])) {
    $_SESSION['quiz_answers'][$quiz_id] = [];
}

// Merge new answers with existing ones
$updated_count = 0;
foreach ($data['answers'] as $key => $value) {
    if (strpos($key, 'answer_') === 0) {
        // Only update if value is not empty, or allow clearing
        $_SESSION['quiz_answers'][$quiz_id][$key] = $value;
        $updated_count++;
    }
}

// Remove answers that are empty (optional - keeps session clean)
foreach ($_SESSION['quiz_answers'][$quiz_id] as $key => $value) {
    if (empty($value)) {
        unset($_SESSION['quiz_answers'][$quiz_id][$key]);
    }
}

echo json_encode([
    'status' => 'success', 
    'saved' => $updated_count,
    'total_saved' => count($_SESSION['quiz_answers'][$quiz_id])
]);
?>