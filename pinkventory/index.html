<?php
session_start([
    'cookie_lifetime' => 86400, // Set session cookie lifetime to 1 day
    'cookie_secure'   => true, // Ensure cookies are only sent over HTTPS
    'cookie_httponly' => true, // Prevent JavaScript access to session cookies
    'use_strict_mode' => true // Enable strict mode for sessions
]);

require_once 'connect.php'; // Include database connection

// Generate a CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mssge = "Account locked. Please try again after 1 minute.";
$errors = [];

// Determine the requested action (default to 'signin')
$action = isset($_GET['action']) ? $_GET['action'] : 'signin';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF protection: Check if the submitted token matches the session token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    if ($action == 'signin') {
        // Process admin or user login based on input fields
        $admin_username = filter_input(INPUT_POST, 'admin_username', FILTER_SANITIZE_STRING);
        $admin_password = filter_input(INPUT_POST, 'admin_password', FILTER_SANITIZE_STRING);

        if (isset($admin_username)) {
            // Admin login process
            $sql = "SELECT * FROM admin WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $admin_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();

                // Verify admin password
                if (password_verify($admin_password, $admin['password'])) {
                    $_SESSION['admin_username'] = $admin['username'];
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $error = "Incorrect admin username or password.";
                }
            } else {
                $error = "Admin account not found.";
            }
        } else {
            // User login process
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

            $sql = "SELECT *, TIMESTAMPDIFF(SECOND, last_failed_attempt, NOW()) AS time_since_last_fail FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Check if account is locked due to multiple failed attempts
                if ($user['failed_attempts'] >= 5 && $user['time_since_last_fail'] < 60) {
                    $error = $mssge;
                } else {
                    // Verify user password
                    if (password_verify($password, $user['password'])) {
                        // Reset failed login attempts upon successful login
                        $resetAttempts = "UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL WHERE email = ?";
                        $stmt = $conn->prepare($resetAttempts);
                        $stmt->bind_param("s", $email);
                        $stmt->execute();

                        $_SESSION['email'] = $user['email'];
                        header("Location: homepage.php");
                        exit();
                    } else {
                        // Increment failed login attempts
                        $failedAttempts = $user['failed_attempts'] + 1;
                        $updateAttempts = "UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW() WHERE email = ?";
                        $stmt = $conn->prepare($updateAttempts);
                        $stmt->bind_param("is", $failedAttempts, $email);
                        $stmt->execute();

                        // Show error message with remaining attempts
                        $error = $failedAttempts >= 5 ? $mssge : "Incorrect email or password. Attempts left: " . (5 - $failedAttempts);
                    }
                }
            } else {
                $error = "Account not found.";
            }
        }
    } elseif ($action == 'signup') {
        // Handle user signup
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $repeat_password = filter_input(INPUT_POST, 'repeat_password', FILTER_SANITIZE_STRING);
        $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);

        // Validate password strength
        if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
            $errors[] = "Password must be at least 8 characters long and include at least one uppercase letter and one number.";
        }

        // Ensure passwords match
        if ($password !== $repeat_password) {
            $errors[] = "Passwords do not match.";
        }

        // Check if email already exists
        $checkEmail = "SELECT email FROM users WHERE email = ?";
        $stmt = $conn->prepare($checkEmail);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Email already exists.";
        }

        // Insert new user into the database if there are no errors
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, fullname, password, dob, gender) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $email, $fullname, $hashed_password, $dob, $gender);
            if ($stmt->execute()) {
                header("Location: index.php?action=signin");
                exit();
            } else {
                $errors[] = "Error creating account.";
            }
        }
    } elseif ($action == 'logout') {
        // Destroy session and redirect to login page on logout
        session_destroy();
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $action == 'signin' ? 'Sign In' : ($action == 'signup' ? 'Sign Up' : 'Update Info'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <meta http-equiv="Content-Security-Policy" content="
">

    <script>
        function toggleForm(action) {
            document.querySelector('.page-container').classList.add('fade-out');
            setTimeout(function() {
                window.location.href = 'index.php?action=' + action;
            }, 500);
        }

        function showLoginForm(type) {
            const userForm = document.getElementById('userLoginForm');
            const adminForm = document.getElementById('adminLoginForm');
            const userBtn = document.getElementById('userBtn');
            const adminBtn = document.getElementById('adminBtn');

            if (type === 'user') {
                userForm.style.display = 'flex';
                adminForm.style.display = 'none';
                userBtn.classList.add('active');
                adminBtn.classList.remove('active');
            } else {
                userForm.style.display = 'none';
                adminForm.style.display = 'flex';
                adminBtn.classList.add('active');
                userBtn.classList.remove('active');
            }
        }
    </script>
</head>
<body>
    <div class="page-container">
        <?php if ($action == 'signin'): ?>
            <div class="left-panel">
                <h1>Welcome back!</h1>
                <p>To keep connected, please login with your personal info</p>
                <button onclick="toggleForm('signup')" id="signupBtn">Sign up</button>
            </div>
            <div class="right-panel">
            <div class="header">
                    <div class="brand">
                        <img src="BARBIE-removebg-preview 2.png" alt="Pinkventory Logo" class="logo">
                        <span class="brand-name">Pinkventory</span>
                    </div>
                </div>
                <h1 class ="loginH1">Log in to your account</h1>
                <p class="login-description">
        Pinkventory is a smart inventory management solution designed for effortless tracking, organization, and checkout. With real-time stock monitoring, easy product management, and insightful analytics, it helps streamline operations for businesses and individuals. Stay organized with Pinkventory!
    </p>
                <div class="login-type-dropdown">
                    <select id="loginType" onchange="showLoginForm(this.value)" class="login-dropdown">
                        <option value="user" selected>User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                
                <form method="post" action="index.php?action=signin" id="userLoginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Sign in</button>
                </form>

                <form method="post" action="index.php?action=signin" id="adminLoginForm" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="text" name="admin_username" placeholder="Admin Username" required>
                    <input type="password" name="admin_password" placeholder="Admin Password" required>
                    <button type="submit">Admin Sign in</button>
                    <p style="margin-top: 10px; text-align: center;">
                        <a href="Admin.php?action=create" class="create-admin-link">Create Admin Account</a>
                    </p>
                </form>
            </div>
        <?php elseif ($action == 'signup'): ?>
            <div class="right-panel">
            <div class="header">
                    <div class="brand">
                        <img src="BARBIE-removebg-preview 2.png" alt="Pinkventory Logo" class="logo">
                        <span class="brand-name">Pinkventory</span>
                    </div>
                </div>
                <h1>Create Account</h1>
                <?php if (!empty($errors)) foreach ($errors as $err) echo "<div class='alert alert-danger'>$err</div>"; ?>
                <form method="post" action="index.php?action=signup">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="email" name="email" placeholder="Your Email" required>
                    <input type="text" name="fullname" placeholder="Your Name" required>
                    <input type="password" name="password" placeholder="Create Password" required>
                    <input type="password" name="repeat_password" placeholder="Repeat Password" required>
                    <input type="date" name="dob" required>
                    <label>
                        <input type="radio" name="gender" value="Male" required> Male
                    </label>
                    <label>
                        <input type="radio" name="gender" value="Female" required> Female
                    </label>
                    <button type="submit" class="create-account">Sign Up</button>
                </form>
            </div>
            <div class="left-panel">
                <h1>Hello, Friend!</h1>
                <p>Enter your personal details and start your journey with us</p>
                <button onclick="toggleForm('signin')" id="signupBtn">Sign In</button>
            </div>
        <?php endif; ?>
    </div>


    <script> 
        const express = require('express');
            const helmet = require('helmet');

            const app = express();

            app.use(
            helmet.contentSecurityPolicy({
                directives: {
                defaultSrc: ["'self'"],
                scriptSrc: ["'self'", "'unsafe-inline'", "https://trusted.cdn.com"],
                styleSrc: ["'self'", "'unsafe-inline'"],
                imgSrc: ["'self'", "data:"],
                fontSrc: ["'self'", "https://fonts.gstatic.com"],
                frameSrc: ["'none'"],
                },
            })
            );

            app.listen(3000, () => console.log('Server running on port 3000'));

    </script>

<script> 

const express = require('express');
const helmet = require('helmet');

const app = express();
app.use(
  helmet.contentSecurityPolicy({
    directives: {
      defaultSrc: ["'self'"],
      scriptSrc: ["'self'", "https://trusted.cdn.com"],
      styleSrc: ["'self'", "'unsafe-inline'"],
      imgSrc: ["'self'", "data:"],
      fontSrc: ["'self'", "https://fonts.gstatic.com"],
    },
  })
);

app.listen(3000, () => console.log('Server running on port 3000'));

</script>


</body>
</html>