<?php
include 'db.php';

echo "<h2>Setting up Pause/Resume Database Structure</h2>";

// Check if columns already exist
$result = $conn->query("SHOW COLUMNS FROM designer_points LIKE 'is_paused'");
if ($result->num_rows == 0) {
    echo "Adding is_paused column...<br>";
    $conn->query("ALTER TABLE designer_points ADD COLUMN is_paused TINYINT(1) DEFAULT 0");
    echo "✅ is_paused column added<br>";
} else {
    echo "✅ is_paused column already exists<br>";
}

$result = $conn->query("SHOW COLUMNS FROM designer_points LIKE 'paused_duration'");
if ($result->num_rows == 0) {
    echo "Adding paused_duration column...<br>";
    $conn->query("ALTER TABLE designer_points ADD COLUMN paused_duration INT DEFAULT 0");
    echo "✅ paused_duration column added<br>";
} else {
    echo "✅ paused_duration column already exists<br>";
}

// Check if pause_logs table exists
$result = $conn->query("SHOW TABLES LIKE 'pause_logs'");
if ($result->num_rows == 0) {
    echo "Creating pause_logs table...<br>";
    $conn->query("CREATE TABLE pause_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        designer_point_id INT NOT NULL,
        pause_time DATETIME NOT NULL,
        resume_time DATETIME DEFAULT NULL,
        FOREIGN KEY (designer_point_id) REFERENCES designer_points(id) ON DELETE CASCADE
    )");
    echo "✅ pause_logs table created<br>";

    // Add indexes
    $conn->query("CREATE INDEX idx_pause_logs_task_id ON pause_logs(designer_point_id)");
    $conn->query("CREATE INDEX idx_pause_logs_time ON pause_logs(pause_time, resume_time)");
    echo "✅ Indexes created<br>";
} else {
    echo "✅ pause_logs table already exists<br>";
}

echo "<br><h3>Database setup completed!</h3>";
echo "<a href='../admin/pages/records.php'>Go to Records</a><br>";
echo "<a href='../employee/pages/tasks.php'>Go to Tasks</a>";

$conn->close();
