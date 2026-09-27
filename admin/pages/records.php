<?php
include '../../config/db.php';
include '../includes/verify_admin.php';

// Fetch designer names from the database
$designer_query = $conn->prepare("SELECT name FROM users WHERE role = 'employee' ORDER BY name");
$designer_query->execute();
$designer_result = $designer_query->get_result();
$designers = $designer_result->fetch_all(MYSQLI_ASSOC);
$designer_query->close();

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png" />
    <title>Designer Records - TeamHub</title>
    <link rel="stylesheet" href="../css/styles.css?v=1.2">
    <link rel="stylesheet" href="../css/records_stylesheet.css?v=1.2">
    <script src="../js/backToTop.js"></script>
</head>

<body>
<?php include '../includes/navbar_admin.php'; ?>

    <div class="records-container">
        <div class="page-header">
            <h1 class="page-title">Designer Submission Records</h1>
            <p class="page-subtitle">Track and manage designer submissions with advanced filtering</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <!-- Filter Toggle Button -->
            <button type="button" onclick="toggleFilterForm()" class="filter-toggle">
                <span class="toggle-icon">🔽</span> Show Filters
            </button>

            <!-- Filter Form Container -->
            <div id="filterFormContainer" class="filter-form-wrapper hidden">

                <!-- Filter Form -->
                <form method="GET" class="filter-form" id="filterForm">
                    <div class="filter-grid">
                        <!-- Designer Filter -->
                        <div class="filter-group">
                            <label for="designer" class="filter-label">👤 Designer</label>
                            <select name="designer" id="designer" class="filter-select <?= !empty($_GET['designer']) ? 'filter-active' : '' ?>">
                                <option value="">All Designers</option>
                                <?php foreach ($designers as $designer): ?>
                                    <option value="<?php echo htmlspecialchars($designer['name']); ?>" <?= ($_GET['designer'] ?? '') == $designer['name'] ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($designer['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Order No Filter -->
                        <div class="filter-group">
                            <label for="order_no" class="filter-label">📋 Order No</label>
                            <input type="text" name="order_no" id="order_no" 
                                   value="<?= htmlspecialchars($_GET['order_no'] ?? '') ?>" 
                                   placeholder="Enter order number..."
                                   class="filter-input <?= !empty($_GET['order_no']) ? 'filter-active' : '' ?>">
                        </div>

                        <!-- Start Date Filter -->
                        <div class="filter-group">
                            <label for="start_date" class="filter-label">📅 Start Date</label>
                            <input type="date" name="start_date" id="start_date" 
                                   value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>" 
                                   onchange="handleDateRangeChange()"
                                   class="filter-input <?= !empty($_GET['start_date']) ? 'filter-active' : '' ?>">
                        </div>
                        
                        <!-- End Date Filter -->
                        <div class="filter-group">
                            <label for="end_date" class="filter-label">📅 End Date</label>
                            <input type="date" name="end_date" id="end_date" 
                                   value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>" 
                                   onchange="handleDateRangeChange()"
                                   class="filter-input <?= !empty($_GET['end_date']) ? 'filter-active' : '' ?>">
                        </div>
                        
                        <!-- Legacy date field for backward compatibility -->
                        <input type="hidden" name="date" id="date" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
                    </div>

                    <!-- Action Buttons -->
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="button-icon">🔍</span> Apply Filters
                        </button>
                        <a href="records.php" class="btn btn-danger">
                            <span class="button-icon">❌</span> Clear All
                        </a>
                    </div>
                </form>
            </div>
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
                            <th>Designer</th>
                            <th>Order No</th>
                            <th>Work</th>
                            <th>Work Type</th>
                            <th>Amount</th>
                            <th>Allotted Time</th>
                            <th>Completed Time</th>
                            <th>Pause Duration</th>
                            <th>Allotted Points</th>
                            <th>Gained Points</th>
                            <th>Bonus Points</th>
                            <th>Assigned Time</th>
                            <th>Completion Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php
        // Pagination settings
        $limit = 20; // Number of records per page
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $limit;

        $where = [];
        if (!empty($_GET['designer'])) {
            $designer = $conn->real_escape_string($_GET['designer']);
            $where[] = "designer_name = '$designer'";
        }
        if (!empty($_GET['order_no'])) {
            $order = $conn->real_escape_string($_GET['order_no']);
            $where[] = "order_no LIKE '%$order%'";
        }
        // Handle date range filtering
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
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM designer_points $where_sql";
        $count_result = $conn->query($count_query);
        $total_records = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $limit);

        // Get paginated results
        $query = "SELECT * FROM designer_points $where_sql ORDER BY timestamp DESC LIMIT $limit OFFSET $offset";
        $result = $conn->query($query);

        $current_category = '';
        while ($row = $result->fetch_assoc()) {
            $category = get_category($row['timestamp']);
            if ($category != $current_category) {
                $current_category = $category;
                echo "<tr><td colspan='14' class='category-header'>$category</td></tr>";
            }
            $isPending = empty($row['completion_time']);
        ?>
                        <tr<?= $isPending ? ' class="pending-row"' : '' ?>>
                            <td><?= htmlspecialchars($row['designer_name']) ?></td>
                            <td><?= htmlspecialchars($row['order_no']) ?></td>
                            <td><?= htmlspecialchars($row['work']) ?></td>
                            <td><?= htmlspecialchars($row['work_type']) ?></td>
                            <td><?= htmlspecialchars($row['amount']) ?></td>
                            <td>
                                <?= htmlspecialchars($row['allotted_time']) ?> min
                                <div class="countdown"
                                    data-assigned="<?= htmlspecialchars($row['assigned_time']) ?>"
                                    data-allotted="<?= htmlspecialchars($row['allotted_time']) ?>"
                                    id="countdown-<?= htmlspecialchars($row['id']) ?>"
                                    data-completed="<?= $row['completion_time'] ? '1' : '0' ?>"
                                    data-paused-duration="<?= $row['paused_duration'] ?? 0 ?>"
                                    data-paused="<?= $row['is_paused'] ? '1' : '0' ?>">
                                </div>
                                <?php if (empty($row['completion_time'])): ?>
                                    <div class="pause-resume-controls">
                                        <button type="button"
                                            class="pause-btn"
                                            onclick="togglePause(<?= $row['id'] ?>, <?= $row['is_paused'] ? 0 : 1 ?>)"
                                            data-task-id="<?= $row['id'] ?>"
                                            data-is-paused="<?= $row['is_paused'] ?>">
                                            <?= $row['is_paused'] ? '▶️ Resume' : '⏸️ Pause' ?>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="task-completed">✅ Task Completed</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['completion_time']): ?>
                                    <?= htmlspecialchars($row['completed_time']) ?> min
                                <?php else: ?>
                                    <span style="color: var(--warning-color);">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(round($row['paused_duration'] / 60)) ?> min</td>
                            <td><?= htmlspecialchars($row['allotted_points']) ?></td>
                            <td>
                                <?php if ($row['completion_time']): ?>
                                    <?= htmlspecialchars($row['points_gained']) ?>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['bonus_points']) ?></td>
                            <td><?= !empty($row['assigned_time']) ? date('d-m-Y h:i A', strtotime($row['assigned_time'])) : 'N/A' ?></td>
                            <td><?= !empty($row['completion_time']) ? date('d-m-Y h:i A', strtotime($row['completion_time'])) : '<span style="color: var(--warning-color);">Pending</span>' ?></td>
                            <td>
                                <div class="action-links">
                                    <a href="edit_record.php?id=<?= $row['id'] ?>" class="action-link edit-link">Edit</a>
                                    <a href="../../api/delete_record.php?id=<?= $row['id'] ?>" class="action-link delete-link" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                                    <?php if (!empty($row['completion_time'])): ?>
                                        <a href="bonus_points.php?designer_name=<?= urlencode($row['designer_name']) ?>&task_id=<?= $row['id'] ?>" class="action-link bonus-link">Bonus</a>
                                    <?php else: ?>
                                        <span class="action-link bonus-link-disabled" title="Bonus points can only be added after task completion">Bonus</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
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

    <button id="backToTopBtn" title="Go to top">↑</button>

    <!-- Export Section -->
    <div class="export-section">
        <form method="GET" action="../../api/export.php">
            <input type="hidden" name="designer" value="<?= htmlspecialchars($_GET['designer'] ?? '') ?>">
            <input type="hidden" name="order_no" value="<?= htmlspecialchars($_GET['order_no'] ?? '') ?>">
            <input type="hidden" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
            <input type="hidden" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
            <input type="hidden" name="date" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
            <input type="hidden" name="page" value="<?= $page ?>">
            <button type="submit" class="export-btn">📁 Export Shown Data</button>
        </form>
    </div>
    
    <div id="toast" class="toast"></div>

    <!-- Audio for notifications -->
    <!-- Removed audio element for taskAssignedSound -->

    <script src="../js/navbar.js"></script>
    <script src="../../js/session_check.js"></script>
    <script>
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status === 'added') {
            showToast('✅ Record added successfully!');
        } else if (status === 'updated') {
            showToast('📝 Record updated successfully!');
        } else if (status === 'deleted') {
            showToast('❌ Record deleted successfully!');
        }

        function toggleDarkMode() {
            document.body.classList.toggle("dark");
            localStorage.setItem("darkMode", document.body.classList.contains("dark"));
        }

        if (localStorage.getItem("darkMode") === "true") {
            document.body.classList.add("dark");
        }

        function toggleFilterForm() {
            const wrapper = document.getElementById('filterFormContainer');
            const toggleBtn = document.querySelector('.filter-toggle');
            const toggleIcon = toggleBtn.querySelector('.toggle-icon');
            const isVisible = wrapper.style.display === 'block';

            wrapper.style.display = isVisible ? 'none' : 'block';
            toggleIcon.textContent = isVisible ? '🔽' : '🔼';
            toggleBtn.innerHTML = isVisible ? 
                '<span class="toggle-icon">🔽</span> Show Filters' : 
                '<span class="toggle-icon">🔼</span> Hide Filters';
            
            // Auto-focus on first input when showing
            if (!isVisible) {
                setTimeout(() => {
                    const firstInput = wrapper.querySelector('input, select');
                    if (firstInput) firstInput.focus();
                }, 100);
            }
        }

        // Add loading state to form submission
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            this.classList.add('loading');
            setTimeout(() => {
                this.classList.remove('loading');
            }, 2000);
        });

        // Update active filter indicators on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasFilters = urlParams.has('designer') || urlParams.has('order_no') || 
                              urlParams.has('start_date') || urlParams.has('end_date');
            
            if (hasFilters) {
                // Show filter form by default if filters are active
                const wrapper = document.getElementById('filterFormContainer');
                const toggleBtn = document.querySelector('.filter-toggle');
                const toggleIcon = toggleBtn.querySelector('.toggle-icon');
                
                wrapper.style.display = 'block';
                toggleIcon.textContent = '🔼';
                toggleBtn.innerHTML = '<span class="toggle-icon">🔼</span> Hide Filters';
            }
        });

        // Handle date range validation and auto-population
        function handleDateRangeChange() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const legacyDateInput = document.getElementById('date');
            
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            // Clear legacy date when using new date range
            if (startDate || endDate) {
                legacyDateInput.value = '';
            }
            
            // Validate date range
            if (startDate && endDate && startDate > endDate) {
                showToast('❌ End date cannot be before start date');
                endDateInput.value = '';
                return;
            }
            
            // Auto-populate end date if only start date is selected
            if (startDate && !endDate) {
                endDateInput.value = startDate;
                showToast('📅 End date set to same as start date. Adjust if needed.');
            }
        }

        // Time countdown
        function pad(n) {
            return n < 10 ? '0' + n : n;
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
        setInterval(updateCountdowns, 1000);
        document.addEventListener('DOMContentLoaded', updateCountdowns);

        // Pause/Resume functionality
        function togglePause(taskId, action) {
            const actionText = action === 1 ? 'pause' : 'resume';
            const button = event.target;

            fetch('../../api/pause_resume.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}&action=${actionText}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update button text and state
                        if (actionText === 'pause') {
                            button.textContent = '▶️ Resume';
                            button.setAttribute('data-is-paused', '1');
                            button.onclick = () => togglePause(taskId, 0);
                        } else {
                            button.textContent = '⏸️ Pause';
                            button.setAttribute('data-is-paused', '0');
                            button.onclick = () => togglePause(taskId, 1);
                        }

                        // Update countdown data attribute
                        const countdown = document.getElementById(`countdown-${taskId}`);
                        if (countdown) {
                            countdown.setAttribute('data-paused', action === 1 ? '1' : '0');

                            // If resuming, fetch updated paused_duration
                                if (actionText === 'resume') {
                                    fetch(`../../api/get_task_status.php?task_id=${taskId}`)
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                countdown.setAttribute('data-paused-duration', data.paused_duration);
                                                // Force immediate countdown update
                                                setTimeout(() => updateCountdowns(), 100);
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error fetching task status:', error);
                                        });
                                }
                        }

                        // Notify employee about pause/resume
                        const row = button.closest('tr');
                        const designerName = row.cells[0].textContent;
                        const pauseResumeData = {
                            timestamp: Date.now(),
                            task_id: taskId,
                            designer_name: designerName,
                            is_paused: action === 1
                        };
                        localStorage.setItem('taskPauseResume', JSON.stringify(pauseResumeData));

                        // Also trigger storage event for immediate notification
                        window.dispatchEvent(new StorageEvent('storage', {
                            key: 'taskPauseResume',
                            newValue: JSON.stringify(pauseResumeData)
                        }));

                        showToast(data.message);
                    } else {
                        showToast('Error: ' + (data.error || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error occurred while updating task status: ' + error.message);
                });
        }

        // Listen for task completion notifications from tasks.php
        window.addEventListener('storage', function(e) {
            if (e.key === 'taskCompleted') {
                const taskData = JSON.parse(e.newValue);
                if (taskData) {
                // Play completion sound
                // Removed audio play for taskAssignedSound
                // const audio = document.getElementById('taskAssignedSound');
                // if (audio) {
                //     audio.play().catch(e => console.log('Audio play failed:', e));
                // }

                    // Show notification
                    showToast(`✅ Task ${taskData.order_no} completed by ${taskData.designer_name}!`);

                    // Reload the page to show updated status
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            }
        });

        // Auto-refresh table every 30 seconds
        let refreshCountdown = 30;

        setInterval(function() {
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
    </script>

</body>

</html>
