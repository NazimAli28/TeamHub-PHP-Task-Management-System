 <?php
session_start();
include '../includes/verify_employee.php';
include '../../config/db.php';

// Helper function to get stat data
function getStats($conn, $employee_name, $start_date, $end_date)
{
    $stmt = $conn->prepare("SELECT COUNT(*) as total_tasks,
                                   COUNT(CASE WHEN completion_time IS NOT NULL THEN 1 END) as total_completed_tasks,
                                   SUM(allotted_time)/10 as total_assigned_points,
                                   SUM(points_gained) as total_gained_points,
                                   SUM(bonus_points) as total_bonus_points,
                                   SUM(points_gained + bonus_points) as total_earned_points
                            FROM designer_points
                            WHERE designer_name = ?
                              AND DATE(assigned_time) BETWEEN ? AND ?");
    $stmt->bind_param("sss", $employee_name, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

// Today's and current month's date range
$today = date('Y-m-d');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$todayStats = getStats($conn, $employee_name, $today, $today);
$monthStats = getStats($conn, $employee_name, $month_start, $month_end);

// Weekly and monthly chart data
$chartWeeklyPoints = [];
$chartWeeklyTasks = [];
$chartMonthlyPoints = [];
$chartMonthlyTasks = [];

for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $stats = getStats($conn, $employee_name, $day, $day);
    $chartWeeklyPoints[] = ['date' => $day, 'assigned' => round($stats['total_assigned_points'] ?? 0, 2), 'gained' => round($stats['total_gained_points'] ?? 0, 2)];
    $chartWeeklyTasks[] = ['date' => $day, 'tasks' => $stats['total_completed_tasks'] ?? 0];
}

for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $stats = getStats($conn, $employee_name, $day, $day);
    $chartMonthlyPoints[] = ['date' => $day, 'assigned' => round($stats['total_assigned_points'] ?? 0, 2), 'gained' => round($stats['total_gained_points'] ?? 0, 2)];
    $chartMonthlyTasks[] = ['date' => $day, 'tasks' => $stats['total_completed_tasks'] ?? 0];
}

// Overdue and near-deadline alerts
$alert_stmt = $conn->prepare("SELECT order_no, assigned_time, allotted_time FROM designer_points WHERE designer_name = ? AND completion_time IS NULL");
$alert_stmt->bind_param("s", $employee_name);
$alert_stmt->execute();
$alerts = [];
$now = new DateTime();
$result = $alert_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $assigned = new DateTime($row['assigned_time']);
    $deadline = clone $assigned;
    $deadline->modify("+{$row['allotted_time']} minutes");

    $minutesLeft = ($deadline->getTimestamp() - $now->getTimestamp()) / 60;

    if ($minutesLeft <= 0) {
        $alerts[] = "⚠️ Task {$row['order_no']} is overdue!";
    } elseif ($minutesLeft <= 15) {
        $alerts[] = "⏰ Task {$row['order_no']} is nearing deadline!";
    }
}
$alert_stmt->close();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png" />
    <title>Employee Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/employee_styles.css">
    <link rel="stylesheet" href="../css/sidebar_stylesheet.css">
    <link rel="stylesheet" href="../css/employee_dashboard_styles.css">
</head>

