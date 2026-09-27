<?php
include 'db.php';

echo "<h2>Setting up Bonus Logs Table</h2>";

// Check if bonus_logs table exists
$result = $conn->query("SHOW TABLES LIKE 'bonus_logs'");
if ($result->num_rows == 0) {
    echo "Creating bonus_logs table...<br>";
    $conn->query("CREATE TABLE bonus_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        designer_name VARCHAR(100) NOT NULL,
        task_id INT DEFAULT NULL,
        bonus_points FLOAT NOT NULL,
        reason TEXT,
        added_by VARCHAR(100) NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES designer_points(id) ON DELETE SET NULL
    )");
    echo "✅ bonus_logs table created<br>";

    // Add indexes
    $conn->query("CREATE INDEX idx_bonus_logs_designer ON bonus_logs(designer_name)");
    $conn->query("CREATE INDEX idx_bonus_logs_task ON bonus_logs(task_id)");
    $conn->query("CREATE INDEX idx_bonus_logs_time ON bonus_logs(added_at)");
    echo "✅ Indexes created<br>";
} else {
    echo "✅ bonus_logs table already exists<br>";
}

echo "<br><h3>Bonus logs setup completed!</h3>";
echo "<a href='../admin/pages/admin_dashboard.php'>Go to Admin Dashboard</a><br>";

$conn->close();
?>
