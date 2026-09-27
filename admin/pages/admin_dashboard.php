<?php date_default_timezone_set('Asia/Karachi'); ?>
<?php
include '../includes/verify_admin.php';
include '../../config/db.php';

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
    <title>Designer Entry Form</title>
    <link rel="stylesheet" href="../css/styles.css?v=1.2">
    <link rel="stylesheet" href="../css/admin_dashboard_styles.css?v=1.0">
</head>

<body>
<?php include '../includes/navbar_admin.php'; ?>

    <h2>Add Designer Record</h2>

    <div id="successMessage" class="success" style="display: none;">✅ Task assigned successfully!</div>
    <div id="errorMessage" class="error" style="display: none;"></div>

    <form id="taskForm" action="../../api/add_record.php" method="POST">
        <div class="form-group">
            <label>Designer Name:</label>
            <select name="designer_name" required>
                <option value="">Select Designer</option>
                <?php foreach ($designers as $designer): ?>
                    <option value="<?php echo htmlspecialchars($designer['name']); ?>">
                        <?php echo htmlspecialchars($designer['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Order No:</label>
            <input type="text" name="order_no" placeholder="e.g., ORD123A" required>
        </div>

        <div class="form-group">
            <label>Work:</label>
            <select id="workSelect">
                <option value="">Select Work</option>
                <option>Logo Static</option>
                <option>Logo Animated</option>
                <option>Social Post Static</option>
                <option>Social Post Animated</option>
                <option>Web Banner Set</option>
                <option>Illustration</option>
                <option>Portrait Static</option>
                <option>Icon Set</option>
                <option>Layout Design</option>
                <option>Notification Graphics</option>
                <option>Screens Static</option>
                <option>Screens Animated</option>
                <option>Wallpaper Static</option>
                <option>Wallpaper Animated</option>
                <option>Transition Animated</option>
                <option>Intro Animated</option>
                <option>Outro Animated</option>
                <option>Character Sheet</option>
                <option>Avatar Static</option>
                <option>Avatar Animated</option>
                <option>Reaction Graphic Static</option>
                <option>Reaction Graphic Animated</option>
                <option>Badge Icon</option>
                <option>2D Character Cutout</option>
                <option>2D Character Rigging</option>
                <option>Video Edit</option>
                <option>Brand Pack Static</option>
                <option>Brand Pack Animated</option>
                <option>Poster Design</option>
                <option>3D Model Grayscale</option>
                <option>3D Model Textured</option>
                <option>3D Model Rig</option>
                <option>3D Character Model</option>
                <option>3D Scene Creation</option>
                <option>3D Asset Grayscale</option>
                <option>3D Asset Textured</option>
                <option>Corporate Logo</option>
                <option>Corporate Poster</option>
                <option>Transition</option>
                <option>Thumbnail</option>
                <option>Merch Design</option>
                <option>Mockups</option>
                <option>Reels Edit</option>
                <option>Long Video Edits</option>
                <option>Tattoo Design</option>
            </select>

            <input type="hidden" name="work" id="workInput" required>

            <div id="selectedWorks" class="selected-works"></div>
        </div>

        <div class="form-group">
            <label>Work Type:</label>
            <select name="work_type" required>
                <option value="">Select Type</option>
                <option>New Easy</option>
                <option>New Hard</option>
                <option>Feedback Easy</option>
                <option>Feedback Hard</option>
                <option>Approve Need To Mail</option>
            </select>
        </div>

        <div class="form-group">
            <label>Work Amount:</label>
            <input type="number" name="amount" value="1" min="1" required>
        </div>

        <div class="form-group">
            <label>Allotted Time (in minutes):</label>
            <input type="number" id="timeInput" value="1" name="allotted_time" min="1" required>
            <div class="hint" id="timeHint">= 0 Hours 1 Minutes</div>
        </div>

        <button type="submit" id="submitBtn">Add Record</button>
    </form>

    <!-- Audio for notifications -->
    <audio id="taskAssignedSound" preload="auto">
        <source src="../assets/sounds/task_assigned.mp3" type="audio/mpeg">
    </audio>

    <script src="../js/admin_script.js"></script>
    <script src="../js/navbar.js"></script>
    <script>
        // AJAX form submission
        document.getElementById('taskForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Assigning...';

            // Hide previous messages
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';

            fetch('../../api/add_record.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        successMsg.style.display = 'block';

                        // Play notification sound
                        const audio = document.getElementById('taskAssignedSound');
                        audio.play().catch(e => console.log('Audio play failed:', e));

                        // Reset form
                        this.reset();
                        document.getElementById('selectedWorks').innerHTML = '';
                        document.getElementById('workInput').value = '';
                        
                        // Reset the selected works array (fix for bug where previous work persists)
                        if (typeof selected !== 'undefined') {
                            selected = [];
                        }

                        // Notify other pages about new task
                        const notificationData = {
                            timestamp: Date.now(),
                            designer: data.designer_name,
                            order_no: data.order_no,
                            work: data.work
                        };
                        localStorage.setItem('newTaskAssigned', JSON.stringify(notificationData));

                        // Also trigger storage event for immediate notification
                        window.dispatchEvent(new StorageEvent('storage', {
                            key: 'newTaskAssigned',
                            newValue: JSON.stringify(notificationData)
                        }));

                        // Auto-hide success message after 3 seconds
                        setTimeout(() => {
                            successMsg.style.display = 'none';
                        }, 3000);
                    } else {
                        errorMsg.textContent = data.error || 'Failed to assign task';
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
                    submitBtn.textContent = 'Add Record';
                });
        });

        // Listen for task completion notifications from records.php
        window.addEventListener('storage', function(e) {
            if (e.key === 'taskCompleted') {
                const taskData = JSON.parse(e.newValue);
                if (taskData) {
                    // Play completion sound
                    const audio = document.getElementById('taskAssignedSound');
                    audio.play().catch(e => console.log('Audio play failed:', e));

                    // Show notification
                    const successMsg = document.getElementById('successMessage');
                    successMsg.textContent = `✅ Task ${taskData.order_no} completed by ${taskData.designer_name}!`;
                    successMsg.style.display = 'block';

                    setTimeout(() => {
                        successMsg.style.display = 'none';
                    }, 3000);
                }
            }
        });
    </script>
    <script src="../../js/session_check.js"></script>
</body>

</html>
