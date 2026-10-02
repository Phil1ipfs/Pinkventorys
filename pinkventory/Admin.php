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
    <title>Admin Registration</title>
    <link rel="stylesheet" href="<?php echo asset('style.css'); ?>">
</head>
<body>
    <div class="page-container">
        <div class="right-panel">
        <div class="header">
                    <div class="brand">
                        <img src="pinkventory-logo.png" alt="Pinkventory Logo" class="logo">
                        <span class="brand-name">Pinkventory</span>
                    </div>
                </div>
            <h1>Create Admin Account</h1>
            <?php if (!empty($blocked)): ?>
                <div class="alert alert-danger">Only a signed-in admin can create new admin accounts.</div>
            <?php else: ?>
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div>
                <?php endif; ?>
                <form method="post" action="">
                    <?php echo csrf_field(); ?>
                    <input type="text" name="admin_username" placeholder="Admin Username" pattern="[A-Za-z0-9_]{3,50}" required>
                    <input type="text" name="admin_fullname" placeholder="Full Name" required>
                    <input type="password" name="admin_password" placeholder="Password" required>
                    <button type="submit" name="adminSignUp">Create Admin Account</button>
                </form>
            <?php endif; ?>
            <p class="already-member">
                <?php if (is_admin()): ?>
                    <a href="admin_dashboard.php">Back to dashboard</a>
                <?php else: ?>
                    Already a member? <a href="index.php?action=signin">Sign In</a>
                <?php endif; ?>
            </p>

        </div>
        <div class="left-panel">
            <h1>Welcome, Admin!</h1>
            <p>Already have an admin account? Log in now.</p>
            <a href="index.php?action=signin">
                <button id="adminbtn">Admin Sign In</button>
            </a>
        </div>

    </div>
</body>
</html>
