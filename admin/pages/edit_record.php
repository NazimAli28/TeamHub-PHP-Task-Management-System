<?php
include '../../config/db.php';
include '../includes/verify_admin.php';

// Validate and sanitize the ID parameter
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "Invalid record ID.";
    exit;
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM designer_points WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Record not found.";
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

// Fetch designer names from the database
$designer_query = $conn->prepare("SELECT name FROM users WHERE role = 'employee' ORDER BY name");
$designer_query->execute();
$designer_result = $designer_query->get_result();
$designers = $designer_result->fetch_all(MYSQLI_ASSOC);
$designer_query->close();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png" />
    <title>Edit Record</title>
    <link rel="stylesheet" href="../css/styles.css?v=1.2">
    <link rel="stylesheet" href="../css/edit_stylesheet.css">
</head>

<body>
<?php include '../includes/navbar_admin.php'; ?>

    <div class="container">
        <h2>Edit Designer Record</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                Record updated successfully!
            </div>
        <?php endif; ?>

        <form action="../../api/update_record.php" method="POST" onsubmit="return validateForm()">
            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">

            <div class="form-group">
                <label for="designer_name">Designer Name:</label>
                <select name="designer_name" id="designer_name" required>
                    <option value="">Select Designer</option>
                    <?php foreach ($designers as $designer): ?>
                        <option value="<?php echo htmlspecialchars($designer['name']); ?>" <?= ($row['designer_name'] == $designer['name']) ? 'selected' : '' ?>>
                            <?php echo htmlspecialchars($designer['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="order_no">Order No:</label>
                <input type="text" name="order_no" id="order_no" value="<?= htmlspecialchars($row['order_no']) ?>" required>
            </div>

            <div class="form-group">
                <label for="work">Work:</label>
                <select name="work" id="work" required onchange="updateWorkTypeOptions()">
                    <option value="">Select Work Type</option>
                    <?php
                    $works = [
                        "Logo Static",
                        "Logo Animated",
                        "Social Post Static",
                        "Social Post Animated",
                        "Web Banner Set",
                        "Layout Design",
                        "Screens Static",
                        "Screens Animated",
                        "Wallpaper Static",
                        "Wallpaper Animated",
                        "Transition Animated",
                        "Intro Animated",
                        "Outro Animated",
                        "Character Sheet",
                        "Avatar Static",
                        "Avatar Animated",
                        "Reaction Graphic Static",
                        "Reaction Graphic Animated",
                        "Badge Icon",
                        "2D Character Cutout",
                        "2D Character Rigging",
                        "Video Edit",
                        "Brand Pack Static",
                        "Brand Pack Animated",
                        "Poster Design",
                        "3D Model Grayscale",
                        "3D Model Textured",
                        "3D Model Rig",
                        "3D Character Model",
                        "3D Scene Creation",
                        "3D Asset Grayscale",
                        "3D Asset Textured",
                        "Corporate Logo",
                        "Corporate Poster",
                        "Transition",
                        "Thumbnail",
                        "Merch Design",
                        "Mockups",
                        "Reels Edit",
                        "Long Video Edits",
                        "Tattoo Design"
                    ];
                    foreach ($works as $work) {
                        $selected = ($row['work'] == $work) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($work) . "' $selected>" . htmlspecialchars($work) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="work_type">Work Type:</label>
                <select name="work_type" id="work_type" required>
                    <option value="">Select Type</option>
                    <option value="New Easy" <?= ($row['work_type'] == 'New Easy') ? 'selected' : '' ?>>New Easy</option>
                    <option value="New Hard" <?= ($row['work_type'] == 'New Hard') ? 'selected' : '' ?>>New Hard</option>
                    <option value="Feedback Easy" <?= ($row['work_type'] == 'Feedback Easy') ? 'selected' : '' ?>>Feedback Easy</option>
                    <option value="Feedback Hard" <?= ($row['work_type'] == 'Feedback Hard') ? 'selected' : '' ?>>Feedback Hard</option>
                    <option value="Approve Need To Mail" <?= ($row['work_type'] == 'Approve Need To Mail') ? 'selected' : '' ?>>Approve Need To Mail</option>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" step="1" name="amount" id="amount" min="0" value="<?= htmlspecialchars($row['amount']) ?>" required>
            </div>

            <div class="form-group">
                <label for="allotted_time">Allotted Time (minutes):</label>
                <input type="number" step="1" name="allotted_time" id="allotted_time" min="0" value="<?= htmlspecialchars($row['allotted_time'] ?? 0) ?>" required onchange="calculatePoints()">
            </div>

            <div class="form-group">
                <label for="allotted_points">Allotted Points:</label>
                <input type="number" step="0.01" min="0" name="allotted_points" id="allotted_points" value="<?= htmlspecialchars($row['allotted_points']) ?>" required readonly>
                <small>Calculated automatically (Time ÷ 10)</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Update Record</button>
                <a href="records.php" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="../js/navbar.js"></script>
    <script src="../../js/session_check.js"></script>
    <script>
        function calculatePoints() {
            const allottedTime = document.getElementById('allotted_time').value;
            const allottedPoints = document.getElementById('allotted_points');

            if (allottedTime > 0) {
                allottedPoints.value = (allottedTime / 10).toFixed(2);
            } else {
                allottedPoints.value = '0.00';
            }
        }

        function updateWorkTypeOptions() {
            const workSelect = document.getElementById('work');
            const workTypeSelect = document.getElementById('work_type');
            const selectedWork = workSelect.value;

            // Reset work type selection
            workTypeSelect.value = '';

            // You can add logic here to auto-select work type based on work if needed
        }

        function validateForm() {
            const designerName = document.getElementById('designer_name').value;
            const orderNo = document.getElementById('order_no').value;
            const work = document.getElementById('work').value;
            const workType = document.getElementById('work_type').value;
            const amount = document.getElementById('amount').value;
            const allottedTime = document.getElementById('allotted_time').value;
            const pointsGained = document.getElementById('points_gained').value;

            if (!designerName || !orderNo || !work || !workType || !amount || !allottedTime || !pointsGained) {
                alert('Please fill in all required fields.');
                return false;
            }

            if (amount < 0 || allottedTime < 0 || pointsGained < 0) {
                alert('Please enter valid positive numbers for amount, time, and points.');
                return false;
            }

            return true;
        }

        // Initialize points calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculatePoints();
        });

        function toggleDarkMode() {
            document.body.classList.toggle("dark");
            localStorage.setItem("darkMode", document.body.classList.contains("dark"));
        }

        if (localStorage.getItem("darkMode") === "true") {
            document.body.classList.add("dark");
        }
    </script>
</body>

</html>
