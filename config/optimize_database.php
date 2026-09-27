<?php
// Database optimization script for performance improvements
include 'db.php';

// Create indexes on frequently queried columns
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_designer_name ON designer_points(designer_name)",
    "CREATE INDEX IF NOT EXISTS idx_order_no ON designer_points(order_no)",
    "CREATE INDEX IF NOT EXISTS idx_assigned_time ON designer_points(assigned_time)",
    "CREATE INDEX IF NOT EXISTS idx_completion_time ON designer_points(completion_time)",
    "CREATE INDEX IF NOT EXISTS idx_timestamp ON designer_points(timestamp)",
    "CREATE INDEX IF NOT EXISTS idx_designer_completion ON designer_points(designer_name, completion_time)",
    "CREATE INDEX IF NOT EXISTS idx_date_range ON designer_points(assigned_time, completion_time)"
];

// Execute index creation
foreach ($indexes as $index) {
    if ($conn->query($index) === TRUE) {
        echo "Index created successfully: " . $index . "\n";
    } else {
        echo "Error creating index: " . $conn->error . "\n";
    }
}

// Optimize table structure
$optimize = "OPTIMIZE TABLE designer_points";
if ($conn->query($optimize) === TRUE) {
    echo "Table optimized successfully\n";
}

// Add query performance monitoring
$conn->query("SET profiling = 1");

echo "Database optimization completed successfully!\n";
$conn->close();
?>