<body>
    <div class="dashboard">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php foreach ($alerts as $alert): ?>
                <div class="alert"><?= htmlspecialchars($alert) ?></div>
            <?php endforeach; ?>

            <h2>Today's Stats</h2>
            <div class="stats-grid">
                <div class="stat-box">✅ Tasks Completed: <?= $todayStats['total_completed_tasks'] ?? 0 ?></div>
                <div class="stat-box">📌 Assigned Points: <?= round($todayStats['total_assigned_points'] ?? 0, 2) ?></div>
                <div class="stat-box">🏁 Gained Points: <?= round($todayStats['total_gained_points'] ?? 0, 2) ?></div>
                <div class="stat-box">⭐ Bonus Points: <?= round($todayStats['total_bonus_points'] ?? 0, 2) ?></div>
                <div class="stat-box">💰 Total Earned: <?= round($todayStats['total_earned_points'] ?? 0, 2) ?></div>
            </div>

            <h2>This Month's Stats</h2>
            <div class="stats-grid">
                <div class="stat-box">✅ Tasks Completed: <?= $monthStats['total_completed_tasks'] ?? 0 ?></div>
                <div class="stat-box">📌 Assigned Points: <?= round($monthStats['total_assigned_points'] ?? 0, 2) ?></div>
                <div class="stat-box">🏁 Gained Points: <?= round($monthStats['total_gained_points'] ?? 0, 2) ?></div>
                <div class="stat-box">⭐ Bonus Points: <?= round($monthStats['total_bonus_points'] ?? 0, 2) ?></div>
                <div class="stat-box">💰 Total Earned: <?= round($monthStats['total_earned_points'] ?? 0, 2) ?></div>
            </div>

            <h2>Weekly vs Monthly Charts</h2>
            <div class="chart-row">
                <div class="chart-container">
                    <h4>📊 Points (Last 7 Days)</h4>
                    <canvas id="weeklyPointsChart"></canvas>
                </div>
                <div class="chart-container">
                    <h4>✅ Tasks (Last 7 Days)</h4>
                    <canvas id="weeklyTasksChart"></canvas>
                </div>
            </div>
            <div class="chart-row">
                <div class="chart-container">
                    <h4>📊 Points (Last 30 Days)</h4>
                    <canvas id="monthlyPointsChart"></canvas>
                </div>
                <div class="chart-container">
                    <h4>✅ Tasks (Last 30 Days)</h4>
                    <canvas id="monthlyTasksChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        const weeklyLabels = <?= json_encode(array_column($chartWeeklyPoints, 'date')) ?>;
        const weeklyAssigned = <?= json_encode(array_column($chartWeeklyPoints, 'assigned')) ?>;
        const weeklyGained = <?= json_encode(array_column($chartWeeklyPoints, 'gained')) ?>;
        const weeklyTasks = <?= json_encode(array_column($chartWeeklyTasks, 'tasks')) ?>;

        const monthlyLabels = <?= json_encode(array_column($chartMonthlyPoints, 'date')) ?>;
        const monthlyAssigned = <?= json_encode(array_column($chartMonthlyPoints, 'assigned')) ?>;
        const monthlyGained = <?= json_encode(array_column($chartMonthlyPoints, 'gained')) ?>;
        const monthlyTasks = <?= json_encode(array_column($chartMonthlyTasks, 'tasks')) ?>;

        // Function to get chart colors based on dark mode
        function getChartColors() {
            const isDark = document.body.classList.contains('dark');
            if (isDark) {
                return {
                    assigned: '#00bfff', // Deep sky blue
                    gained: '#32cd32',   // Lime green
                    tasks: '#ff69b4'     // Hot pink
                };
            } else {
                return {
                    assigned: 'blue',
                    gained: 'green',
                    tasks: 'purple'
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

        const weeklyPointsChart = new Chart(document.getElementById('weeklyPointsChart'), {
            type: 'line',
            data: {
                labels: weeklyLabels,
                datasets: [{
                        label: 'Assigned',
                        data: weeklyAssigned,
                        borderColor: colors.assigned,
                        fill: false
                    },
                    {
                        label: 'Gained',
                        data: weeklyGained,
                        borderColor: colors.gained,
                        fill: false
                    }
                ]
            },
            options: options
        });

        const weeklyTasksChart = new Chart(document.getElementById('weeklyTasksChart'), {
            type: 'bar',
            data: {
                labels: weeklyLabels,
                datasets: [{
                    label: 'Tasks Completed',
                    data: weeklyTasks,
                    backgroundColor: colors.tasks
                }]
            },
            options: options
        });

        const monthlyPointsChart = new Chart(document.getElementById('monthlyPointsChart'), {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                        label: 'Assigned',
                        data: monthlyAssigned,
                        borderColor: colors.assigned,
                        fill: false
                    },
                    {
                        label: 'Gained',
                        data: monthlyGained,
                        borderColor: colors.gained,
                        fill: false
                    }
                ]
            },
            options: options
        });

        const monthlyTasksChart = new Chart(document.getElementById('monthlyTasksChart'), {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Tasks Completed',
                    data: monthlyTasks,
                    backgroundColor: colors.tasks
                }]
            },
            options: options
        });

        // Function to update chart colors and options
        function updateCharts() {
            const newColors = getChartColors();
            const newOptions = getChartOptions();

            // Update weekly points chart
            weeklyPointsChart.data.datasets[0].borderColor = newColors.assigned;
            weeklyPointsChart.data.datasets[1].borderColor = newColors.gained;
            weeklyPointsChart.options = newOptions;
            weeklyPointsChart.update();

            // Update weekly tasks chart
            weeklyTasksChart.data.datasets[0].backgroundColor = newColors.tasks;
            weeklyTasksChart.options = newOptions;
            weeklyTasksChart.update();

            // Update monthly points chart
            monthlyPointsChart.data.datasets[0].borderColor = newColors.assigned;
            monthlyPointsChart.data.datasets[1].borderColor = newColors.gained;
            monthlyPointsChart.options = newOptions;
            monthlyPointsChart.update();

            // Update monthly tasks chart
            monthlyTasksChart.data.datasets[0].backgroundColor = newColors.tasks;
            monthlyTasksChart.options = newOptions;
            monthlyTasksChart.update();
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
    <script src="../../js/session_check.js"></script>
</body>

</html>
<?php $conn->close(); ?>
