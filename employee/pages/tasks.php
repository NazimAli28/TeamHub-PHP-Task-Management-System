<?php
session_start();
date_default_timezone_set('Asia/Karachi');
include '../includes/verify_employee.php';
include '../../config/db.php';

// Function to categorize records by date
function get_category($timestamp) {
    $date = date('Y-m-d', strtotime($timestamp));
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $seven_days_ago = date('Y-m-d', strtotime('-7 days'));

    if ($date == $today) {
        return 'Today';
    } elseif ($date == $yesterday) {
        return 'Yesterday';
    } elseif ($date >= $seven_days_ago) {
        return 'Last 7 Days';
    } else {
        return date('F Y', strtotime($timestamp));
    }
}

// Pagination settings
$limit = 20; // Number of records per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Fetch user data from DB
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result()->fetch_assoc();
$user_query->close();

$employee_name = $user_result['name'] ?? $_SESSION['username'];
$profile_pic = $user_result['profile_picture'] ?: '../../assets/images/default_profile.png';

// Get total count for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM designer_points WHERE designer_name = ?");
$count_stmt->bind_param("s", $employee_name);
$count_stmt->execute();
$total_result = $count_stmt->get_result()->fetch_assoc();
$total_records = $total_result['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

// Fetch paginated tasks
$stmt = $conn->prepare("SELECT *, bonus_points FROM designer_points WHERE designer_name = ? ORDER BY assigned_time DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $employee_name, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
$totalAllotted = 0;
$totalGained = 0;

while ($row = $result->fetch_assoc()) {
    // Allotted points = allotted_time / 10
    $allottedPoints = $row['allotted_time'] / 10;
    $totalAllotted += $allottedPoints;

    // Use stored points_gained for completed tasks
    $gainedPoints = 0;
    if ($row['completion_time'] && isset($row['points_gained'])) {
        $gainedPoints = floatval($row['points_gained']);
        $totalGained += $gainedPoints;
    }

    $row['allotted_points'] = $allottedPoints;
    $row['gained_points'] = $gainedPoints;
    $tasks[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png" />
    <title>My Tasks</title>
    <link rel="stylesheet" href="../css/employee_styles.css?v=1.1">
    <link rel="stylesheet" href="../css/sidebar_stylesheet.css?v=1.1">
    <link rel="stylesheet" href="../css/tasks_stylesheet.css?v=1.1">
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="records-container">
                <div class="page-header">
                    <h1 class="page-title">My Tasks</h1>
                    <p class="page-subtitle">Track and manage your assigned tasks</p>
                </div>

                <!-- Refresh Indicator -->
                <div id="refresh-indicator" class="refresh-indicator">
                    <span class="refresh-text">🔄 Auto-refresh in <span id="refresh-countdown">30</span> seconds</span>
                    <button onclick="location.reload()" class="refresh-btn">Refresh Now</button>
                </div>

                <!-- Table Section -->
                <div class="table-section">
                    <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order No</th>
                            <th>Work</th>
                            <th>Work Type</th>
                            <th>Alloted Time</th>
                            <th>Completed Time</th>
                            <th>Assigned</th>
                            <th>Completion</th>
                            <th>Alloted Points</th>
                            <th>Gained Points</th>
                            <th>Bonus Points</th>
                        </tr>
                    </thead>
                    <tbody id="tasksTableBody">
                        <?php
                        $current_category = '';
                        foreach ($tasks as $task):
                            $category = get_category($task['assigned_time']);
                            if ($category != $current_category) {
                                $current_category = $category;
                                echo "<tr><td colspan='10' class='category-header'>$category</td></tr>";
                            }
                        ?>
                            <tr<?= empty($task['completion_time']) ? ' class="pending-row"' : '' ?> data-task-id="<?= $task['id'] ?>">
                                <td><?= htmlspecialchars($task['order_no']) ?></td>
                                <td><?= htmlspecialchars($task['work']) ?></td>
                                <td><?= htmlspecialchars($task['work_type']) ?></td>
                                <td>
                                    <?= htmlspecialchars($task['allotted_time']) ?> min
                                    <div class="countdown"
                                        data-assigned="<?= htmlspecialchars($task['assigned_time']) ?>"
                                        data-allotted="<?= htmlspecialchars($task['allotted_time']) ?>"
                                        id="countdown-<?= htmlspecialchars($task['id']) ?>"
                                        data-completed="<?= $task['completion_time'] ? '1' : '0' ?>"
                                        data-paused-duration="<?= $task['paused_duration'] ?? 0 ?>"
                                        data-paused="<?= $task['is_paused'] ? '1' : '0' ?>">
                                    </div>
                                    <?php if (!empty($task['is_paused'])): ?>
                                        <div class="task-status paused">⏸️ Task Paused by Admin</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($task['completion_time']): ?>
                                        <?= htmlspecialchars($task['completed_time']) ?> min
                                    <?php else: ?>
                                        Pending
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d-m-Y h:i A', strtotime($task['assigned_time'])) ?></td>
                                <td>
                                    <?php if ($task['completion_time']): ?>
                                        ✅ <?= date('d-m-Y h:i A', strtotime($task['completion_time'])) ?>
                                    <?php else: ?>
                                        <button type="button" class="complete-btn" onclick="markTaskCompleted(<?= $task['id'] ?>)">Mark Completed</button>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($task['allotted_points']) ?></td>
                                <td>
                                    <?php if ($task['completion_time']): ?>
                                        <?= htmlspecialchars($task['gained_points']) ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($task['completion_time'] && isset($task['bonus_points'])): ?>
                                        <?= htmlspecialchars($task['bonus_points']) ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php 
                // Build query string without page parameter
                $query_params = $_GET;
                unset($query_params['page']);
                $query_string = http_build_query($query_params);
                $separator = $query_string ? '&' : '';
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $separator . $query_string ?>" class="pagination-link prev-next">&laquo; Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<a href="?page=1' . $separator . $query_string . '" class="pagination-link">1</a>';
                    if ($start_page > 2) echo '<span class="pagination-ellipsis">...</span>';
                }
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="?page=<?= $i ?><?= $separator . $query_string ?>" class="pagination-link <?= $i == $page ? 'pagination-active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo '<span class="pagination-ellipsis">...</span>';
                    echo '<a href="?page=' . $total_pages . $separator . $query_string . '" class="pagination-link">' . $total_pages . '</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $separator . $query_string ?>" class="pagination-link prev-next">Next &raquo;</a>
                <?php endif; ?>
            </div>

            <div class="pagination-info">
                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_records) ?> of <?= $total_records ?> records
            </div>

            <div id="toast" class="toast"></div>

            <script>
                function pad(n) {
                    return n < 10 ? '0' + n : n;
                }

                function showNotification(message) {
                    const toast = document.getElementById('toast');
                    toast.textContent = message;
                    toast.classList.add('show');
                    setTimeout(() => {
                        toast.classList.remove('show');
                    }, 3000);
                }

                function updateCountdowns() {
                    const countdowns = document.querySelectorAll('.countdown');
                    countdowns.forEach(function(div) {
                        if (div.dataset.completed === '1') {
                            div.textContent = '✅ Task Complete';
                            return;
                        }

                        // Check if task is paused
                        if (div.dataset.paused === '1') {
                            div.textContent = '⏸️ Task Paused';
                            return;
                        }

                        const assigned = new Date(div.dataset.assigned.replace(' ', 'T'));
                        const allotted = parseInt(div.dataset.allotted) * 60 * 1000; // ms
                        const pauseSecs = parseInt(div.dataset.pausedDuration || "0");
                        const pauseMs = pauseSecs * 1000;

                        const now = new Date();
                        // Calculate the original deadline
                        const originalEnd = new Date(assigned.getTime() + allotted);

                        // The paused duration represents time that was "frozen" during pause
                        // So we need to extend the deadline by the paused duration
                        const effectiveEnd = new Date(originalEnd.getTime() + pauseMs);
                        let diff = effectiveEnd - now;
                        console.log('diff:', diff);

                        if (diff > 0) {
                            const mins = Math.floor(diff / 60000);
                            const secs = Math.floor((diff % 60000) / 1000);
                            div.textContent = `⏳ ${pad(mins)}:${pad(secs)} left`;
                        } else {
                            let up = Math.abs(diff);
                            const mins = Math.floor(up / 60000);
                            const secs = Math.floor((up % 60000) / 1000);
                            div.textContent = `⏰ Up time: ${pad(mins)}:${pad(secs)}`;
                        }
                    });
                }

                function markTaskCompleted(taskId) {
                    if (!confirm('Mark this task completed?')) {
                        return;
                    }

                    if (isCompletingTask) {
                        alert('Task completion already in progress.');
                        return;
                    }

                    isCompletingTask = true;

                    fetch('../../api/complete_task.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `task_id=${taskId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            isCompletingTask = false;
                            if (data.success) {
                                // Play completion sound
                                // Removed audio play for taskCompletedSound
                                // const audio = document.getElementById('taskCompletedSound');
                                // audio.play().catch(e => console.log('Audio play failed:', e));

                                // Update the row
                                const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
                                if (row) {
                                    // Update completion cell
                                    const completionCell = row.cells[6];
                                    completionCell.innerHTML = `✅ ${data.completion_time}`;

                                    // Update completed time cell
                                    const completedTimeCell = row.cells[4];
                                    completedTimeCell.textContent = data.completed_time;

                                    // Update gained points cell
                                    const gainedPointsCell = row.cells[8];
                                    gainedPointsCell.textContent = data.points_gained;

                                    // Update countdown
                                    const countdown = document.getElementById(`countdown-${taskId}`);
                                    if (countdown) {
                                        countdown.textContent = '✅ Task Complete';
                                        countdown.setAttribute('data-completed', '1');
                                    }

                                    // Remove pending class
                                    row.classList.remove('pending-row');

                                    // Notify admin about task completion
                                    localStorage.setItem('taskCompleted', JSON.stringify({
                                        timestamp: Date.now(),
                                        designer_name: '<?= $employee_name ?>',
                                        order_no: row.cells[0].textContent,
                                        task_id: taskId
                                    }));
                                }
                            } else {
                                alert('Error: ' + (data.error || 'Failed to complete task'));
                            }
                        })
                        .catch(error => {
                            isCompletingTask = false;
                            console.error('Error:', error);
                            alert('Network error occurred');
                        });
                }

                // Real-time updates for pause/resume
                function updateTaskStatus(taskId, isPaused) {
                    console.log('Updating task status:', taskId, 'isPaused:', isPaused);
                    const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
                    if (row) {
                        const countdown = document.getElementById(`countdown-${taskId}`);
                        if (countdown) {
                            countdown.setAttribute('data-paused', isPaused ? '1' : '0');
                            console.log('Updated data-paused to:', isPaused ? '1' : '0');

                            // Force immediate countdown update
                            if (isPaused) {
                                countdown.textContent = '⏸️ Task Paused';
                                console.log('Task paused, countdown stopped');
                            } else {
                                console.log('Task resumed, fetching updated status...');
                                // Fetch updated task data to get correct paused_duration
                                fetch(`../../api/get_task_status.php?task_id=${taskId}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            console.log('Updated paused_duration:', data.paused_duration);
                                            countdown.setAttribute('data-paused-duration', data.paused_duration);
                                            // Force immediate countdown update
                                            updateCountdowns();
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error fetching task status:', error);
                                        // Fallback to immediate update
                                        updateCountdowns();
                                    });
                            }
                        }

                        // Update pause status display
                        let statusDiv = row.querySelector('.task-status');
                        if (isPaused) {
                            if (!statusDiv) {
                                statusDiv = document.createElement('div');
                                statusDiv.className = 'task-status paused';
                                row.cells[3].appendChild(statusDiv);
                            }
                            statusDiv.textContent = '⏸️ Task Paused by Admin';
                        } else if (statusDiv) {
                            statusDiv.remove();
                        }
                    }
                }

                // Listen for pause/resume updates from admin
                window.addEventListener('storage', function(e) {
                    if (e.key === 'taskPauseResume') {
                        try {
                            const data = JSON.parse(e.newValue);
                            console.log('Received pause/resume event:', data);
                            if (data && data.designer_name === '<?= $employee_name ?>') {
                                updateTaskStatus(data.task_id, data.is_paused);
                            }
                        } catch (error) {
                            console.error('Error parsing pause/resume data:', error);
                        }
                    }
                });

                // Check for new tasks periodically
                let lastTaskCheck = Date.now();
                setInterval(function() {
                    const newTaskData = localStorage.getItem('newTaskAssigned');
                    if (newTaskData) {
                        try {
                            const data = JSON.parse(newTaskData);
                            if (data && data.designer === '<?= $employee_name ?>' && data.timestamp > lastTaskCheck) {
                                lastTaskCheck = data.timestamp;
                                console.log('New task detected:', data);

                                // Play new task sound
                                // Removed audio play for newTaskSound
                                // const audio = document.getElementById('newTaskSound');
                                // audio.play().catch(e => console.log('Audio play failed:', e));

                                // Show notification
                                showNotification('New task assigned: ' + data.order_no);

                                // Reset refresh countdown before reloading
                                refreshCountdown = 30;

                                // Reload the page to show new task
                                location.reload();
                            }
                        } catch (e) {
                            console.error('Error parsing newTaskData:', e);
                        }
                    }
                }, 2000); // Check every 2 seconds

                // Listen for pause/resume updates from admin
                window.addEventListener('storage', function(e) {
                    if (e.key === 'taskPauseResume') {
                        const data = JSON.parse(e.newValue);
                        if (data && data.designer_name === '<?= $employee_name ?>') {
                            updateTaskStatus(data.task_id, data.is_paused);
                        }
                    }
                });

                // Manual test function for pause/resume
                window.testPauseResume = function(taskId, isPaused) {
                    console.log('Manual test: pause/resume task', taskId, 'isPaused:', isPaused);
                    updateTaskStatus(taskId, isPaused);
                };

                // Test function to simulate pause/resume with specific paused duration
                window.testPauseResumeWithDuration = function(taskId, isPaused, pausedDuration) {
                    console.log('Manual test: pause/resume task', taskId, 'isPaused:', isPaused, 'pausedDuration:', pausedDuration);
                    const countdown = document.getElementById(`countdown-${taskId}`);
                    if (countdown) {
                        countdown.setAttribute('data-paused', isPaused ? '1' : '0');
                        if (!isPaused) {
                            countdown.setAttribute('data-paused-duration', pausedDuration);
                        }
                        updateCountdowns();
                    }
                };

                // Auto-refresh table every 30 seconds
                let refreshCountdown = 30;
                let isCompletingTask = false;

                setInterval(function() {
                    if (isCompletingTask) {
                        refreshCountdown = 30;
                        return;
                    }
                    refreshCountdown--;
                    const countdownElement = document.getElementById('refresh-countdown');
                    if (countdownElement) {
                        countdownElement.textContent = refreshCountdown;
                    }

                    if (refreshCountdown <= 0) {
                        console.log('Auto-refreshing table...');
                        location.reload();
                    }
                }, 1000); // Update countdown every second

                setInterval(updateCountdowns, 1000);
                document.addEventListener('DOMContentLoaded', updateCountdowns);
            </script>
        </div>
    </div>
    <script src="../../js/session_check.js"></script>
</body>

</html>