<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
ob_start();

session_start();
include '../config/db.php';
include '../employee/includes/verify_employee.php';
date_default_timezone_set('Asia/Karachi');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = intval($_POST['task_id']);
    $completion_time = date('Y-m-d H:i:s');

    // Get user info
    $user_id = $_SESSION['user_id'];
    $user_query = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result()->fetch_assoc();
    $user_query->close();

    $employee_name = $user_result['name'] ?? $_SESSION['username'];

    // Get task details including pause_start_time and is_paused
    $stmt = $conn->prepare("SELECT assigned_time, allotted_time, paused_duration, pause_start_time, is_paused, order_no FROM designer_points WHERE id = ? AND designer_name = ?");
    $stmt->bind_param("is", $task_id, $employee_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['error' => 'Task not found or not assigned to you']);
        ob_end_flush();
        exit;
    }

    $assigned_time = new DateTime($row['assigned_time']);
    $allotted_time = $row['allotted_time'];
    $paused_duration_seconds = intval($row['paused_duration'] ?? 0);
    $pause_start_time_str = $row['pause_start_time'];
    $is_paused = intval($row['is_paused']);

    // If task is paused, use pause_start_time as completion time for calculation
    if ($is_paused) {
        if ($pause_start_time_str) {
            $completed_time = new DateTime($pause_start_time_str);
            $completion_time = $pause_start_time_str; // store pause start time as completion time
        } else {
            // pause_start_time missing, fallback to current completion_time minus paused_duration
            $completed_time = new DateTime($completion_time);
            // Subtract paused_duration seconds from completion_time
            $completed_time->modify("-{$paused_duration_seconds} seconds");
            $completion_time = $completed_time->format('Y-m-d H:i:s');
        }
    } else {
        $completed_time = new DateTime($completion_time);
    }

    // Calculate total time used in seconds
    $interval = $assigned_time->diff($completed_time);
    $total_seconds_used = ($interval->days * 24 * 60 * 60) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;

    // Subtract paused duration (in seconds) and convert to minutes with rounding
    $actual_seconds_used = max(0, $total_seconds_used - $paused_duration_seconds);
    $actual_minutes_used = round($actual_seconds_used / 60, 2);

    $allotted_points = $allotted_time / 10;
    $saved_or_delayed = $allotted_time - $actual_minutes_used;
    $gained_points = $allotted_points + round($saved_or_delayed * 0.1, 2);
    $gained_points = max(0, $gained_points);
    $gained_points = round($gained_points, 2);

    // Update task with completion details
    $update = $conn->prepare("UPDATE designer_points SET completion_time = ?, points_gained = ?, completed_time = ? WHERE id = ? AND designer_name = ?");
    $update->bind_param("sdiis", $completion_time, $gained_points, $actual_minutes_used, $task_id, $employee_name);

    if ($update->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Task completed successfully!',
            'completion_time' => date('d-m-Y h:i A', strtotime($completion_time)),
            'completed_time' => $actual_minutes_used,
            'points_gained' => $gained_points,
            'order_no' => $row['order_no']
        ]);
        ob_end_flush();
    } else {
        echo json_encode(['error' => 'Failed to complete task: ' . $update->error]);
        ob_end_flush();
    }
    $update->close();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    ob_end_flush();
}
