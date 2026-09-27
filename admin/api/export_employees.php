<?php
include '../../config/db.php';
include '../includes/verify_admin.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="employees_overview_export_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, [
    'Employee Name',
    'Total Tasks',
    'Completed Tasks',
    'Pending Tasks',
    'Total Allotted Points',
    'Total Gained Points',
    'Completion Percentage',
    'Last Completion Date'
]);

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$work_type = $_GET['work_type'] ?? '';

// Build where clause for date filtering
$date_where = '';
if ($start_date && $end_date) {
    $date_where = "AND DATE(dp.timestamp) BETWEEN '$start_date' AND '$end_date'";
} elseif ($start_date) {
    $date_where = "AND DATE(dp.timestamp) >= '$start_date'";
} elseif ($end_date) {
    $date_where = "AND DATE(dp.timestamp) <= '$end_date'";
}

// Build where clause for work type filtering
$work_where = '';
if ($work_type) {
    $work_where = "AND dp.work_type = '" . $conn->real_escape_string($work_type) . "'";
}

$query = "
    SELECT
        u.name,
        COUNT(dp.id) as total_tasks,
        SUM(CASE WHEN dp.completion_time IS NOT NULL THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN dp.completion_time IS NULL THEN 1 ELSE 0 END) as pending_tasks,
        COALESCE(SUM(dp.allotted_points), 0) as total_allotted_points,
        COALESCE(SUM(dp.points_gained + dp.bonus_points), 0) as total_gained_points,
        ROUND(
            CASE
                WHEN SUM(dp.allotted_points) > 0
                THEN (SUM(dp.points_gained + dp.bonus_points) / SUM(dp.allotted_points)) * 100
                ELSE 0
            END, 1
        ) as completion_percentage,
        MAX(dp.completion_time) as last_completion_date
    FROM users u
    LEFT JOIN designer_points dp ON u.name = dp.designer_name $date_where $work_where
    WHERE u.role = 'employee'
    GROUP BY u.id, u.name
    ORDER BY u.name ASC
";

$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['name'],
        $row['total_tasks'],
        $row['completed_tasks'],
        $row['pending_tasks'],
        $row['total_allotted_points'],
        $row['total_gained_points'],
        $row['completion_percentage'] . '%',
        $row['last_completion_date']
    ]);
}

fclose($output);
exit;
?>
