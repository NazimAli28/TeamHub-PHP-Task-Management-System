<?php
include '../includes/verify_admin.php';
include '../../config/db.php';

// Handle sorting
$allowed_sorts = ['name', 'total_tasks', 'completed_tasks', 'completion_percentage'];
$sort_by = in_array($_GET['sort_by'] ?? '', $allowed_sorts, true) ? $_GET['sort_by'] : 'name';
$sort_order = strtoupper($_GET['sort_order'] ?? '') === 'DESC' ? 'DESC' : 'ASC';

// Get all employees with their stats
$query = "
    SELECT
        u.name,
        u.profile_picture,
        COUNT(dp.id) as total_tasks,
        SUM(CASE WHEN dp.completion_time IS NOT NULL THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN dp.completion_time IS NULL THEN 1 ELSE 0 END) as pending_tasks,
        COALESCE(SUM(dp.allotted_points), 0) as total_allotted_points,
        COALESCE(SUM(dp.points_gained + dp.bonus_points), 0) as total_gained_points,
        MAX(dp.completion_time) as last_completion_date,
        ROUND(
            CASE
                WHEN SUM(dp.allotted_points) > 0
                THEN (SUM(dp.points_gained + dp.bonus_points) / SUM(dp.allotted_points)) * 100
                ELSE 0
            END, 1
        ) as completion_percentage
    FROM users u
    LEFT JOIN designer_points dp ON u.name = dp.designer_name
    WHERE u.role = 'employee'
    GROUP BY u.id, u.name, u.profile_picture
    ORDER BY
        CASE WHEN '$sort_by' = 'name' AND '$sort_order' = 'ASC' THEN u.name END ASC,
        CASE WHEN '$sort_by' = 'name' AND '$sort_order' = 'DESC' THEN u.name END DESC,
        CASE WHEN '$sort_by' = 'total_tasks' AND '$sort_order' = 'ASC' THEN COUNT(dp.id) END ASC,
        CASE WHEN '$sort_by' = 'total_tasks' AND '$sort_order' = 'DESC' THEN COUNT(dp.id) END DESC,
        CASE WHEN '$sort_by' = 'completed_tasks' AND '$sort_order' = 'ASC' THEN SUM(CASE WHEN dp.completion_time IS NOT NULL THEN 1 ELSE 0 END) END ASC,
        CASE WHEN '$sort_by' = 'completed_tasks' AND '$sort_order' = 'DESC' THEN SUM(CASE WHEN dp.completion_time IS NOT NULL THEN 1 ELSE 0 END) END DESC,
        CASE WHEN '$sort_by' = 'completion_percentage' AND '$sort_order' = 'ASC' THEN ROUND(CASE WHEN SUM(dp.allotted_points) > 0 THEN (SUM(dp.points_gained + dp.bonus_points) / SUM(dp.allotted_points)) * 100 ELSE 0 END, 1) END ASC,
        CASE WHEN '$sort_by' = 'completion_percentage' AND '$sort_order' = 'DESC' THEN ROUND(CASE WHEN SUM(dp.allotted_points) > 0 THEN (SUM(dp.points_gained + dp.bonus_points) / SUM(dp.allotted_points)) * 100 ELSE 0 END, 1) END DESC,
        u.name ASC
";

$result = $conn->query($query);
$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png" />
    <title>Employee Overview - TeamHub</title>
    <link rel="stylesheet" href="../css/styles.css?v=1.2">
    <link rel="stylesheet" href="../css/emp_overview.css?v=1.2">
    <script src="../js/backToTop.js"></script>
</head>
<body>
<?php include '../includes/navbar_admin.php'; ?>

<div class="records-container">
    <div class="page-header">
        <h1 class="page-title">👥 Employee Overview</h1>
        <p class="page-subtitle">Comprehensive view of all employees' performance and progress</p>
    </div>

    <!-- Employee Summary Table -->
    <div class="table-section">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th onclick="sortTable('total_tasks')" class="sortable">
                            Total Tasks
                            <span class="sort-icon <?= $sort_by == 'total_tasks' ? ($sort_order == 'ASC' ? 'asc' : 'desc') : '' ?>"></span>
                        </th>
                        <th onclick="sortTable('completed_tasks')" class="sortable">
                            Completed
                            <span class="sort-icon <?= $sort_by == 'completed_tasks' ? ($sort_order == 'ASC' ? 'asc' : 'desc') : '' ?>"></span>
                        </th>
                        <th>Pending</th>
                        <th>Allotted Points</th>
                        <th>Gained Points</th>
                        <th onclick="sortTable('completion_percentage')" class="sortable">
                            Completion %
                            <span class="sort-icon <?= $sort_by == 'completion_percentage' ? ($sort_order == 'ASC' ? 'asc' : 'desc') : '' ?>"></span>
                        </th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td>
                                <div class="employee-info">
                                    <img src="<?= htmlspecialchars($employee['profile_picture'] ?: '../../assets/images/default_profile.png') ?>"
                                         alt="Profile" class="employee-avatar">
                                    <span class="employee-name"><?= htmlspecialchars($employee['name']) ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($employee['total_tasks']) ?></td>
                            <td class="completed-cell"><?= htmlspecialchars($employee['completed_tasks']) ?></td>
                            <td class="pending-cell"><?= htmlspecialchars($employee['pending_tasks']) ?></td>
                            <td><?= number_format($employee['total_allotted_points'], 2) ?></td>
                            <td><?= number_format($employee['total_gained_points'], 2) ?></td>
                            <td>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="width: <?= $employee['completion_percentage'] ?>%"></div>
                                    <span class="progress-text"><?= $employee['completion_percentage'] ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php if ($employee['last_completion_date']): ?>
                                    <?= date('d-m-Y', strtotime($employee['last_completion_date'])) ?>
                                <?php else: ?>
                                    <span class="no-activity">No activity</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="employee_detail.php?designer=<?= urlencode($employee['name']) ?>&<?= http_build_query($_GET) ?>"
                                   class="action-link view-link">👁️ View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Employee Management Button -->
    <div class="management-section">
        <a href="manage_employees.php" class="management-btn">⚙️ Manage Employees</a>
    </div>

</div>

<button id="backToTopBtn" title="Go to top">↑</button>

<script src="../js/navbar.js"></script>
<script>
function sortTable(column) {
    const currentSort = '<?= $sort_by ?>';
    const currentOrder = '<?= $sort_order ?>';
    let newOrder = 'ASC';

    if (currentSort === column && currentOrder === 'ASC') {
        newOrder = 'DESC';
    }

    const url = new URL(window.location);
    url.searchParams.set('sort_by', column);
    url.searchParams.set('sort_order', newOrder);
    window.location.href = url.toString();
}
</script>

</body>
</html>
