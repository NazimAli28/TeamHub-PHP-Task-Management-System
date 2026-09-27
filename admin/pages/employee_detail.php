<?php
include '../../config/db.php';
include '../includes/verify_admin.php';

// Get employee name from URL
$employee_name = $_GET['designer'] ?? '';
if (!$employee_name) {
    header('Location: employees_overview.php');
    exit;
}

// Set default date range to last 7 days if not specified
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
if (empty($start_date) && empty($end_date)) {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = date('Y-m-d');
}

// Prepare date filter SQL
$date_filter_sql = "AND DATE(assigned_time) BETWEEN '$start_date' AND '$end_date'";

// Query 1: Number of tasks assigned per day with total allotted points
$tasks_assigned_query = "
    SELECT DATE(assigned_time) as date, COUNT(*) as task_count, SUM(allotted_points) as total_points
    FROM designer_points
    WHERE designer_name = '" . $conn->real_escape_string($employee_name) . "'
    $date_filter_sql
    GROUP BY DATE(assigned_time)
    ORDER BY DATE(assigned_time) ASC
";
$tasks_assigned_result = $conn->query($tasks_assigned_query);
$tasks_assigned_data = [];
while ($row = $tasks_assigned_result->fetch_assoc()) {
    $tasks_assigned_data[] = $row;
}

// Query 2: Work type distribution
$work_type_query = "
    SELECT work_type, COUNT(*) as count
    FROM designer_points
    WHERE designer_name = '" . $conn->real_escape_string($employee_name) . "'
    $date_filter_sql
    GROUP BY work_type
";
$work_type_result = $conn->query($work_type_query);
$work_type_data = [];
while ($row = $work_type_result->fetch_assoc()) {
    $work_type_data[] = $row;
}

// Query 3: Daily total points assigned and points gained
$daily_points_query = "
    SELECT DATE(assigned_time) as date, 
           SUM(allotted_points) as total_assigned, 
           SUM(points_gained) as total_gained
    FROM designer_points
    WHERE designer_name = '" . $conn->real_escape_string($employee_name) . "'
    $date_filter_sql
    GROUP BY DATE(assigned_time)
    ORDER BY DATE(assigned_time) ASC
";
$daily_points_result = $conn->query($daily_points_query);
$daily_points_data = [];
while ($row = $daily_points_result->fetch_assoc()) {
    $daily_points_data[] = $row;
}

// Get employee info
$employee_query = "SELECT * FROM users WHERE name = '" . $conn->real_escape_string($employee_name) . "' AND role = 'employee'";
$employee_result = $conn->query($employee_query);
$employee = $employee_result->fetch_assoc();

