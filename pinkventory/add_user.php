<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_admin();

$genders = ['Male', 'Female', 'Other'];
$errors = [];
$values = ['email' => '', 'fullname' => '', 'gender' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $fullName = trim((string) ($_POST['fullname'] ?? ''));
    $gender = (string) ($_POST['gender'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $values = ['email' => $email, 'fullname' => $fullName, 'gender' => $gender];

    if ($fullName === '' || mb_strlen($fullName) > 100) {
        $errors['fullname'] = 'Enter a full name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!in_array($gender, $genders, true)) {
        $errors['gender'] = 'Choose a gender.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    if (!$errors) {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'An account with this email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, fullname, gender, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $email, $fullName, $gender, $hashedPassword);
            $stmt->execute();
            flash('success', $fullName . ' was added. They can now sign in with ' . $email . '.');
            redirect('users.php');
        }
    }
}

$err = fn(string $f) => isset($errors[$f]) ? '<p class="field-error" id="' . $f . 'Error">' . icon('alert-circle') . e($errors[$f]) . '</p>' : '';
$invalid = fn(string $f) => isset($errors[$f]) ? 'aria-invalid="true" aria-describedby="' . $f . 'Error"' : '';

layout_start('Add user', 'add-user');
?>

<div class="page-head">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="users.php">Users</a> <?php echo icon('chevron-right'); ?> <span>Add user</span>
        </nav>
        <h1>Add user</h1>
        <p>Create an account for someone on your team.</p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error" role="alert"><?php echo icon('alert-circle'); ?><div><strong>Please fix the highlighted fields.</strong></div></div>
<?php endif; ?>

<form method="post" class="card form-card" novalidate>
    <?php echo csrf_field(); ?>
    <div class="form-section">
        <div class="form-section-intro">
            <h2>Profile</h2>
            <p>Their name and the email they'll sign in with.</p>
        </div>
        <div class="fields">
            <div class="field <?php echo isset($errors['fullname']) ? 'has-error' : ''; ?>">
                <label for="fullname">Full name</label>
                <div class="control">
                    <?php echo icon('user', 'icon-sm'); ?>
                    <input type="text" id="fullname" name="fullname" class="input" maxlength="100" autocomplete="off" required
                           value="<?php echo e($values['fullname']); ?>" placeholder="Jane Doe" <?php echo $invalid('fullname'); ?>>
                </div>
                <?php echo $err('fullname'); ?>
            </div>
            <div class="field <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                <label for="email">Email</label>
                <div class="control">
                    <?php echo icon('mail', 'icon-sm'); ?>
                    <input type="email" id="email" name="email" class="input" autocomplete="off" required
                           value="<?php echo e($values['email']); ?>" placeholder="jane@example.com" <?php echo $invalid('email'); ?>>
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

    <div class="form-section">
        <div class="form-section-intro">
            <h2>Password</h2>
            <p>Share it with them securely. They sign in on the login page as a <strong>User</strong>.</p>
        </div>
        <div class="fields">
            <div class="field <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
                <label for="password">Temporary password</label>
                <div class="control has-toggle">
                    <?php echo icon('lock', 'icon-sm'); ?>
                    <input type="password" id="password" name="password" class="input" autocomplete="new-password" required
                           placeholder="At least 8 characters" <?php echo $invalid('password'); ?>>
                    <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false"><?php echo icon('eye', 'icon-show') . icon('eye-off', 'icon-hide'); ?></button>
                </div>
                <ul class="checklist" data-rules-for="password">
                    <li data-rule="length">8+ characters</li>
                </ul>
                <?php echo $err('password'); ?>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="users.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><?php echo icon('user-plus'); ?> Add user</button>
    </div>
</form>

<?php layout_end(); ?>
