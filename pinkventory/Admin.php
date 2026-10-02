<?php
require_once 'bootstrap.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Anyone may create the very first admin (initial setup). After that, only a
// signed-in admin can create more admin accounts.
$adminCount = (int) $conn->query("SELECT COUNT(*) FROM admin")->fetch_row()[0];
if ($adminCount > 0 && !is_admin()) {
    http_response_code(403);
    $blocked = true;
}

$message = null;
$messageType = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($blocked)) {
    // CSRF is verified in bootstrap.php for every POST request.
    $username = trim((string) ($_POST['admin_username'] ?? ''));
    $fullname = trim((string) ($_POST['admin_fullname'] ?? ''));
    $password = (string) ($_POST['admin_password'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        // Ensure username contains only alphanumeric characters and underscores
        $message = "Username must be 3-50 letters, numbers or underscores.";
    } elseif (!preg_match('/^[a-zA-Z\s]{1,100}$/', $fullname)) {
        // Ensure full name contains only letters and spaces
        $message = "Full name may only contain letters and spaces.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $message = "Password must include uppercase letters, numbers, and special characters.";
    } else {
        // Check if username already exists in the database
        $stmt = $conn->prepare("SELECT 1 FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $message = "Username Already Exists!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin (username, password, fullname) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashedPassword, $fullname);
            $stmt->execute();
            $message = "Admin account successfully created.";
            $messageType = 'success';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create admin account · Pinkventory</title>
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
            <h2><?php echo is_admin() ? 'Grow your team' : 'Welcome, Admin!'; ?></h2>
            <p>Admins manage user accounts and keep the store running smoothly.</p>
            <ul class="perks">
                <li><span class="perk-icon"><i data-lucide="users"></i></span> Add, edit and remove users</li>
                <li><span class="perk-icon"><i data-lucide="shield-check"></i></span> Protected admin-only area</li>
                <li><span class="perk-icon"><i data-lucide="key-round"></i></span> Strong password required</li>
            </ul>
        </div>

        <div class="aside-foot">
            <?php if (is_admin()): ?>
                <a href="admin_dashboard.php"><i data-lucide="arrow-left"></i> Back to dashboard</a>
            <?php else: ?>
                <a href="index.php"><i data-lucide="arrow-left"></i> Back to home</a>
            <?php endif; ?>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <a href="index.php" class="brand-link mobile-brand">
                <img src="pinkventory-logo.png" alt="">
                Pinkventory
            </a>

            <h1>Create admin account</h1>
            <p class="lead">
                <?php echo is_admin() ? 'Add another administrator for this store.' : 'Set up the first administrator for this store.'; ?>
            </p>

            <?php if (!empty($blocked)): ?>
                <div class="alert alert-danger" role="alert">
                    <i data-lucide="lock"></i>
                    <span>Only a signed-in admin can create new admin accounts.</span>
                </div>
                <a href="login.php" class="btn-primary">Go to sign in <i data-lucide="arrow-right"></i></a>
            <?php else: ?>
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>" role="<?php echo $messageType === 'success' ? 'status' : 'alert'; ?>">
                        <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle-2' : 'alert-circle'; ?>"></i>
                        <span>
                            <?php echo e($message); ?>
                            <?php if ($messageType === 'success' && !is_admin()): ?>
                                <a href="login.php">Sign in now</a>.
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <form method="post" action="" class="form">
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="admin_username">Username</label>
                        <div class="input">
                            <i data-lucide="at-sign"></i>
                            <input type="text" id="admin_username" name="admin_username" placeholder="store_admin" pattern="[A-Za-z0-9_]{3,50}" title="3-50 letters, numbers or underscores" autocomplete="username" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="admin_fullname">Full name</label>
                        <div class="input">
                            <i data-lucide="user"></i>
                            <input type="text" id="admin_fullname" name="admin_fullname" placeholder="Jane Doe" autocomplete="name" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="admin_password">Password</label>
                        <div class="input has-toggle">
                            <i data-lucide="lock"></i>
                            <input type="password" id="admin_password" name="admin_password" placeholder="Create a strong password" autocomplete="new-password" aria-describedby="passwordRules" required>
                            <button type="button" class="toggle-pass" aria-label="Show password"><i data-lucide="eye"></i></button>
                        </div>
                        <ul class="checklist" id="passwordRules">
                            <li data-rule="length">8+ characters</li>
                            <li data-rule="upper">One uppercase letter</li>
                            <li data-rule="number">One number</li>
                            <li data-rule="symbol">One symbol</li>
                        </ul>
                    </div>
                    <button type="submit" name="adminSignUp" class="btn-primary">Create admin account <i data-lucide="arrow-right"></i></button>
                </form>

                <?php if (!is_admin()): ?>
                    <p class="switch-line">Already have an admin account? <a href="login.php">Sign in</a></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();

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

    var password = document.getElementById('admin_password');
    if (password) {
        var rules = {
            length: function (v) { return v.length >= 8; },
            upper: function (v) { return /[A-Z]/.test(v); },
            number: function (v) { return /[0-9]/.test(v); },
            symbol: function (v) { return /[^A-Za-z0-9]/.test(v); }
        };
        password.addEventListener('input', function () {
            document.querySelectorAll('#passwordRules li').forEach(function (li) {
                li.classList.toggle('met', rules[li.dataset.rule](password.value));
            });
        });
    }
</script>
</body>
</html>
