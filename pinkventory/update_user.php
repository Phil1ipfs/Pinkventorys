<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_admin();

$genders = ['Male', 'Female', 'Other'];
$errors = [];

$id = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    redirect('users.php');
}

$stmt = $conn->prepare("SELECT id, email, fullname, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    flash('error', 'That user no longer exists.');
    redirect('users.php');
}

$values = ['email' => $user['email'], 'fullname' => $user['fullname'], 'gender' => (string) $user['gender']];

if (isset($_POST['update'])) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $fullname = trim((string) ($_POST['fullname'] ?? ''));
    $gender = (string) ($_POST['gender'] ?? '');
    $values = ['email' => $email, 'fullname' => $fullname, 'gender' => $gender];

    if ($fullname === '' || mb_strlen($fullname) > 100) {
        $errors['fullname'] = 'Enter a full name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!in_array($gender, $genders, true)) {
        $errors['gender'] = 'Choose a gender.';
    }

    if (!$errors) {
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ? AND id <> ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'That email is already used by another account.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET email = ?, fullname = ?, gender = ? WHERE id = ?");
            $stmt->bind_param("sssi", $email, $fullname, $gender, $id);
            $stmt->execute();
            flash('success', $fullname . '’s details were updated.');
            redirect('users.php');
        }
    }
}

$err = fn(string $f) => isset($errors[$f]) ? '<p class="field-error" id="' . $f . 'Error">' . icon('alert-circle') . e($errors[$f]) . '</p>' : '';
$invalid = fn(string $f) => isset($errors[$f]) ? 'aria-invalid="true" aria-describedby="' . $f . 'Error"' : '';

layout_start('Edit ' . $user['fullname'], 'users');
?>

<div class="page-head">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="users.php">Users</a> <?php echo icon('chevron-right'); ?> <span>Edit user</span>
        </nav>
        <h1><?php echo e($user['fullname']); ?></h1>
        <p><?php echo e($user['email']); ?> · User #<?php echo (int) $user['id']; ?></p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error" role="alert"><?php echo icon('alert-circle'); ?><div><strong>Please fix the highlighted fields.</strong></div></div>
<?php endif; ?>

<form method="post" action="update_user.php" class="card form-card" novalidate>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
    <input type="hidden" name="update" value="1">
    <div class="form-section">
        <div class="form-section-intro">
            <h2>Profile</h2>
            <p>Changing the email changes what they sign in with.</p>
        </div>
        <div class="fields">
            <div class="field <?php echo isset($errors['fullname']) ? 'has-error' : ''; ?>">
                <label for="fullname">Full name</label>
                <div class="control">
                    <?php echo icon('user', 'icon-sm'); ?>
                    <input type="text" id="fullname" name="fullname" class="input" maxlength="100" required
                           value="<?php echo e($values['fullname']); ?>" <?php echo $invalid('fullname'); ?>>
                </div>
                <?php echo $err('fullname'); ?>
            </div>
            <div class="field <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                <label for="email">Email</label>
                <div class="control">
                    <?php echo icon('mail', 'icon-sm'); ?>
                    <input type="email" id="email" name="email" class="input" required
                           value="<?php echo e($values['email']); ?>" <?php echo $invalid('email'); ?>>
                </div>
                <?php echo $err('email'); ?>
            </div>
            <fieldset class="field <?php echo isset($errors['gender']) ? 'has-error' : ''; ?>">
                <legend class="label">Gender</legend>
                <div class="choices">
                    <?php foreach ($genders as $g): ?>
                        <label class="choice">
                            <input type="radio" name="gender" value="<?php echo $g; ?>" <?php echo $values['gender'] === $g ? 'checked' : ''; ?> required>
                            <span><?php echo $g; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php echo $err('gender'); ?>
            </fieldset>
        </div>
    </div>

    <div class="form-actions">
        <a href="users.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><?php echo icon('check'); ?> Save changes</button>
    </div>
</form>

<?php layout_end(); ?>
