<?php
session_start();
include '../config/db.php';
include '../admin/includes/verify_admin.php';
date_default_timezone_set('Asia/Karachi');

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log raw POST data for debugging
error_log("add_bonus_points POST data: " . print_r($_POST, true), 3, __DIR__ . "/error_log.txt");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designer_name = trim($_POST['designer_name']);
    $bonus_points = floatval($_POST['bonus_points']);
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : null;
    $reason = trim($_POST['reason'] ?? '');
    
    // Validate inputs
    if (empty($designer_name)) {
        echo json_encode(['error' => 'Designer name is required']);
        exit;
    }

    if ($bonus_points <= 0) {
        echo json_encode(['error' => 'Bonus points must be greater than 0']);
        exit;
    }

    try {
        $conn->begin_transaction();

        if ($task_id) {
            // Add bonus points to a specific task
            $stmt = $conn->prepare("UPDATE designer_points SET bonus_points = bonus_points + ? WHERE id = ? AND designer_name = ?");
            $stmt->bind_param("dis", $bonus_points, $task_id, $designer_name);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows === 0) {
                    throw new Exception("Task not found or not assigned to this designer");
                }
                
                // Log the bonus addition
                $log_stmt = $conn->prepare("INSERT INTO bonus_logs (designer_name, task_id, bonus_points, reason, added_by) VALUES (?, ?, ?, ?, ?)");
                $added_by = $_SESSION['username'] ?? 'Admin';
                $log_stmt->bind_param("sidis", $designer_name, $task_id, $bonus_points, $reason, $added_by);
                $log_stmt->execute();
                $log_stmt->close();
                
                $conn->commit();
                // Set session flag to show popup on progress display
                // Fetch profile picture URL
                $pic_stmt = $conn->prepare("SELECT profile_picture FROM users WHERE name = ?");
                $pic_stmt->bind_param("s", $designer_name);
                $pic_stmt->execute();
                $pic_result = $pic_stmt->get_result();
                $profile_picture = '../assets/images/default_profile.png';
                if ($pic_row = $pic_result->fetch_assoc()) {
                    if (!empty($pic_row['profile_picture'])) {
                        $profile_picture = $pic_row['profile_picture'];
                    }
                }
                $pic_stmt->close();

                $_SESSION['bonus_added'] = [
                    'designer' => $designer_name,
                    'points' => $bonus_points,
                    'type' => 'task',
                    'picture' => $profile_picture,
                    'reason' => $reason
                ];
                echo json_encode([
                    'success' => true,
                    'message' => "✅ $bonus_points bonus points added to task successfully!"
                ]);
            } else {
                throw new Exception("Failed to update task bonus points: " . $stmt->error);
            }
            $stmt->close();
        } else {
            // Add general bonus points (not tied to a specific task)
            $stmt = $conn->prepare("INSERT INTO designer_points (designer_name, bonus_points, assigned_time, work_type) VALUES (?, ?, NOW(), 'Bonus')");
            $stmt->bind_param("sd", $designer_name, $bonus_points);
            
            if ($stmt->execute()) {
                $new_task_id = $conn->insert_id;
                
                // Log the bonus addition
                $log_stmt = $conn->prepare("INSERT INTO bonus_logs (designer_name, task_id, bonus_points, reason, added_by) VALUES (?, ?, ?, ?, ?)");
                $added_by = $_SESSION['username'] ?? 'Admin';
                $log_stmt->bind_param("sidis", $designer_name, $new_task_id, $bonus_points, $reason, $added_by);
                $log_stmt->execute();
                $log_stmt->close();
                
                $conn->commit();
                // Set session flag to show popup on progress display
                // Fetch profile picture URL
                $pic_stmt = $conn->prepare("SELECT profile_picture FROM users WHERE name = ?");
                $pic_stmt->bind_param("s", $designer_name);
                $pic_stmt->execute();
                $pic_result = $pic_stmt->get_result();
                $profile_picture = '../assets/images/default_profile.png';
                if ($pic_row = $pic_result->fetch_assoc()) {
                    if (!empty($pic_row['profile_picture'])) {
                        $profile_picture = $pic_row['profile_picture'];
                    }
                }
                $pic_stmt->close();

                $_SESSION['bonus_added'] = [
                    'designer' => $designer_name,
                    'points' => $bonus_points,
                    'type' => 'general',
                    'picture' => $profile_picture,
                    'reason' => $reason
                ];
                echo json_encode([
                    'success' => true,
                    'message' => "✅ $bonus_points bonus points added successfully!"
                ]);
            } else {
                throw new Exception("Failed to add bonus points: " . $stmt->error);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("add_bonus_points error: " . $e->getMessage(), 3, __DIR__ . "/error_log.txt");
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
