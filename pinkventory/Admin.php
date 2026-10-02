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

// Signed-in admins go back to the users list with a confirmation toast.
if ($messageType === 'success' && is_admin()) {
    flash('success', 'Admin account “' . $username . '” was created.');
    redirect('users.php');
}

$adminFields = function () {
    ?>
    <div class="field">
        <label for="admin_username">Username</label>
        <div class="<?php echo is_admin() ? 'control' : 'input'; ?>">
            <?php echo icon('at-sign', 'icon-sm'); ?>
            <input type="text" id="admin_username" name="admin_username" class="input" placeholder="store_admin" pattern="[A-Za-z0-9_]{3,50}"
                   title="3-50 letters, numbers or underscores" autocomplete="username" value="<?php echo e($_POST['admin_username'] ?? ''); ?>" required>
        </div>
        <p class="help">3–50 letters, numbers or underscores. Used to sign in.</p>
    </div>
    <div class="field">
        <label for="admin_fullname">Full name</label>
        <div class="<?php echo is_admin() ? 'control' : 'input'; ?>">
            <?php echo icon('user', 'icon-sm'); ?>
            <input type="text" id="admin_fullname" name="admin_fullname" class="input" placeholder="Jane Doe" autocomplete="name" value="<?php echo e($_POST['admin_fullname'] ?? ''); ?>" required>
        </div>
    </div>
    <div class="field">
        <label for="admin_password">Password</label>
        <div class="<?php echo is_admin() ? 'control' : 'input'; ?> has-toggle">
            <?php echo icon('lock', 'icon-sm'); ?>
            <input type="password" id="admin_password" name="admin_password" class="input" placeholder="Create a strong password" autocomplete="new-password" required>
            <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false"><?php echo icon('eye', 'icon-show') . icon('eye-off', 'icon-hide'); ?></button>
        </div>
        <ul class="checklist" data-rules-for="admin_password">
            <li data-rule="length">8+ characters</li>
            <li data-rule="upper">One uppercase letter</li>
            <li data-rule="number">One number</li>
            <li data-rule="symbol">One symbol</li>
        </ul>
    </div>
    <?php
};

if (is_admin()):
    require_once 'includes/layout.php';
    layout_start('Add admin', 'admins');
    ?>
    <div class="page-head">
        <div>
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="users.php">Users</a> <?php echo icon('chevron-right'); ?> <span>Add admin</span>
            </nav>
            <h1>Add admin</h1>
            <p>Admins can manage users and create other admins.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-error" role="alert"><?php echo icon('alert-circle'); ?><div><?php echo e($message); ?></div></div>
    <?php endif; ?>

    <form method="post" action="Admin.php" class="card form-card">
        <?php echo csrf_field(); ?>
        <div class="form-section">
            <div class="form-section-intro">
                <h2>Admin account</h2>
                <p>They sign in on the login page using the <strong>Admin</strong> option.</p>
            </div>
            <div class="fields"><?php $adminFields(); ?></div>
        </div>
        <div class="form-actions">
            <a href="users.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="adminSignUp" class="btn btn-primary"><?php echo icon('shield-check'); ?> Create admin</button>
        </div>
    </form>
    <?php
    layout_end();
    exit;
endif;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create admin account · Pinkventory</title>
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
            <p class="eyebrow">Store setup</p>
            <h2>Welcome, Admin!</h2>
            <p>Create the first administrator account to manage inventory and users.</p>
        </div>

        <div class="illustration">
            <img src="assets/img/illus-signin.svg" alt="" width="340" height="250">
        </div>

        <ul class="features">
            <li><?php echo icon('package'); ?> Manage inventory</li>
            <li><?php echo icon('users'); ?> Manage users</li>
            <li><?php echo icon('shield-check'); ?> Admin-only area</li>
        </ul>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <a href="index.php" class="brand-link mobile-brand">
                <img src="assets/img/logo.png" alt="">
                Pinkventory
            </a>

            <p class="card-switch">Already have an admin account?<a href="login.php">Log in <?php echo icon('arrow-right'); ?></a></p>
            <h1>Create admin <span class="accent">account</span></h1>
            <p class="lead">Set up the first administrator for this store.</p>

            <?php if (!empty($blocked)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo icon('lock'); ?>
                    <span>Only a signed-in admin can create new admin accounts.</span>
                </div>
                <a href="login.php" class="btn-primary">Go to log in <?php echo icon('arrow-right'); ?></a>
            <?php else: ?>
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>" role="<?php echo $messageType === 'success' ? 'status' : 'alert'; ?>">
                        <?php echo icon($messageType === 'success' ? 'check-circle-2' : 'alert-circle'); ?>
                        <span>
                            <?php echo e($message); ?>
                            <?php if ($messageType === 'success'): ?>
                                <a href="login.php">Log in now</a>.
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($messageType !== 'success'): ?>
                    <form method="post" action="" class="form">
                        <?php echo csrf_field(); ?>
                        <?php $adminFields(); ?>
                        <button type="submit" name="adminSignUp" class="btn-primary"><?php echo icon('shield-check'); ?> Create admin account <?php echo icon('arrow-right'); ?></button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
