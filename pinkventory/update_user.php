<?php
require_once 'bootstrap.php';
require_admin();

$genders = ['Male', 'Female', 'Other'];
$error = null;

$id = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    redirect('admin_dashboard.php');
}

if (isset($_POST['update'])) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $fullname = trim((string) ($_POST['fullname'] ?? ''));
    $gender = (string) ($_POST['gender'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif ($fullname === '' || mb_strlen($fullname) > 100) {
        $error = "Please enter a full name (up to 100 characters).";
    } elseif (!in_array($gender, $genders, true)) {
        $error = "Please select a valid gender.";
    } else {
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ? AND id <> ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "That email is already used by another account.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET email = ?, fullname = ?, gender = ? WHERE id = ?");
            $stmt->bind_param("sssi", $email, $fullname, $gender, $id);
            $stmt->execute();
            echo "<script>alert('User updated successfully'); window.location.href='admin_dashboard.php';</script>";
            exit;
        }
    }
}

$stmt = $conn->prepare("SELECT id, email, fullname, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    http_response_code(404);
    exit('User not found. <a href="admin_dashboard.php">Back to dashboard</a>');
}

// Keep what the admin typed if validation failed.
if ($error) {
    $user = array_merge($user, array_intersect_key($_POST, array_flip(['email', 'fullname', 'gender'])));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <link rel="stylesheet" type="text/css" href="<?php echo asset('update.css'); ?>">
</head>
<body>
    <div class="container">
        <h2>Update User</h2>
        <?php if ($error): ?>
            <p style="color: #721c24; background: #f8d7da; padding: 10px; border-radius: 6px;"><?php echo e($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo e($user['email']); ?>" required>
            <label>Full Name:</label>
            <input type="text" name="fullname" value="<?php echo e($user['fullname']); ?>" maxlength="100" required>
            <label>Gender:</label>
            <select name="gender" required>
                <?php foreach ($genders as $g): ?>
                    <option value="<?php echo $g; ?>" <?php echo ($user['gender'] === $g) ? 'selected' : ''; ?>><?php echo $g; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="update">Update</button>
        </form>
        <p style="margin-top: 15px;"><a href="admin_dashboard.php">Back to dashboard</a></p>
    </div>
</body>
</html>
