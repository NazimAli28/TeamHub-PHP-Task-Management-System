<?php
date_default_timezone_set('Asia/Karachi');
include '../includes/verify_admin.php';
include '../../config/db.php';

// Get query parameters
$designer_name = isset($_GET['designer_name']) ? htmlspecialchars($_GET['designer_name']) : '';

// Validate that designer_name is provided
if (empty($designer_name)) {
    die("Error: Designer name is required. Please access this page from the records page.");
}

// Get order number if task_id is provided
$order_no = '';
if (isset($_GET['task_id'])) {
    $task_id = intval($_GET['task_id']);
    $stmt = $conn->prepare("SELECT order_no FROM designer_points WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $order_no = htmlspecialchars($row['order_no']);
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png" />
    <title>Bonus Points Management</title>
    <link rel="stylesheet" href="../css/styles.css?v=1.2">
</head>

<body>
<?php include '../includes/navbar_admin.php'; ?>

    <h2>Bonus Points Management</h2>

    <div id="successMessage" class="success" style="display: none;">✅ Bonus points added successfully!</div>
    <div id="errorMessage" class="error" style="display: none;"></div>

    <form id="bonusForm" action="../../api/add_bonus_points.php" method="POST">
        <div class="form-group">
            <label>Designer:</label>
            <input type="text" value="<?= $designer_name ?>" readonly>
            <input type="hidden" name="designer_name" value="<?= $designer_name ?>">
            <?php if (isset($_GET['task_id'])): ?>
                <input type="hidden" name="task_id" value="<?= intval($_GET['task_id']) ?>">
            <?php endif; ?>
        </div>

        <?php if (!empty($order_no)): ?>
            <div class="form-group">
                <label>Order No:</label>
                <input type="text" value="<?= $order_no ?>" readonly>
                <input type="hidden" name="order_no" value="<?= $order_no ?>">
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Bonus Points:</label>
            <input type="number" name="bonus_points" min="0" step="0.01" placeholder="Enter bonus points" required>
        </div>

        <div class="form-group">
            <label>Reason for Bonus:</label>
            <textarea name="reason" placeholder="Enter reason for bonus points" rows="3"></textarea>
        </div>

        <button type="submit" id="submitBtn">Add Bonus Points</button>
    </form>

    <script src="../js/navbar.js"></script>
    <script src="../../js/session_check.js"></script>
    <script>
        // AJAX form submission
        document.getElementById('bonusForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding Bonus...';

            // Hide previous messages
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';

            fetch('../../api/add_bonus_points.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        successMsg.style.display = 'block';
                        successMsg.textContent = data.message || '✅ Bonus points added successfully!';

                        // Reset form
                        this.reset();

                        // Auto-hide success message after 3 seconds
                        setTimeout(() => {
                            successMsg.style.display = 'none';
                        }, 3000);
                    } else {
                        errorMsg.textContent = data.error || 'Failed to add bonus points';
                        errorMsg.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorMsg.textContent = 'Network error occurred';
                    errorMsg.style.display = 'block';
                })
                .finally(() => {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Add Bonus Points';
                });
        });
    </script>
</body>

</html>
