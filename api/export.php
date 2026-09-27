<?php
include '../admin/includes/verify_admin.php';
include '../config/db.php';

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=designer_records.csv");

$output = fopen("php://output", "w");

// Column headers matching records.php table
fputcsv($output, [
    'Designer',
    'Order No',
    'Work',
    'Work Type',
    'Amount',
    'Allotted Time',
    'Completed Time',
    'Allotted Points',
    'Gained Points',
    'Bonus Points',
    'Assigned Time',
    'Completion Time'
]);

// Build filter conditions (matching records.php logic)
$where = [];
if (!empty($_GET['designer'])) {
    $designer = $conn->real_escape_string($_GET['designer']);
    $where[] = "designer_name = '$designer'";
}
if (!empty($_GET['order_no'])) {
    $order = $conn->real_escape_string($_GET['order_no']);
    $where[] = "order_no LIKE '%$order%'";
}

// Handle date range filtering (matching records.php logic)
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$legacy_date = $_GET['date'] ?? '';

// Priority: new date range > legacy single date
if (!empty($start_date) || !empty($end_date)) {
    if (!empty($start_date) && !empty($end_date)) {
        // Both start and end dates provided
        $start_date = $conn->real_escape_string($start_date);
        $end_date = $conn->real_escape_string($end_date);
        $where[] = "DATE(timestamp) BETWEEN '$start_date' AND '$end_date'";
    } elseif (!empty($start_date)) {
        // Only start date provided - filter from start date to current date
        $start_date = $conn->real_escape_string($start_date);
        $where[] = "DATE(timestamp) >= '$start_date'";
    } elseif (!empty($end_date)) {
        // Only end date provided - filter from beginning to end date
        $end_date = $conn->real_escape_string($end_date);
        $where[] = "DATE(timestamp) <= '$end_date'";
    }
} elseif (!empty($legacy_date)) {
    // Legacy single date filter for backward compatibility
    $legacy_date = $conn->real_escape_string($legacy_date);
    $where[] = "DATE(timestamp) = '$legacy_date'";
}

$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "SELECT * FROM designer_points $where_sql ORDER BY timestamp DESC";
$result = $conn->query($query);

// Output each row to CSV
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['designer_name'],
        $row['order_no'],
        $row['work'],
        $row['work_type'],
        $row['amount'],
        $row['allotted_time'] . ' min',
        (!empty($row['completed_time']) ? $row['completed_time'] . ' min' : 'Pending'),
        $row['allotted_points'],
        (!empty($row['completion_time']) ? $row['points_gained'] : 'N/A'),
        $row['bonus_points'],
        (!empty($row['assigned_time']) ? date('d-m-Y h:i A', strtotime($row['assigned_time'])) : 'N/A'),
        (!empty($row['completion_time']) ? date('d-m-Y h:i A', strtotime($row['completion_time'])) : 'Pending')
    ]);
}

fclose($output);
exit;
