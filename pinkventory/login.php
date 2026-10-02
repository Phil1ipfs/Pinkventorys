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
    <title><?php echo $isSignup ? 'Create account' : 'Sign in'; ?> · Pinkventory</title>
    <link rel="icon" type="image/png" href="pinkventory-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo asset('auth.css'); ?>">
    <script src="https://unpkg.com/lucide@0.359.0/dist/umd/lucide.min.js"></script>
</head>
<body>
<div class="auth">
    <aside class="auth-aside">
        <a href="index.php" class="brand-link">
            <img src="pinkventory-logo.png" alt="">
            Pinkventory
        </a>

        <div class="aside-body">
            <?php if ($isSignup): ?>
                <h2>Hello, friend!</h2>
                <p>Create your account and start tracking stock, sales and customers in one tidy place.</p>
            <?php else: ?>
                <h2>Welcome back!</h2>
                <p>Sign in to keep your shelves stocked and your checkout moving.</p>
            <?php endif; ?>
            <ul class="perks">
                <li><span class="perk-icon"><i data-lucide="package"></i></span> Real-time stock tracking</li>
                <li><span class="perk-icon"><i data-lucide="scan-barcode"></i></span> Fast barcode checkout</li>
                <li><span class="perk-icon"><i data-lucide="bell-ring"></i></span> Low-stock alerts before you run out</li>
            </ul>
        </div>

        <div class="aside-foot">
            <a href="index.php"><i data-lucide="arrow-left"></i> Back to home</a>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <a href="index.php" class="brand-link mobile-brand">
                <img src="pinkventory-logo.png" alt="">
                Pinkventory
            </a>

            <nav class="tabs" aria-label="Account">
                <a href="login.php" <?php echo $isSignup ? '' : 'aria-current="page"'; ?>>Sign in</a>
                <a href="login.php?action=signup" <?php echo $isSignup ? 'aria-current="page"' : ''; ?>>Create account</a>
            </nav>

            <?php if (!$isSignup): ?>
                <h1>Sign in to your account</h1>
                <p class="lead">Choose how you're signing in, then enter your details.</p>

                <div class="role-switch" role="group" aria-label="Sign in as">
                    <button type="button" data-role="user" aria-pressed="<?php echo $adminSelected ? 'false' : 'true'; ?>"><i data-lucide="user"></i> User</button>
                    <button type="button" data-role="admin" aria-pressed="<?php echo $adminSelected ? 'true' : 'false'; ?>"><i data-lucide="shield-check"></i> Admin</button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="status"><i data-lucide="check-circle-2"></i><span><?php echo e($success); ?></span></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert"><i data-lucide="alert-circle"></i><span><?php echo e($error); ?></span></div>
                <?php endif; ?>

                <form method="post" action="login.php?action=signin" class="form" id="userLoginForm" <?php echo $adminSelected ? 'hidden' : ''; ?>>
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="email">Email</label>
                        <div class="input">
                            <i data-lucide="mail"></i>
                            <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input has-toggle">
                            <i data-lucide="lock"></i>
                            <input type="password" id="password" name="password" placeholder="Your password" autocomplete="current-password" required>
                            <button type="button" class="toggle-pass" aria-label="Show password"><i data-lucide="eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Sign in <i data-lucide="arrow-right"></i></button>
                </form>

                <form method="post" action="login.php?action=signin" class="form" id="adminLoginForm" <?php echo $adminSelected ? '' : 'hidden'; ?>>
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="admin_username">Admin username</label>
                        <div class="input">
                            <i data-lucide="shield-check"></i>
                            <input type="text" id="admin_username" name="admin_username" placeholder="admin_username" autocomplete="username" value="<?php echo e($_POST['admin_username'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="admin_password">Password</label>
                        <div class="input has-toggle">
                            <i data-lucide="lock"></i>
                            <input type="password" id="admin_password" name="admin_password" placeholder="Admin password" autocomplete="current-password" required>
                            <button type="button" class="toggle-pass" aria-label="Show password"><i data-lucide="eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Sign in as admin <i data-lucide="arrow-right"></i></button>
                    <?php if ($needsFirstAdmin): ?>
                        <p class="switch-line">Setting up? <a href="Admin.php">Create the first admin account</a></p>
                    <?php endif; ?>
                </form>

                <p class="switch-line">New to Pinkventory? <a href="login.php?action=signup">Create an account</a></p>

            <?php else: ?>
                <h1>Create your account</h1>
                <p class="lead">It only takes a minute.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i data-lucide="alert-circle"></i>
                        <ul><?php foreach ($errors as $err) echo '<li>' . e($err) . '</li>'; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php?action=signup" class="form" id="signupForm">
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="fullname">Full name</label>
                        <div class="input">
                            <i data-lucide="user"></i>
                            <input type="text" id="fullname" name="fullname" placeholder="Jane Doe" autocomplete="name" maxlength="100" value="<?php echo e($_POST['fullname'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <div class="input">
                            <i data-lucide="mail"></i>
                            <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="dob">Date of birth</label>
                            <div class="input">
                                <i data-lucide="calendar"></i>
                                <input type="date" id="dob" name="dob" max="<?php echo date('Y-m-d'); ?>" value="<?php echo e($_POST['dob'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="field">
                            <span class="label" id="genderLabel">Gender</span>
                            <div class="pills" role="radiogroup" aria-labelledby="genderLabel">
                                <?php foreach (['Male', 'Female'] as $g): ?>
                                    <label class="pill">
                                        <input type="radio" name="gender" value="<?php echo $g; ?>" <?php echo ($_POST['gender'] ?? '') === $g ? 'checked' : ''; ?> required>
                                        <span><?php echo $g; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input has-toggle">
                            <i data-lucide="lock"></i>
                            <input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" aria-describedby="passwordRules" required>
                            <button type="button" class="toggle-pass" aria-label="Show password"><i data-lucide="eye"></i></button>
                        </div>
                        <ul class="checklist" id="passwordRules">
                            <li data-rule="length">8+ characters</li>
                            <li data-rule="upper">One uppercase letter</li>
                            <li data-rule="number">One number</li>
                        </ul>
                    </div>
                    <div class="field">
                        <label for="repeat_password">Confirm password</label>
                        <div class="input has-toggle">
                            <i data-lucide="lock"></i>
                            <input type="password" id="repeat_password" name="repeat_password" placeholder="Repeat your password" autocomplete="new-password" aria-describedby="matchHint" required>
                            <button type="button" class="toggle-pass" aria-label="Show password"><i data-lucide="eye"></i></button>
                        </div>
                        <p class="hint" id="matchHint" aria-live="polite"></p>
                    </div>
                    <button type="submit" class="btn-primary">Create account <i data-lucide="arrow-right"></i></button>
                </form>

                <p class="switch-line">Already have an account? <a href="login.php">Sign in</a></p>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();

    // User / Admin switch
    document.querySelectorAll('.role-switch button').forEach(function (button) {
        button.addEventListener('click', function () {
            var isAdmin = button.dataset.role === 'admin';
            document.querySelectorAll('.role-switch button').forEach(function (b) {
                b.setAttribute('aria-pressed', String(b === button));
            });
            document.getElementById('userLoginForm').hidden = isAdmin;
            document.getElementById('adminLoginForm').hidden = !isAdmin;
            document.getElementById(isAdmin ? 'admin_username' : 'email').focus();
        });
    });

    // Show / hide password
    document.querySelectorAll('.toggle-pass').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = button.parentElement.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.innerHTML = '<i data-lucide="' + (show ? 'eye-off' : 'eye') + '"></i>';
            lucide.createIcons();
        });
    });

    // Live password rules + match check on sign up
    var signupForm = document.getElementById('signupForm');
    if (signupForm) {
        var password = document.getElementById('password');
        var repeat = document.getElementById('repeat_password');
        var hint = document.getElementById('matchHint');
        var rules = {
            length: function (v) { return v.length >= 8; },
            upper: function (v) { return /[A-Z]/.test(v); },
            number: function (v) { return /[0-9]/.test(v); }
        };

        function refresh() {
            document.querySelectorAll('#passwordRules li').forEach(function (li) {
                li.classList.toggle('met', rules[li.dataset.rule](password.value));
            });
            if (!repeat.value) {
                hint.textContent = '';
                hint.className = 'hint';
            } else if (repeat.value === password.value) {
                hint.textContent = 'Passwords match';
                hint.className = 'hint ok';
            } else {
                hint.textContent = 'Passwords do not match yet';
                hint.className = 'hint bad';
            }
        }

        password.addEventListener('input', refresh);
        repeat.addEventListener('input', refresh);
    }
</script>
</body>
</html>
