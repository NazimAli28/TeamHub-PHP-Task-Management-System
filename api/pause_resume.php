<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/db.php';
include '../admin/includes/verify_admin.php'; // Only admins can pause/resume
date_default_timezone_set('Asia/Karachi');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = intval($_POST['task_id']);
    $action = $_POST['action']; // 'pause' or 'resume'

    // Validate task exists and is not completed
    $stmt = $conn->prepare("SELECT id, is_paused, completion_time FROM designer_points WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Task not found']);
        exit;
    }

    $task = $result->fetch_assoc();
    $stmt->close();

    // Check if task is already completed
    if ($task['completion_time']) {
        echo json_encode(['error' => 'Cannot pause/resume completed tasks']);
        exit;
    }

    if ($action === 'pause') {
        // Check if already paused
        if ($task['is_paused']) {
            echo json_encode(['error' => 'Task is already paused']);
            exit;
        }

        // Pause the task
        $stmt = $conn->prepare("UPDATE designer_points SET is_paused = 1, pause_start_time = NOW() WHERE id = ?");
        $stmt->bind_param("i", $task_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Task paused successfully']);
        } else {
            echo json_encode(['error' => 'Failed to pause task: ' . $stmt->error]);
        }
        $stmt->close();
    } elseif ($action === 'resume') {
        // Check if not paused
        if (!$task['is_paused']) {
            echo json_encode(['error' => 'Task is not paused']);
            exit;
        }

        // Get pause start time
        $stmt = $conn->prepare("SELECT pause_start_time FROM designer_points WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $pause_start = $row['pause_start_time'];
        $current_time = date('Y-m-d H:i:s');

        // Calculate pause duration in seconds for better precision
        $pause_duration = 0;
        if ($pause_start) {
            $pause_start_obj = new DateTime($pause_start);
            $current_obj = new DateTime($current_time);
            $diff = $current_obj->getTimestamp() - $pause_start_obj->getTimestamp();
            $pause_duration = $diff;
        }

        // Get existing paused duration
        $stmt = $conn->prepare("SELECT paused_duration FROM designer_points WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $existing_paused = intval($row['paused_duration'] ?? 0);
        $total_paused = $existing_paused + $pause_duration;

        // Resume the task
        $stmt = $conn->prepare("UPDATE designer_points SET is_paused = 0, pause_start_time = NULL, paused_duration = ? WHERE id = ?");
        $stmt->bind_param("ii", $total_paused, $task_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Task resumed successfully']);
        } else {
            echo json_encode(['error' => 'Failed to resume task: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
