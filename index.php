<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/pages/admin_dashboard.php");
    } elseif ($_SESSION['role'] === 'employee') {
        header("Location: employee/pages/employee_dashboard.php");
    }
    exit;
}

$error = '';

// Login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['LAST_ACTIVITY'] = time();

            if ($user['role'] === 'admin') {
                header("Location: admin/pages/admin_dashboard.php");
            } else {
                header("Location: employee/pages/employee_dashboard.php");
            }
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<!-- Coding By CodingNepal - codingnepalweb.com -->
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="assets/images/fav_icon.png" />
    <!-- ===== Iconscout CSS ===== -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    <!-- ===== CSS ===== -->
    <link rel="stylesheet" href="css/login_stylesheet.css" />
    <title>Login & Registration Form</title>
  </head>
  <body>
    <div class="container">
      <div class="forms">

        <!-- Login Form -->
        <div class="form login">
          <span class="title">Login</span>
          <?php if (!empty($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
          <form method="POST">
            <div class="input-field">
              <input type="text" name="username" placeholder="Enter your username" required />
              <i class="uil uil-user icon"></i>
            </div>
            <div class="input-field">
              <input type="password" name="password" class="password" placeholder="Enter your password" required />
              <i class="uil uil-lock icon"></i>
              <i class="uil uil-eye-slash showHidePw"></i>
            </div>
            <div class="checkbox-text">
              <div class="checkbox-content">
                <input type="checkbox" id="logCheck" />
                <label for="logCheck" class="text">Remember me</label>
              </div>
              <a href="#" class="text">Forgot password?</a>
            </div>
            <div class="input-field button">
              <input type="submit" value="Login" />
            </div>
          </form>
          <div class="login-signup">
            <span class="text"
              >Not a member?
              <a href="#" class="text signup-link">Signup Now</a>
            </span>
          </div>
        </div>

        <!-- Registration Form -->
        <div class="form signup">
          <span class="title">Registration</span>
          <form action="#">
            <div class="input-field">
              <input type="text" placeholder="Enter your name" required />
              <i class="uil uil-user"></i>
            </div>
            <div class="input-field">
              <input type="text" placeholder="Enter your email" required />
              <i class="uil uil-envelope icon"></i>
            </div>
            <div class="input-field">
              <input type="password" class="password" placeholder="Create a password" required />
              <i class="uil uil-lock icon"></i>
              <i class="uil uil-eye-slash showHidePw"></i>
            </div>
            <div class="input-field">
              <input type="password" class="password" placeholder="Confirm a password" required />
              <i class="uil uil-lock icon"></i>
              <i class="uil uil-eye-slash showHidePw"></i>
            </div>
            <div class="checkbox-text">
              <div class="checkbox-content">
                <input type="checkbox" id="termCon" />
                <label for="termCon" class="text">I accepted all terms and conditions</label>
              </div>
            </div>
            <div class="input-field button">
              <input type="button" value="Signup" />
            </div>
          </form>
          <div class="login-signup">
            <span class="text"
              >Already a member?
              <a href="#" class="text login-link">Login Now</a>
            </span>
          </div>
        </div>

      </div>
    </div>
    <script src="js/login.js"></script>
  </body>
</html>