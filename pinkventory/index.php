<?php
require_once 'bootstrap.php';

const MAX_FAILED_ATTEMPTS = 5;
const LOCKOUT_SECONDS     = 60;

$mssge = "Account locked. Please try again after 1 minute.";
$errors = [];
$success = isset($_GET['registered']) ? 'Account created. You can now sign in.' : null;

// Determine the requested action (default to 'signin')
$action = $_GET['action'] ?? 'signin';
if ($action === 'logout') {
    redirect(logout_url());
}
if (!in_array($action, ['signin', 'signup'], true)) {
    $action = 'signin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF is verified in bootstrap.php for every POST request.

    if ($action === 'signin') {
        if (isset($_POST['admin_username'])) {
            // Admin login process
            $admin_username = trim((string) $_POST['admin_username']);
            $admin_password = (string) ($_POST['admin_password'] ?? '');

            $stmt = $conn->prepare("SELECT username, password FROM admin WHERE username = ?");
            $stmt->bind_param("s", $admin_username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();

            if ($admin && password_verify($admin_password, $admin['password'])) {
                login_as('admin_username', $admin['username']);
                redirect('admin_dashboard.php');
            }
            $error = "Incorrect admin username or password.";
        } else {
            // User login process
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $sql = "SELECT email, password, failed_attempts, TIMESTAMPDIFF(SECOND, last_failed_attempt, NOW()) AS time_since_last_fail FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                $error = "Incorrect email or password.";
            } else {
                $attempts = (int) $user['failed_attempts'];
                $sinceLastFail = $user['time_since_last_fail'];
                $locked = $attempts >= MAX_FAILED_ATTEMPTS && $sinceLastFail !== null && $sinceLastFail < LOCKOUT_SECONDS;

                if ($locked) {
                    // Check if account is locked due to multiple failed attempts
                    $error = $mssge;
                } elseif (password_verify($password, $user['password'])) {
                    // Reset failed login attempts upon successful login
                    $stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();

                    login_as('email', $user['email']);
                    redirect('homepage.php');
                } else {
                    // A finished lockout gives the user a fresh set of attempts.
                    $failedAttempts = ($attempts >= MAX_FAILED_ATTEMPTS ? 0 : $attempts) + 1;
                    $stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW() WHERE email = ?");
                    $stmt->bind_param("is", $failedAttempts, $email);
                    $stmt->execute();

                    // Show error message with remaining attempts
                    $error = $failedAttempts >= MAX_FAILED_ATTEMPTS
                        ? $mssge
                        : "Incorrect email or password. Attempts left: " . (MAX_FAILED_ATTEMPTS - $failedAttempts);
                }
            }
        }
    } elseif ($action === 'signup') {
        // Handle user signup
        $email = trim((string) ($_POST['email'] ?? ''));
        $fullname = trim((string) ($_POST['fullname'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $repeat_password = (string) ($_POST['repeat_password'] ?? '');
        $dob = (string) ($_POST['dob'] ?? '');
        $gender = (string) ($_POST['gender'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        if ($fullname === '' || mb_strlen($fullname) > 100) {
            $errors[] = "Please enter your name (up to 100 characters).";
        }
        $dobDate = DateTime::createFromFormat('!Y-m-d', $dob);
        if (!$dobDate || $dobDate->format('Y-m-d') !== $dob || $dobDate > new DateTime()) {
            $errors[] = "Please enter a valid date of birth.";
        }
        if (!in_array($gender, ['Male', 'Female'], true)) {
            $errors[] = "Please select a gender.";
        }

        // Validate password strength
        if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
            $errors[] = "Password must be at least 8 characters long and include at least one uppercase letter and one number.";
        }

        // Ensure passwords match
        if ($password !== $repeat_password) {
            $errors[] = "Passwords do not match.";
        }

        // Check if email already exists
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "Email already exists.";
            }
        }

        // Insert new user into the database if there are no errors
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, fullname, password, dob, gender) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $email, $fullname, $hashed_password, $dob, $gender);
            $stmt->execute();
            redirect('index.php?action=signin&registered=1');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $action == 'signin' ? 'Sign In' : 'Sign Up'; ?></title>
    <link rel="stylesheet" href="<?php echo asset('style.css'); ?>">

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

            if (type === 'user') {
                userForm.style.display = 'flex';
                adminForm.style.display = 'none';
            } else {
                userForm.style.display = 'none';
                adminForm.style.display = 'flex';
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
                        <img src="pinkventory-logo.png" alt="Pinkventory Logo" class="logo">
                        <span class="brand-name">Pinkventory</span>
                    </div>
                </div>
                <h1 class ="loginH1">Log in to your account</h1>
                <p class="login-description">
        Pinkventory is a smart inventory management solution designed for effortless tracking, organization, and checkout. With real-time stock monitoring, easy product management, and insightful analytics, it helps streamline operations for businesses and individuals. Stay organized with Pinkventory!
    </p>
                <?php $adminSelected = isset($_POST['admin_username']); ?>
                <div class="login-type-dropdown">
                    <select id="loginType" onchange="showLoginForm(this.value)" class="login-dropdown">
                        <option value="user" <?php echo $adminSelected ? '' : 'selected'; ?>>User</option>
                        <option value="admin" <?php echo $adminSelected ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <?php if ($success) echo "<div class='alert alert-success'>" . e($success) . "</div>"; ?>
                <?php if (isset($error)) echo "<div class='alert alert-danger'>" . e($error) . "</div>"; ?>

                <form method="post" action="index.php?action=signin" id="userLoginForm" <?php echo $adminSelected ? 'style="display: none;"' : ''; ?>>
                    <?php echo csrf_field(); ?>
                    <input type="email" name="email" placeholder="Email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Sign in</button>
                </form>

                <form method="post" action="index.php?action=signin" id="adminLoginForm" <?php echo $adminSelected ? '' : 'style="display: none;"'; ?>>
                    <?php echo csrf_field(); ?>
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
                        <img src="pinkventory-logo.png" alt="Pinkventory Logo" class="logo">
                        <span class="brand-name">Pinkventory</span>
                    </div>
                </div>
                <h1>Create Account</h1>
                <?php foreach ($errors as $err) echo "<div class='alert alert-danger'>" . e($err) . "</div>"; ?>
                <form method="post" action="index.php?action=signup">
                    <?php echo csrf_field(); ?>
                    <input type="email" name="email" placeholder="Your Email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                    <input type="text" name="fullname" placeholder="Your Name" value="<?php echo e($_POST['fullname'] ?? ''); ?>" maxlength="100" required>
                    <input type="password" name="password" placeholder="Create Password" required>
                    <input type="password" name="repeat_password" placeholder="Repeat Password" required>
                    <input type="date" name="dob" value="<?php echo e($_POST['dob'] ?? ''); ?>" required>
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

</body>
</html>
