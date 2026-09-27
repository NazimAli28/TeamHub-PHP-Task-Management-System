<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/db.php';
// Allow both admin and employee access
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
date_default_timezone_set('Asia/Karachi');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $task_id = intval($_GET['task_id']);

    // Get task status (admin can access any task, employee only their own)
    $stmt = $conn->prepare("SELECT is_paused, paused_duration FROM designer_points WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['error' => 'Task not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'is_paused' => $row['is_paused'],
        'paused_duration' => $row['paused_duration'] ?? 0
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