if (!$employee) {
    header('Location: employees_overview.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png" />
    <title>Employee Details: <?= htmlspecialchars($employee_name) ?> - TeamHub</title>
    <link rel="stylesheet" href="../css/styles.css?v=1.2">
    <link rel="stylesheet" href="../css/emp_details.css?v=1.2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/backToTop.js"></script>
</head>
<body>
<?php include '../includes/navbar_admin.php'; ?>

<div class="records-container">
    <div class="page-header">
        <h1 class="page-title">
            <img src="<?= htmlspecialchars($employee['profile_picture'] ?: '../../assets/images/default_profile.png') ?>"
                 alt="Profile" class="employee-detail-avatar">
            📊 <?= htmlspecialchars($employee_name) ?> - Task Details
        </h1>
        <a href="employees_overview.php" class="back-link">← Back to Employee Overview</a>
    </div>
    
    <!-- Date Range Filter -->
    <div class="date-filter">
        <h3>📅 Date Range Filter</h3>
        <form method="GET" class="filter-form">
            <input type="hidden" name="designer" value="<?= htmlspecialchars($employee_name) ?>">
            <div class="fields-row">
                <div class="form-group">
                    <label>Start Date:</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="form-group">
                    <label>End Date:</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                </div>
            </div>
            <div class="buttons-row">
                <button type="submit">Apply Filter</button>
                <a href="?designer=<?= urlencode($employee_name) ?>" class="clear-btn">Reset to Last 7 Days</a>
            </div>
        </form>

        <!-- Current Date Range Display -->
        <div class="current-range">
            <?php
            $display_start = $start_date ?: date('Y-m-d', strtotime('-7 days'));
            $display_end = $end_date ?: date('Y-m-d');

            // Format dates for display
            $formatted_start = date('M j, Y', strtotime($display_start));
            $formatted_end = date('M j, Y', strtotime($display_end));

            // Check if custom range is selected
            $is_custom_range = (!empty($_GET['start_date']) || !empty($_GET['end_date']));
            ?>
            <div class="range-display">
                <span class="range-label">Current Range:</span>
                <span class="range-dates">
                    <strong><?= $formatted_start ?> - <?= $formatted_end ?></strong>
                    <?php if ($is_custom_range): ?>
                        <span class="custom-indicator">(Custom)</span>
                    <?php else: ?>
                        <span class="default-indicator">(Last 7 Days)</span>
                    <?php endif; ?>
                </span>
                <?php if ($is_custom_range): ?>
                    <a href="?designer=<?= urlencode($employee_name) ?>" class="clear-range-btn" title="Clear custom date range">
                        ✕ Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Employee Stats Summary -->
    <div class="employee-stats">
        <?php
        // Build stats query with same filters as table
        $stats_where = ["designer_name = '" . $conn->real_escape_string($employee_name) . "'"];

        if (!empty($_GET['order_no'])) {
            $order = $conn->real_escape_string($_GET['order_no']);
            $stats_where[] = "order_no LIKE '%$order%'";
        }
        if (!empty($_GET['work_type'])) {
            $work_type = $conn->real_escape_string($_GET['work_type']);
            $stats_where[] = "work_type = '$work_type'";
        }
        if (!empty($_GET['work'])) {
            $work = $conn->real_escape_string($_GET['work']);
            $stats_where[] = "work LIKE '%$work%'";
        }
        if (!empty($_GET['status'])) {
            if ($_GET['status'] == 'completed') {
                $stats_where[] = "completion_time IS NOT NULL";
            } elseif ($_GET['status'] == 'pending') {
                $stats_where[] = "completion_time IS NULL";
            }
        }

        // Handle date range filtering for stats
        if (!empty($start_date) || !empty($end_date)) {
            if (!empty($start_date) && !empty($end_date)) {
                $stats_where[] = "DATE(assigned_time) BETWEEN '$start_date' AND '$end_date'";
            } elseif (!empty($start_date)) {
                $stats_where[] = "DATE(assigned_time) >= '$start_date'";
            } elseif (!empty($end_date)) {
                $stats_where[] = "DATE(assigned_time) <= '$end_date'";
            }
        }

        $stats_where_sql = count($stats_where) ? 'WHERE ' . implode(' AND ', $stats_where) : '';

        // Get filtered employee stats
        $stats_query = "
            SELECT
                COUNT(*) as total_tasks,
                SUM(CASE WHEN completion_time IS NOT NULL THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN completion_time IS NULL THEN 1 ELSE 0 END) as pending_tasks,
                COALESCE(SUM(allotted_points), 0) as total_allotted,
                COALESCE(SUM(points_gained + bonus_points), 0) as total_gained,
                ROUND(
                    CASE
                        WHEN SUM(allotted_points) > 0
                        THEN (SUM(points_gained + bonus_points) / SUM(allotted_points)) * 100
                        ELSE 0
                    END, 1
                ) as completion_rate,
                COUNT(DISTINCT DATE(assigned_time)) as working_days,
                COALESCE(SUM(allotted_time), 0) as total_allotted_time,
                COALESCE(SUM(CASE WHEN completion_time IS NOT NULL THEN completed_time ELSE 0 END), 0) as total_completed_time,
                COALESCE(SUM(paused_duration / 60), 0) as total_break_time
            FROM designer_points
            $stats_where_sql
        ";
        $stats_result = $conn->query($stats_query);
        $stats = $stats_result->fetch_assoc();

        // Set default date range to last 7 days if not specified
        if (empty($start_date) && empty($end_date)) {
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $end_date = date('Y-m-d');
        }

        // Chart 1: Number of tasks assigned with points per day
        $tasks_chart_query = "
            SELECT
                DATE(assigned_time) as date,
                COUNT(*) as task_count,
                COALESCE(SUM(allotted_points), 0) as allotted_points,
                COALESCE(SUM(points_gained + bonus_points), 0) as gained_points
            FROM designer_points
            WHERE designer_name = '" . $conn->real_escape_string($employee_name) . "'
            AND DATE(assigned_time) BETWEEN '$start_date' AND '$end_date'
            GROUP BY DATE(assigned_time)
            ORDER BY DATE(assigned_time)
        ";
        $tasks_chart_result = $conn->query($tasks_chart_query);
        $tasks_chart_data = [];
        while ($row = $tasks_chart_result->fetch_assoc()) {
            $tasks_chart_data[] = $row;
        }

        // Chart 2: Work type distribution
        $work_type_query = "
            SELECT
                work_type,
                COUNT(*) as count,
                COALESCE(SUM(allotted_points), 0) as total_points
            FROM designer_points
            WHERE designer_name = '" . $conn->real_escape_string($employee_name) . "'
            AND DATE(assigned_time) BETWEEN '$start_date' AND '$end_date'
            GROUP BY work_type
            ORDER BY count DESC
        ";
        $work_type_result = $conn->query($work_type_query);
        $work_type_data = [];
        while ($row = $work_type_result->fetch_assoc()) {
            $work_type_data[] = $row;
        }

        // Chart 3: Work distribution by work (pie chart)
        $work_query = "
            SELECT work, COUNT(*) as count
            FROM designer_points
            WHERE designer_name = '" . $conn->real_escape_string($employee_name) . "'
            AND DATE(assigned_time) BETWEEN '$start_date' AND '$end_date'
            GROUP BY work
            ORDER BY count DESC
        ";
        $work_result = $conn->query($work_query);
        $work_data = [];
        while ($row = $work_result->fetch_assoc()) {
            $work_data[] = $row;
        }

        // Chart 4: Allotted Time vs Completed Time per day
        $time_comparison_query = "
            SELECT
                DATE(assigned_time) as date,
                COUNT(*) as task_count,
                COALESCE(SUM(allotted_time), 0) as allotted_time,
                COALESCE(SUM(completed_time), 0) as completed_time,
                COALESCE(SUM(paused_duration / 60), 0) as break_time
            FROM designer_points
            WHERE designer_name = '" . $conn->real_escape_string($employee_name) . "'
            AND DATE(assigned_time) BETWEEN '$start_date' AND '$end_date'
            AND completion_time IS NOT NULL
            GROUP BY DATE(assigned_time)
            ORDER BY DATE(assigned_time) ASC
        ";
        $time_comparison_result = $conn->query($time_comparison_query);
        $time_comparison_data = [];
        while ($row = $time_comparison_result->fetch_assoc()) {
            $time_comparison_data[] = $row;
        }
        ?>
        <div class="stat-card">
            <h3>Total Tasks Completed</h3>
            <div class="stat-value completed"><?= $stats['completed_tasks'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Avg. Tasks Completed per Day</h3>
            <div class="stat-value">
                <?php
                $avg_tasks = $stats['working_days'] > 0 ? $stats['completed_tasks'] / $stats['working_days'] : 0;
                echo number_format($avg_tasks, 2);
                ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>Total Allotted Time</h3>
            <div class="stat-value"><?= number_format($stats['total_allotted_time'] / 60, 2) ?> hrs</div>
        </div>
        <div class="stat-card">
            <h3>Avg. Allotted Time per Day</h3>
            <div class="stat-value">
                <?php
                $avg_allotted = $stats['working_days'] > 0 ? ($stats['total_allotted_time'] / $stats['working_days']) / 60 : 0;
                echo number_format($avg_allotted, 2) . ' hrs';
                ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>Total Completed Time</h3>
            <div class="stat-value"><?= number_format($stats['total_completed_time'] / 60, 2) ?> hrs</div>
        </div>
        <div class="stat-card">
            <h3>Avg. Completed Time per Day</h3>
            <div class="stat-value">
                <?php
                $avg_completed = $stats['working_days'] > 0 ? ($stats['total_completed_time'] / $stats['working_days']) / 60 : 0;
                echo number_format($avg_completed, 2) . ' hrs';
                ?>
            </div>
        </div>
        <div class="stat-card">
            <h3>Total Break Time</h3>
            <div class="stat-value"><?= number_format($stats['total_break_time'] / 60, 2) ?> hrs</div>
        </div>
        <div class="stat-card">
            <h3>Avg. Break Time per Day</h3>
            <div class="stat-value">
                <?php
                $avg_break = $stats['working_days'] > 0 ? ($stats['total_break_time'] / $stats['working_days']) / 60 : 0;
                echo number_format($avg_break, 2) . ' hrs';
                ?>
            </div>
        </div>
    </div>


    <h2>Task Analytics Charts</h2>

    <!-- Chart 1: Number of tasks assigned with points -->
    <div class="chart-row">
        <div class="chart-container">
            <h4>📋 Tasks Assigned with Points</h4>
            <canvas id="tasksAssignedChart"></canvas>
        </div>
        <div class="chart-container">
            <h4>⏱️ Allotted Time vs Completed Time (Hours:Minutes)</h4>
            <canvas id="timeComparisonChart"></canvas>
        </div>
    </div>

    <!-- Chart 3: Daily points assigned and gained -->
    <div class="chart-row">
        <div class="chart-container">
            <h4>🥧 Work Distribution</h4>
            <canvas id="dailyPointsChart"></canvas>
        </div>
        <div class="chart-container">
            <h4>🥧 Work Type Distribution</h4>
            <canvas id="workTypeChart"></canvas>
        </div>
    </div>

</div>

<button id="backToTopBtn" title="Go to top">↑</button>

<script>
        // Chart data from PHP
        const tasksChartData = <?= json_encode($tasks_chart_data) ?>;
        const workTypeData = <?= json_encode($work_type_data) ?>;
        const dailyPointsData = <?= json_encode($daily_points_data) ?>;
        const timeComparisonData = <?= json_encode($time_comparison_data) ?>;

        // Extract data for charts
        const tasksChartLabels = tasksChartData.map(item => item.date);
        const tasksChartCounts = tasksChartData.map(item => item.task_count);
        const allottedPoints = tasksChartData.map(item => item.allotted_points);
        const gainedPoints = tasksChartData.map(item => item.gained_points);

        const workTypeLabels = workTypeData.map(item => item.work_type);
        const workTypeCounts = workTypeData.map(item => item.count);

        const dailyPointsLabels = dailyPointsData.map(item => item.date);
        const dailyPointsAssigned = dailyPointsData.map(item => item.points_assigned);
        const dailyPointsGained = dailyPointsData.map(item => item.points_gained);

        const timeComparisonLabels = timeComparisonData.map(item => item.date);
        const timeComparisonTaskCounts = timeComparisonData.map(item => item.task_count);
        const allottedTimes = timeComparisonData.map(item => item.allotted_time);
        const completedTimes = timeComparisonData.map(item => item.completed_time);
        const breakTimes = timeComparisonData.map(item => item.break_time);

        // Function to get chart colors based on dark mode
        function getChartColors() {
            const isDark = document.body.classList.contains('dark');
            if (isDark) {
                return {
                    assigned: '#00bfff', // Deep sky blue
                    gained: '#32cd32',   // Lime green
                    break: '#ffa500',    // Orange
                    tasks: 'rgba(255, 105, 180, 0.5)'     // Hot pink semi-transparent for bars to allow lines on top
                };
            } else {
                return {
                    assigned: 'blue',
                    gained: 'green',
                    break: 'orange',
                    tasks: 'rgba(128, 0, 128, 0.5)'     // Purple semi-transparent for bars to allow lines on top
                };
            }
        }

        // Function to get chart options based on dark mode
        function getChartOptions() {
            const isDark = document.body.classList.contains('dark');
            const textColor = isDark ? '#ffffff' : '#333333';
            const gridColor = isDark ? '#666666' : '#cccccc';
            return {
                plugins: {
                    legend: {
                        labels: {
                            color: textColor
                        }
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Time')) {
                                    let value = context.parsed.y;
                                    if (value === null || value === undefined) return label + ': 0:00.00';
                                    let h = Math.floor(value / 60);
                                    let m = (value % 60).toFixed(2);
                                    let [int, dec] = m.split('.');
                                    let formatted = h + ':' + int.padStart(2, '0') + '.' + dec;
                                    return label + ': ' + formatted;
                                }
                                return label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    y: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    }
                }
            };
        }

        const colors = getChartColors();
        const options = getChartOptions();

        // Chart 1 Tasks Assigned with Points
        const tasksAssignedChart = new Chart(document.getElementById('tasksAssignedChart'), {
            type: 'bar',
            data: {
                labels: tasksChartLabels,
                datasets: [{
                    label: 'Tasks Assigned',
                    data: tasksChartCounts,
                    backgroundColor: colors.tasks,
                    type: 'bar',
                    yAxisID: 'y',
                    order: 10
                }, {
                    label: 'Allotted Points',
                    data: allottedPoints,
                    borderColor: colors.assigned,
                    backgroundColor: 'rgba(0,0,0,0)',
                    type: 'line',
                    fill: false,
                    yAxisID: 'y1',
                    order: 20
                }, {
                    label: 'Points Gained',
                    data: gainedPoints.map(p => parseFloat(p).toFixed(2)),
                    borderColor: colors.gained,
                    backgroundColor: 'rgba(0,0,0,0)',
                    type: 'line',
                    fill: false,
                    yAxisID: 'y1',
                    order: 30
                }]
            },
            options: {
                ...options,
                plugins: {
                    legend: {
                        labels: {
                            color: options.plugins.legend.labels.color,
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Time')) {
                                    let value = context.parsed.y;
                                    if (value === null || value === undefined) return label + ': 0:00.00';
                                    let h = Math.floor(value / 60);
                                    let m = (value % 60).toFixed(2);
                                    let [int, dec] = m.split('.');
                                    let formatted = h + ':' + int.padStart(2, '0') + '.' + dec;
                                    return label + ': ' + formatted;
                                }
                                return label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    ...options.scales,
                    x: {
                        ...options.scales.x,
                        ticks: {
                            ...options.scales.x.ticks,
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            color: options.scales.y.ticks.color,
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: options.scales.y.grid.color
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            color: options.scales.y.ticks.color,
                            font: {
                                weight: 'bold'
                            },
                            callback: function(value) {
                                if (value === null || value === undefined) return '0:00.00';
                                let h = Math.floor(value / 60);
                                let m = (value % 60).toFixed(2);
                                let [int, dec] = m.split('.');
                                return h + ':' + int.padStart(2, '0') + '.' + dec;
                            }
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });

        // Chart 2 Allotted Time vs Completed Time (Minutes)
        const timeComparisonChart = new Chart(document.getElementById('timeComparisonChart'), {
            type: 'bar',
            data: {
                labels: timeComparisonLabels,
                datasets: [{
                    label: 'Number of Tasks',
                    data: timeComparisonTaskCounts,
                    backgroundColor: colors.tasks,
                    type: 'bar',
                    yAxisID: 'y',
                    order: 10
                }, {
                    label: 'Allotted Time (h:mm)',
                    data: allottedTimes,
                    borderColor: colors.assigned,
                    backgroundColor: 'rgba(0,0,0,0)',
                    type: 'line',
                    fill: false,
                    yAxisID: 'y1',
                    order: 20
                }, {
                    label: 'Completed Time (h:mm)',
                    data: completedTimes,
                    borderColor: colors.gained,
                    backgroundColor: 'rgba(0,0,0,0)',
                    type: 'line',
                    fill: false,
                    yAxisID: 'y1',
                    order: 30
                }, {
                    label: 'Break Time (h:mm)',
                    data: breakTimes,
                    borderColor: colors.break,
                    backgroundColor: 'rgba(0,0,0,0)',
                    type: 'line',
                    fill: false,
                    yAxisID: 'y1',
                    order: 40
                }]
            },
            options: {
                ...options,
                plugins: {
                    legend: {
                        labels: {
                            color: options.plugins.legend.labels.color,
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Time')) {
                                    let value = context.parsed.y;
                                    if (value === null || value === undefined) return label + ': 0:00.00';
                                    let h = Math.floor(value / 60);
                                    let m = (value % 60).toFixed(2);
                                    let [int, dec] = m.split('.');
                                    let formatted = h + ':' + int.padStart(2, '0') + '.' + dec;
                                    return label + ': ' + formatted;
                                }
                                return label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    ...options.scales,
                    x: {
                        ...options.scales.x,
                        ticks: {
                            ...options.scales.x.ticks,
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            color: options.scales.y.ticks.color,
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: options.scales.y.grid.color
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            color: options.scales.y.ticks.color,
                            font: {
                                weight: 'bold'
                            },
                            callback: function(value) {
                                let h = Math.floor(value / 60);
                                let m = (value % 60).toFixed(2);
                                let [int, dec] = m.split('.');
                                return h + ':' + int.padStart(2, '0') + '.' + dec;
                            }
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });

        // Chart 3 Work Distribution (Pie Chart)
        const workData = <?= json_encode(array_column($work_data, 'count')) ?>;
        const workLabels = <?= json_encode(array_column($work_data, 'work')) ?>;
        const dailyPointsChart = new Chart(document.getElementById('dailyPointsChart'), {
            type: 'pie',
            data: {
                labels: workLabels,
                datasets: [{
                    data: workData,
                    backgroundColor: [
                        colors.assigned,
                        colors.gained,
                        colors.tasks,
                        '#ff6384',
                        '#36a2eb',
                        '#ffce56',
                        '#4bc0c0',
                        '#9966ff',
                        '#c9cbcf'
                    ]
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: options.plugins.legend.labels.color
                        }
                    }
                }
            }
        });

        // Chart 4 Work Type Distribution
        const workTypeChart = new Chart(document.getElementById('workTypeChart'), {
            type: 'pie',
            data: {
                labels: workTypeLabels,
                datasets: [{
                    data: workTypeCounts,
                    backgroundColor: [
                        colors.assigned,
                        colors.gained,
                        colors.tasks,
                        '#ff6384',
                        '#36a2eb'
                    ]
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: options.plugins.legend.labels.color
                        }
                    }
                }
            }
        });

        // Function to update chart colors and options
        function updateCharts() {
            const newColors = getChartColors();
            const newOptions = getChartOptions();

            // Update new charts
            tasksAssignedChart.data.datasets[0].backgroundColor = newColors.tasks;
            tasksAssignedChart.data.datasets[1].borderColor = newColors.assigned;
            tasksAssignedChart.options = {
                ...newOptions,
                scales: {
                    ...newOptions.scales,
                    y: {
                        ...newOptions.scales.y,
                        ticks: {
                            color: newOptions.scales.y.ticks.color
                        },
                        grid: {
                            color: newOptions.scales.y.grid.color
                        }
                    },
                    y1: {
                        ...newOptions.scales.y,
                        ticks: {
                            color: newOptions.scales.y.ticks.color
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            };
            tasksAssignedChart.update();

            workTypeChart.data.datasets[0].backgroundColor = [
                newColors.assigned,
                newColors.gained,
                newColors.tasks,
                '#ff6384',
                '#36a2eb'
            ];
            workTypeChart.options.plugins.legend.labels.color = newOptions.plugins.legend.labels.color;
            workTypeChart.update();

            dailyPointsChart.options.plugins.legend.labels.color = newOptions.plugins.legend.labels.color;
            dailyPointsChart.update();

            timeComparisonChart.data.datasets[0].backgroundColor = newColors.tasks;
            timeComparisonChart.data.datasets[1].borderColor = newColors.assigned;
            timeComparisonChart.data.datasets[2].borderColor = newColors.gained;
            timeComparisonChart.data.datasets[3].borderColor = newColors.break;
            timeComparisonChart.options = {
                ...newOptions,
                plugins: {
                    legend: {
                        labels: {
                            color: newOptions.plugins.legend.labels.color,
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Time')) {
                                    let value = context.parsed.y;
                                    if (value === null || value === undefined) return label + ': 0:00.00';
                                    let h = Math.floor(value / 60);
                                    let m = (value % 60).toFixed(2);
                                    let [int, dec] = m.split('.');
                                    let formatted = h + ':' + int.padStart(2, '0') + '.' + dec;
                                    return label + ': ' + formatted;
                                }
                                return label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    ...newOptions.scales,
                    x: {
                        ...newOptions.scales.x,
                        ticks: {
                            ...newOptions.scales.x.ticks,
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            color: newOptions.scales.y.ticks.color,
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: newOptions.scales.y.grid.color
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            color: newOptions.scales.y.ticks.color,
                            font: {
                                weight: 'bold'
                            },
                            callback: function(value) {
                                if (value === null || value === undefined) return '0:00.00';
                                let h = Math.floor(value / 60);
                                let m = (value % 60).toFixed(2);
                                let [int, dec] = m.split('.');
                                return h + ':' + int.padStart(2, '0') + '.' + dec;
                            }
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            };
            timeComparisonChart.update();
        }

        // Observe changes to body's class attribute for dark mode toggle
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    updateCharts();
                }
            });
        });

        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['class']
        });
    </script>
<script src="../js/navbar.js"></script>
<script src="../../js/session_check.js"></script>

</body>
</html>
