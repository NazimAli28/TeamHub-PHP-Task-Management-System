<?php
include 'db.php';

echo "<h2>Adding Bonus Points Column to Database</h2>";

// Check if bonus_points column already exists
$result = $conn->query("SHOW COLUMNS FROM designer_points LIKE 'bonus_points'");
if ($result->num_rows == 0) {
    echo "Adding bonus_points column...<br>";
    $conn->query("ALTER TABLE designer_points ADD COLUMN bonus_points FLOAT DEFAULT 0");
    echo "✅ bonus_points column added successfully<br>";
} else {
    echo "✅ bonus_points column already exists<br>";
}

echo "<br><h3>Database update completed!</h3>";
echo "<a href='../admin/pages/admin_dashboard.php'>Go to Admin Dashboard</a><br>";

$conn->close();
?>
