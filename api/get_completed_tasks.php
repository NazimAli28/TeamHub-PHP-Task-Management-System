<?php
session_start();
include '../config/db.php';
include '../admin/includes/verify_admin.php';
date_default_timezone_set('Asia/Karachi');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $designer_name = isset($_GET['designer']) ? trim($_GET['designer']) : '';

    if (empty($designer_name)) {
        echo json_encode(['success' => true, 'tasks' => []]);
        exit;
    }

    // Get completed tasks for the designer
    $stmt = $conn->prepare("
        SELECT id, order_no, work, completion_time, points_gained 
        FROM designer_points 
        WHERE designer_name = ? AND completion_time IS NOT NULL 
        ORDER BY completion_time DESC 
        LIMIT 50
    ");
    $stmt->bind_param("s", $designer_name);
    $stmt->execute();
    $result = $stmt->get_result();

    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = [
            'id' => $row['id'],
            'order_no' => $row['order_no'],
            'work' => $row['work'],
            'completion_time' => date('d-m-Y h:i A', strtotime($row['completion_time'])),
            'points_gained' => $row['points_gained']
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
