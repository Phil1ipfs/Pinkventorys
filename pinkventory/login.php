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

// Already signed in? Go straight to the right dashboard.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (is_user() || is_admin())) {
    redirect(home_url());
}

// Only offer "create admin" while the site has no admin yet (see Admin.php).
$needsFirstAdmin = (int) $conn->query("SELECT COUNT(*) FROM admin")->fetch_row()[0] === 0;

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
            redirect('login.php?registered=1');
        }
    }
}
?>
<?php
$isSignup = $action === 'signup';
$adminSelected = isset($_POST['admin_username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isSignup ? 'Create account' : 'Log in'; ?> · Pinkventory</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap">
    <link rel="stylesheet" href="<?php echo asset('assets/css/auth.css'); ?>">
    <script src="<?php echo asset('assets/js/auth.js'); ?>" defer></script>
</head>
<body>
<div class="auth">
    <aside class="auth-aside">
        <a href="index.php" class="brand-link">
            <img src="assets/img/logo.png" alt="">
            Pinkventory
        </a>

        <div class="aside-body">
            <?php if ($isSignup): ?>
                <p class="eyebrow">Smart inventory solution</p>
                <h2>Welcome to Pinkventory!</h2>
                <p>Create your account to browse products, build your cart and check out with ease.</p>
            <?php else: ?>
                <p class="eyebrow">Welcome back</p>
                <h2>Good to see you again!</h2>
                <p>Log in to your Pinkventory account and pick up right where you left off.</p>
            <?php endif; ?>
        </div>

        <div class="illustration">
            <img src="assets/img/<?php echo $isSignup ? 'illus-signup.svg' : 'illus-signin.svg'; ?>" alt="" width="340" height="250">
        </div>

        <ul class="features">
            <?php if ($isSignup): ?>
                <li><?php echo icon('package'); ?> Real-time stock</li>
                <li><?php echo icon('shopping-cart'); ?> Easy checkout</li>
                <li><?php echo icon('smartphone'); ?> Shop anywhere</li>
            <?php else: ?>
                <li><?php echo icon('shield-check'); ?> Secure &amp; reliable</li>
                <li><?php echo icon('zap'); ?> Easy access</li>
                <li><?php echo icon('smartphone'); ?> Works on any device</li>
            <?php endif; ?>
        </ul>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <a href="index.php" class="brand-link mobile-brand">
                <img src="assets/img/logo.png" alt="">
                Pinkventory
            </a>

            <?php if (!$isSignup): ?>
                <p class="card-switch">Don't have an account?<a href="login.php?action=signup">Sign up <?php echo icon('arrow-right'); ?></a></p>
                <h1>Log in to your <span class="accent">account</span></h1>
                <p class="lead">Enter your details below to continue.</p>

                <div class="role-switch" role="group" aria-label="Log in as">
                    <button type="button" data-role="user" aria-pressed="<?php echo $adminSelected ? 'false' : 'true'; ?>"><?php echo icon('user'); ?> Customer</button>
                    <button type="button" data-role="admin" aria-pressed="<?php echo $adminSelected ? 'true' : 'false'; ?>"><?php echo icon('shield-check'); ?> Admin</button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="status"><?php echo icon('check-circle-2'); ?><span><?php echo e($success); ?></span></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo icon('alert-circle'); ?><span><?php echo e($error); ?></span></div>
                <?php endif; ?>

                <form method="post" action="login.php?action=signin" class="form" id="userLoginForm" <?php echo $adminSelected ? 'hidden' : ''; ?>>
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="email"><?php echo icon('mail'); ?> Email address <span class="req" aria-hidden="true">*</span></label>
                        <div class="input">
                            <?php echo icon('mail'); ?>
                            <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password"><?php echo icon('lock'); ?> Password <span class="req" aria-hidden="true">*</span></label>
                        <div class="input has-toggle">
                            <?php echo icon('lock'); ?>
                            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false"><?php echo icon('eye', 'icon-show') . icon('eye-off', 'icon-hide'); ?></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary"><?php echo icon('log-in'); ?> Log in <?php echo icon('arrow-right'); ?></button>
                </form>

                <form method="post" action="login.php?action=signin" class="form" id="adminLoginForm" <?php echo $adminSelected ? '' : 'hidden'; ?>>
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="admin_username"><?php echo icon('at-sign'); ?> Admin username <span class="req" aria-hidden="true">*</span></label>
                        <div class="input">
                            <?php echo icon('shield-check'); ?>
                            <input type="text" id="admin_username" name="admin_username" placeholder="admin_username" autocomplete="username" value="<?php echo e($_POST['admin_username'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="admin_password"><?php echo icon('lock'); ?> Password <span class="req" aria-hidden="true">*</span></label>
                        <div class="input has-toggle">
                            <?php echo icon('lock'); ?>
                            <input type="password" id="admin_password" name="admin_password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false"><?php echo icon('eye', 'icon-show') . icon('eye-off', 'icon-hide'); ?></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary"><?php echo icon('shield-check'); ?> Log in as admin <?php echo icon('arrow-right'); ?></button>
                    <?php if ($needsFirstAdmin): ?>
                        <p class="switch-line">Setting up? <a href="Admin.php">Create the first admin account</a></p>
                    <?php endif; ?>
                </form>

                <p class="switch-line">Don't have an account? <a href="login.php?action=signup">Sign up</a></p>

            <?php else: ?>
                <p class="card-switch">Already have an account?<a href="login.php">Log in <?php echo icon('arrow-right'); ?></a></p>
                <h1>Create <span class="accent">Account</span></h1>
                <p class="lead">Join Pinkventory and start shopping with us.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo icon('alert-circle'); ?>
                        <ul><?php foreach ($errors as $err) echo '<li>' . e($err) . '</li>'; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php?action=signup" class="form" id="signupForm">
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="email"><?php echo icon('mail'); ?> Your email address <span class="req" aria-hidden="true">*</span></label>
                        <div class="input">
                            <?php echo icon('mail'); ?>
                            <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="fullname"><?php echo icon('user'); ?> Your name <span class="req" aria-hidden="true">*</span></label>
                        <div class="input">
                            <?php echo icon('user'); ?>
                            <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" autocomplete="name" maxlength="100" value="<?php echo e($_POST['fullname'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="password"><?php echo icon('lock'); ?> Create password <span class="req" aria-hidden="true">*</span></label>
                            <div class="input has-toggle">
                                <?php echo icon('lock'); ?>
                                <input type="password" id="password" name="password" placeholder="Create password" autocomplete="new-password" aria-describedby="passwordRules" required>
                                <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false"><?php echo icon('eye', 'icon-show') . icon('eye-off', 'icon-hide'); ?></button>
                            </div>
                        </div>
                        <div class="field">
                            <label for="repeat_password"><?php echo icon('lock'); ?> Repeat password <span class="req" aria-hidden="true">*</span></label>
                            <div class="input has-toggle">
                                <?php echo icon('lock'); ?>
                                <input type="password" id="repeat_password" name="repeat_password" placeholder="Repeat password" autocomplete="new-password" aria-describedby="matchHint" required>
                                <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false"><?php echo icon('eye', 'icon-show') . icon('eye-off', 'icon-hide'); ?></button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <ul class="checklist" id="passwordRules" data-rules-for="password">
                            <li data-rule="length">8+ characters</li>
                            <li data-rule="upper">One uppercase letter</li>
                            <li data-rule="number">One number</li>
                        </ul>
                        <p class="hint" id="matchHint" aria-live="polite"></p>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="dob"><?php echo icon('calendar'); ?> Date of birth <span class="req" aria-hidden="true">*</span></label>
                            <div class="input">
                                <?php echo icon('calendar'); ?>
                                <input type="date" id="dob" name="dob" max="<?php echo date('Y-m-d'); ?>" value="<?php echo e($_POST['dob'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <fieldset class="field">
                            <legend class="label"><?php echo icon('users'); ?> Gender <span class="req" aria-hidden="true">*</span></legend>
                            <div class="pills">
                                <?php foreach (['Male', 'Female'] as $g): ?>
                                    <label class="pill">
                                        <input type="radio" name="gender" value="<?php echo $g; ?>" <?php echo ($_POST['gender'] ?? '') === $g ? 'checked' : ''; ?> required>
                                        <span><?php echo $g; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    </div>
                    <button type="submit" class="btn-primary"><?php echo icon('user-plus'); ?> Sign up <?php echo icon('arrow-right'); ?></button>
                </form>

                <p class="switch-line">Already have an account? <a href="login.php">Log in</a></p>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
