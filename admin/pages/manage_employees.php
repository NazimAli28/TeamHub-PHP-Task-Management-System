<?php
session_start();
include '../includes/verify_admin.php';
include '../../config/db.php';

// Handle form submissions
$message = '';
$error = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            // Add new employee
            $username = trim($_POST['username']);
            $name = trim($_POST['name']);
            $password = $_POST['password'];
            $profile_picture = null;

            // Validate input
            if (empty($username) || empty($name) || empty($password)) {
                $error = 'All fields are required.';
            } else {
                // Check if username exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Username already exists.';
                } else {
                    // Handle profile picture upload
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['profile_picture']['tmp_name'];
                        $file_size = $_FILES['profile_picture']['size'];
                        $file_type = $_FILES['profile_picture']['type'];

                        // Validate size (max 5MB)
                        if ($file_size > 5 * 1024 * 1024) {
                            $error = 'File size too large. Max 5MB allowed.';
                        } elseif (!in_array($file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                            $error = 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.';
                        } else {
                            // Generate unique filename
                            $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                            $file_name = uniqid('profile_', true) . '.' . $file_ext;
                            $upload_dir = '../../assets/images/';
                            $file_path = $upload_dir . $file_name;

                            // Create directory if it doesn't exist
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }

                            if (move_uploaded_file($file_tmp, $file_path)) {
                                $profile_picture = '../../assets/images/' . $file_name;
                            } else {
                                $error = 'Failed to save uploaded file.';
                            }
                        }
                    }

                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert employee
                    $stmt = $conn->prepare("INSERT INTO users (username, name, password, role, profile_picture) VALUES (?, ?, ?, 'employee', ?)");
                    $stmt->bind_param("ssss", $username, $name, $hashed_password, $profile_picture);

                    if ($stmt->execute()) {
                        $_SESSION['message'] = 'Employee added successfully.';
                        header('Location: manage_employees.php');
                        exit;
                    } else {
                        $error = 'Failed to add employee: ' . $conn->error;
                    }
                }
                $stmt->close();
            }
        } elseif ($action === 'edit') {
            // Edit existing employee
            $id = intval($_POST['id']);
            $username = trim($_POST['username']);
            $name = trim($_POST['name']);
            $password = $_POST['password'];
            $new_profile_picture = null;
            $old_profile_picture = null;

            if (empty($username) || empty($name)) {
                $error = 'Username and name are required.';
            } else {
                // Fetch current profile picture
                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ? AND role = 'employee'");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $old_profile_picture = $row['profile_picture'];
                    // Only consider for deletion if it's a string path, not BLOB
                    if (!is_string($old_profile_picture) || strlen($old_profile_picture) > 10000 || strpos($old_profile_picture, '../../assets/') !== 0) {
                        $old_profile_picture = null;
                    } else {
                        // Convert relative path to absolute for file operations
                        $old_profile_picture = dirname(__DIR__, 2) . '/assets/images/' . basename($old_profile_picture);
                    }
                }
                $stmt->close();

                // Check if username exists for other employees
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? AND role = 'employee'");
                $stmt->bind_param("si", $username, $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Username already exists for another employee.';
                } else {
                    // Handle profile picture upload
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['profile_picture']['tmp_name'];
                        $file_size = $_FILES['profile_picture']['size'];
                        $file_type = $_FILES['profile_picture']['type'];

                        // Validate size (max 5MB)
                        if ($file_size > 5 * 1024 * 1024) {
                            $error = 'File size too large. Max 5MB allowed.';
                        } elseif (!in_array($file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                            $error = 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.';
                        } else {
                            // Generate unique filename
                            $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                            $file_name = uniqid('profile_', true) . '.' . $file_ext;
                            $upload_dir = '../../assets/images/';
                            $file_path = $upload_dir . $file_name;

                            // Create directory if it doesn't exist
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }

                            if (move_uploaded_file($file_tmp, $file_path)) {
                                $new_profile_picture = '../../assets/images/' . $file_name;

                                // Delete old profile picture if exists
                                if ($old_profile_picture && file_exists($old_profile_picture)) {
                                    unlink($old_profile_picture);
                                }
                            } else {
                                $error = 'Failed to save uploaded file.';
                            }
                        }
                    }

                    if (empty($error)) {
                        // Hash password if provided
                        if (!empty($password)) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            if ($new_profile_picture !== null) {
                                $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, password = ?, profile_picture = ? WHERE id = ? AND role = 'employee'");
                                $stmt->bind_param("ssssi", $username, $name, $hashed_password, $new_profile_picture, $id);
                            } else {
                                $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, password = ? WHERE id = ? AND role = 'employee'");
                                $stmt->bind_param("sssi", $username, $name, $hashed_password, $id);
                            }
                        } else {
                            if ($new_profile_picture !== null) {
                                $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, profile_picture = ? WHERE id = ? AND role = 'employee'");
                                $stmt->bind_param("sssi", $username, $name, $new_profile_picture, $id);
                            } else {
                                $stmt = $conn->prepare("UPDATE users SET username = ?, name = ? WHERE id = ? AND role = 'employee'");
                                $stmt->bind_param("ssi", $username, $name, $id);
                            }
                        }

                        if ($stmt->execute()) {
                            $_SESSION['message'] = 'Employee updated successfully.';
                            header('Location: manage_employees.php');
                            exit;
                        } else {
                            $error = 'Failed to update employee: ' . $conn->error;
                            // If update failed and new file was uploaded, delete the new file
                            if ($new_profile_picture && file_exists($new_profile_picture)) {
                                unlink($new_profile_picture);
                            }
                        }
                        $stmt->close();
                    }
                }
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            // Delete employee
            $id = intval($_POST['id']);

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $_SESSION['message'] = 'Employee deleted successfully.';
                header('Location: manage_employees.php');
                exit;
            } else {
                $error = 'Failed to delete employee: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get all employees
$employees = $conn->query("SELECT id, username, name FROM users WHERE role = 'employee' ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png" />
    <title>Manage Employees - TeamHub</title>
    <link rel="stylesheet" href="../css/styles.css?v=1.2" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
        }
        .container {
            margin: 0 auto;
            background: var(--card);
            padding: 30px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 1;
        }
        h1 {
            font-size: 2rem;
            color: var(--text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }
        h1 i {
            color: var(--primary-color);
        }
        .message {
            padding: 15px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-weight: 600;
        }
        .success {
            background-color: rgba(24, 211, 49, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        .error {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--error);
            border: 1px solid var(--error);
        }
        form {
            margin-bottom: 30px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text);
        }
        input[type="text"],
        input[type="password"],
        input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 16px;
            box-sizing: border-box;
            background: var(--input-bg);
            color: var(--text);
            transition: border-color var(--transition);
            margin-bottom: 20px;
        }
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(241, 89, 42, 0.1);
        }
        button {
            background: var(--button);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background var(--transition);
        }
        button:hover {
            background: var(--button-hover);
        }
        .employee-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .employee-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--bg-secondary);
            transition: box-shadow var(--transition);
        }
        .employee-item:hover {
            box-shadow: var(--shadow-md);
        }
        .employee-info {
            font-weight: 600;
            color: var(--text);
        }
        .employee-username {
            color: var(--text-secondary);
            font-weight: 400;
            margin-left: 8px;
        }
        .btn-secondary,
        .btn-danger {
            padding: 8px 16px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background var(--transition);
            margin-left: 8px;
        }
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        .btn-danger:hover {
            background: var(--danger-hover);
        }
        @media (max-width: 600px) {
            .employee-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .employee-actions {
                width: 100%;
                display: flex;
                justify-content: flex-start;
            }
            .btn-secondary,
            .btn-danger {
                margin-left: 0;
                margin-right: 10px;
                flex: 1;
            }
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 25px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            position: relative;
            border: 1px solid var(--primary-color);
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text);
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: color var(--transition);
            padding: 4px 12px;
        }
        .modal-close:hover {
            color: var(--primary-color);
        }
        /* Modal form inputs */
        .modal-body label {
            margin-top: 12px;
            font-weight: 600;
            color: var(--text);
        }
        .modal-body input[type="text"],
        .modal-body input[type="password"],
        .modal-body input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 16px;
            box-sizing: border-box;
            background: var(--input-bg);
            color: var(--text);
            transition: border-color var(--transition);
        }
        .modal-body input[type="text"]:focus,
        .modal-body input[type="password"]:focus,
        .modal-body input[type="file"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(241, 89, 42, 0.1);
        }
        .modal-body button {
            margin-top: 20px;
            background: var(--button);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background var(--transition);
            width: 100%;
        }
        .modal-body button:hover {
            background: var(--button-hover);
        }
    </style>
