<?php
include '../config/db.php';
include '../admin/includes/verify_admin.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/pages/records.php");
    exit;
}

// Validate and sanitize input data
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$designer_name = isset($_POST['designer_name']) ? trim($_POST['designer_name']) : '';
$order_no = isset($_POST['order_no']) ? trim($_POST['order_no']) : '';
$work = isset($_POST['work']) ? trim($_POST['work']) : '';
$work_type = isset($_POST['work_type']) ? trim($_POST['work_type']) : '';
$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
$allotted_time = isset($_POST['allotted_time']) ? intval($_POST['allotted_time']) : 0;
$allotted_points = isset($_POST['allotted_points']) ? floatval($_POST['allotted_points']) : 0;

// Validation
$errors = [];

if ($id <= 0) {
    $errors[] = "Invalid record ID.";
}

if (empty($designer_name)) {
    $errors[] = "Designer name is required.";
}

if (empty($order_no)) {
    $errors[] = "Order number is required.";
}

if (empty($work)) {
    $errors[] = "Work type is required.";
}

if (empty($work_type)) {
    $errors[] = "Work category is required.";
}

if ($amount < 0) {
    $errors[] = "Amount must be a positive number.";
}

if ($allotted_time < 0) {
    $errors[] = "Allotted time must be a positive number.";
}

if ($allotted_points < 0) {
    $errors[] = "Allotted points must be a positive number.";
}

// If there are validation errors, redirect back with error message
if (!empty($errors)) {
    $error_message = implode(" ", $errors);
    header("Location: ../admin/pages/edit_record.php?id=$id&error=" . urlencode($error_message));
    exit;
}

// Validate work type options
$valid_work_types = ["New Easy", "New Hard", "Feedback Easy", "Feedback Hard"];
if (!in_array($work_type, $valid_work_types)) {
    header("Location: ../admin/pages/edit_record.php?id=$id&error=" . urlencode("Invalid work type selected."));
    exit;
}

// Validate designer names
$designer_query = $conn->prepare("SELECT name FROM users WHERE role = 'employee'");
$designer_query->execute();
$designer_result = $designer_query->get_result();
$valid_designers = [];
while ($row = $designer_result->fetch_assoc()) {
    $valid_designers[] = $row['name'];
}
$designer_query->close();
if (!in_array($designer_name, $valid_designers)) {
    header("Location: ../admin/pages/edit_record.php?id=$id&error=" . urlencode("Invalid designer selected."));
    exit;
}

try {
    // Use prepared statement to prevent SQL injection
    $sql = "UPDATE designer_points SET 
                designer_name = ?,
                order_no = ?,
                work = ?,
                work_type = ?,
                amount = ?,
                allotted_time = ?,
                allotted_points = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param(
        "ssssiidi",
        $designer_name,
        $order_no,
        $work,
        $work_type,
        $amount,
        $allotted_time,
        $allotted_points,
        $id
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Success - redirect to records page
            header("Location: ../admin/pages/records.php?status=updated&id=$id");
        } else {
            // No rows were affected - record might not exist
            header("Location: ../admin/pages/edit_record.php?id=$id&error=" . urlencode("Record not found or no changes made."));
        }
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    // Log error (in production, log to file instead of showing to user)
    error_log("Update record error: " . $e->getMessage());

    // Redirect back with generic error message
    header("Location: ../admin/pages/edit_record.php?id=$id&error=" . urlencode("An error occurred while updating the record. Please try again."));
}

$conn->close();
