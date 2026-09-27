<?php
session_start();
include '../config/db.php';
include '../admin/includes/verify_admin.php';
date_default_timezone_set('Asia/Karachi');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designer_name = $_POST['designer_name'];
    $order_no = $_POST['order_no'];
    $work = $_POST['work'];
    $work_type = $_POST['work_type'];
    $amount = $_POST['amount'];
    $allotted_time = $_POST['allotted_time'];
    $assigned_time = date('Y-m-d H:i:s');

    // Convert time to points
    $allotted_points = $allotted_time / 10;

    $sql = "INSERT INTO designer_points 
            (designer_name, order_no, work, work_type, amount, allotted_time, allotted_points, assigned_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiids", $designer_name, $order_no, $work, $work_type, $amount, $allotted_time, $allotted_points, $assigned_time);

    if ($stmt->execute()) {
        $task_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Task assigned successfully!',
            'task_id' => $task_id,
            'designer_name' => $designer_name,
            'order_no' => $order_no,
            'work' => $work,
            'assigned_time' => $assigned_time
        ]);
    } else {
        echo json_encode(['error' => 'Failed to assign task: ' . $conn->error]);
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