</head>
<body>
<?php include '../includes/navbar_admin.php'; ?>

<div class="container">
    <h1><i class="fas fa-users"></i> Manage Employees</h1>

    <?php if ($message): ?>
        <div class="message success" id="messageBox"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error" id="errorBox"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add Employee Form -->
    <form method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="action" value="add" />
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required />

        <label for="name">Full Name:</label>
        <input type="text" name="name" id="name" required />

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required />

        <label for="profile_picture">Profile Picture:</label>
        <small>Recommended size: 200x200 pixels</small>
        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" />

        <button type="submit">Add Employee</button>
    </form>

    <!-- Employee List -->
    <div class="employee-list">
        <h2>Current Employees</h2>
        <?php while ($employee = $employees->fetch_assoc()): ?>
            <div class="employee-item">
                <div class="employee-info">
                    <?= htmlspecialchars($employee['name']) ?>
                    <span class="employee-username">(<?= htmlspecialchars($employee['username']) ?>)</span>
                </div>
                <div class="employee-actions">
                    <button onclick="editEmployee(<?= $employee['id'] ?>, '<?= htmlspecialchars($employee['username']) ?>', '<?= htmlspecialchars($employee['name']) ?>')" class="btn-secondary">Edit</button>
                    <button onclick="deleteEmployee(<?= $employee['id'] ?>, '<?= htmlspecialchars($employee['name']) ?>')" class="btn-danger">Delete</button>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Edit Employee Modal/Form -->
    <div id="editForm" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Employee</h2>
                <button class="modal-close" onclick="cancelEdit()" aria-label="Close edit form">&times;</button>
            </div>
            <div class="modal-body">
        <form method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="action" value="edit" />
            <input type="hidden" name="id" id="edit_id" />

            <label for="edit_username">Username:</label>
            <input type="text" name="username" id="edit_username" required />

            <label for="edit_name">Full Name:</label>
            <input type="text" name="name" id="edit_name" required />

            <label for="edit_password">New Password (leave empty to keep current):</label>
            <input type="password" name="password" id="edit_password" />

            <label for="edit_profile_picture">New Profile Picture:</label>
            <small>Recommended size: 200x200 pixels</small>
            <input type="file" name="profile_picture" id="edit_profile_picture" accept="image/*" />

            <button type="submit">Update Employee</button>
        </form>
    </div>
</div>

<script>
function editEmployee(id, username, name) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_password').value = '';
    document.getElementById('edit_profile_picture').value = '';
    document.getElementById('editForm').style.display = 'flex';
    // Remove scroll to top to avoid scrolling
    // window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancelEdit() {
    document.getElementById('editForm').style.display = 'none';
}

function deleteEmployee(id, name) {
    if (confirm(`Are you sure you want to delete employee "${name}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete" />
            <input type="hidden" name="id" value="${id}" />
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-hide confirmation and error messages after 5 seconds
window.onload = function() {
    const messageBox = document.getElementById('messageBox');
    const errorBox = document.getElementById('errorBox');
    if (messageBox) {
        setTimeout(() => {
            messageBox.style.display = 'none';
        }, 5000);
    }
    if (errorBox) {
        setTimeout(() => {
            errorBox.style.display = 'none';
        }, 5000);
    }
};
</script>

</body>
</html>
