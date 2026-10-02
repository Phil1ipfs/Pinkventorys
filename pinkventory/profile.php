<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_login();

$isAdmin = is_admin();
$genders = ['Male', 'Female', 'Other'];
$profileErrors = [];
$passwordErrors = [];

// Load the signed-in account (customers: users table, admins: admin table).
if ($isAdmin) {
    $stmt = $conn->prepare("SELECT id, username, fullname, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['admin_username']);
} else {
    $stmt = $conn->prepare("SELECT id, email, fullname, dob, gender, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $_SESSION['email']);
}
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
if (!$account) {
    redirect(logout_url());
}

$values = [
    'fullname' => (string) $account['fullname'],
    'email'    => (string) ($account['email'] ?? ''),
    'dob'      => (string) ($account['dob'] ?? ''),
    'gender'   => (string) ($account['gender'] ?? ''),
];

$action = $_POST['action'] ?? '';

if ($action === 'profile') {
    $values['fullname'] = trim((string) ($_POST['fullname'] ?? ''));

    if ($isAdmin) {
        if (!preg_match('/^[a-zA-Z\s]{1,100}$/', $values['fullname'])) {
            $profileErrors['fullname'] = 'Use letters and spaces only (up to 100 characters).';
        }
        if (!$profileErrors) {
            $stmt = $conn->prepare("UPDATE admin SET fullname = ? WHERE id = ?");
            $stmt->bind_param("si", $values['fullname'], $account['id']);
            $stmt->execute();
            flash('success', 'Your profile was updated.');
            redirect('profile.php');
        }
    } else {
        $values['email'] = trim((string) ($_POST['email'] ?? ''));
        $values['dob'] = trim((string) ($_POST['dob'] ?? ''));
        $values['gender'] = (string) ($_POST['gender'] ?? '');

        if ($values['fullname'] === '' || mb_strlen($values['fullname']) > 100) {
            $profileErrors['fullname'] = 'Enter your name (up to 100 characters).';
        }
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $profileErrors['email'] = 'Enter a valid email address.';
        }
        if ($values['dob'] !== '') {
            $dob = DateTime::createFromFormat('!Y-m-d', $values['dob']);
            if (!$dob || $dob->format('Y-m-d') !== $values['dob'] || $dob > new DateTime()) {
                $profileErrors['dob'] = 'Enter a valid date of birth.';
            }
        }
        if ($values['gender'] !== '' && !in_array($values['gender'], $genders, true)) {
            $profileErrors['gender'] = 'Choose a gender.';
        }
        if (!$profileErrors && strcasecmp($values['email'], $account['email']) !== 0) {
            $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ? AND id <> ?");
            $stmt->bind_param("si", $values['email'], $account['id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $profileErrors['email'] = 'That email is already used by another account.';
            }
        }
        if (!$profileErrors) {
            $dobValue = $values['dob'] !== '' ? $values['dob'] : null;
            $genderValue = $values['gender'] !== '' ? $values['gender'] : null;
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, dob = ?, gender = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $values['fullname'], $values['email'], $dobValue, $genderValue, $account['id']);
            $stmt->execute();
            // The session identifies customers by email, so keep it in step.
            $_SESSION['email'] = $values['email'];
            flash('success', 'Your profile was updated.');
            redirect('profile.php');
        }
    }
}

if ($action === 'password') {
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!password_verify($current, $account['password'])) {
        $passwordErrors['current_password'] = 'Your current password is incorrect.';
    }
    $rulesOk = strlen($new) >= 8 && preg_match('/[A-Z]/', $new) && preg_match('/[0-9]/', $new)
        && (!$isAdmin || preg_match('/[^A-Za-z0-9]/', $new));
    if (!$rulesOk) {
        $passwordErrors['new_password'] = $isAdmin
            ? 'Use 8+ characters with an uppercase letter, a number and a symbol.'
            : 'Use 8+ characters with an uppercase letter and a number.';
    }
    if ($new !== $confirm) {
        $passwordErrors['confirm_password'] = 'The new passwords don’t match.';
    }
    if (!$passwordErrors) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare($isAdmin ? "UPDATE admin SET password = ? WHERE id = ?" : "UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $account['id']);
        $stmt->execute();
        session_regenerate_id(true);
        flash('success', 'Your password was changed.');
        redirect('profile.php');
    }
}

$err = function (array $errors, string $f): string {
    return isset($errors[$f]) ? '<p class="field-error" id="' . $f . 'Error">' . icon('alert-circle') . e($errors[$f]) . '</p>' : '';
};
$invalid = fn(array $errors, string $f) => isset($errors[$f]) ? 'aria-invalid="true" aria-describedby="' . $f . 'Error"' : '';
$passwordField = function (string $id, string $label, string $autocomplete, bool $withRules = false) use ($passwordErrors, $err, $invalid, $isAdmin) {
    ?>
    <div class="field <?php echo isset($passwordErrors[$id]) ? 'has-error' : ''; ?>">
        <label for="<?php echo $id; ?>"><?php echo $label; ?></label>
        <div class="control has-toggle">
            <?php echo icon('lock', 'icon-sm'); ?>
            <input type="password" id="<?php echo $id; ?>" name="<?php echo $id; ?>" class="input" autocomplete="<?php echo $autocomplete; ?>" required <?php echo $invalid($passwordErrors, $id); ?>>
            <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false"><?php echo icon('eye', 'icon-show') . icon('eye-off', 'icon-hide'); ?></button>
        </div>
        <?php if ($withRules): ?>
            <ul class="checklist" data-rules-for="<?php echo $id; ?>">
                <li data-rule="length">8+ characters</li>
                <li data-rule="upper">One uppercase letter</li>
                <li data-rule="number">One number</li>
                <?php if ($isAdmin): ?><li data-rule="symbol">One symbol</li><?php endif; ?>
            </ul>
        <?php endif; ?>
        <?php echo $err($passwordErrors, $id); ?>
    </div>
    <?php
};

layout_start('My account', 'profile');
?>

<div class="page-head">
    <div>
        <h1>My account</h1>
        <p>Manage your personal information and account settings.</p>
    </div>
</div>

<section class="card profile-card">
    <span class="avatar avatar-xl"><?php echo e(initials($account['fullname'] ?: ($account['username'] ?? '?'))); ?></span>
    <div class="grow">
        <h2><?php echo e($account['fullname']); ?></h2>
        <p class="muted"><?php echo e($isAdmin ? '@' . $account['username'] : $account['email']); ?></p>
    </div>
    <span class="badge <?php echo $isAdmin ? 'badge-brand' : 'badge-neutral'; ?> badge-plain"><?php echo $isAdmin ? 'Administrator' : 'Customer'; ?></span>
</section>

<div class="profile-grid">
    <form method="post" action="profile.php" class="card" novalidate>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="profile">
        <div class="card-head">
            <div>
                <h2>Account information</h2>
                <p><?php echo $isAdmin ? 'Your name as shown to other admins.' : 'Used for your orders and to sign in.'; ?></p>
            </div>
        </div>
        <div class="card-body fields">
            <?php if ($profileErrors): ?>
                <div class="alert alert-error alert-inline" role="alert"><?php echo icon('alert-circle'); ?><div>Please fix the highlighted fields.</div></div>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <div class="field">
                    <label for="username">Username</label>
                    <div class="control">
                        <?php echo icon('at-sign', 'icon-sm'); ?>
                        <input type="text" id="username" class="input" value="<?php echo e($account['username']); ?>" disabled>
                    </div>
                    <p class="help">Usernames can't be changed.</p>
                </div>
            <?php endif; ?>
            <div class="field <?php echo isset($profileErrors['fullname']) ? 'has-error' : ''; ?>">
                <label for="fullname">Full name</label>
                <div class="control">
                    <?php echo icon('user', 'icon-sm'); ?>
                    <input type="text" id="fullname" name="fullname" class="input" maxlength="100" autocomplete="name" required
                           value="<?php echo e($values['fullname']); ?>" <?php echo $invalid($profileErrors, 'fullname'); ?>>
                </div>
                <?php echo $err($profileErrors, 'fullname'); ?>
            </div>
            <?php if (!$isAdmin): ?>
                <div class="field <?php echo isset($profileErrors['email']) ? 'has-error' : ''; ?>">
                    <label for="email">Email address</label>
                    <div class="control">
                        <?php echo icon('mail', 'icon-sm'); ?>
                        <input type="email" id="email" name="email" class="input" autocomplete="email" required
                               value="<?php echo e($values['email']); ?>" <?php echo $invalid($profileErrors, 'email'); ?>>
                    </div>
                    <p class="help">You sign in with this email.</p>
                    <?php echo $err($profileErrors, 'email'); ?>
                </div>
                <div class="fields-2">
                    <div class="field <?php echo isset($profileErrors['dob']) ? 'has-error' : ''; ?>">
                        <label for="dob">Date of birth <span class="optional">(optional)</span></label>
                        <div class="control">
                            <?php echo icon('calendar', 'icon-sm'); ?>
                            <input type="date" id="dob" name="dob" class="input" max="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo e($values['dob']); ?>" <?php echo $invalid($profileErrors, 'dob'); ?>>
                        </div>
                        <?php echo $err($profileErrors, 'dob'); ?>
                    </div>
                    <div class="field <?php echo isset($profileErrors['gender']) ? 'has-error' : ''; ?>">
                        <label for="gender">Gender <span class="optional">(optional)</span></label>
                        <select id="gender" name="gender" class="select" <?php echo $invalid($profileErrors, 'gender'); ?>>
                            <option value="">Prefer not to say</option>
                            <?php foreach ($genders as $g): ?>
                                <option value="<?php echo $g; ?>" <?php echo $values['gender'] === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php echo $err($profileErrors, 'gender'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo icon('check'); ?> Save changes</button>
        </div>
    </form>

    <div class="stack">
        <form method="post" action="profile.php" class="card" novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="password">
            <div class="card-head">
                <div>
                    <h2>Change password</h2>
                    <p>You'll stay signed in on this device.</p>
                </div>
            </div>
            <div class="card-body fields">
                <?php $passwordField('current_password', 'Current password', 'current-password'); ?>
                <?php $passwordField('new_password', 'New password', 'new-password', true); ?>
                <?php $passwordField('confirm_password', 'Confirm new password', 'new-password'); ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?php echo icon('key-round'); ?> Update password</button>
            </div>
        </form>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2>Session</h2>
                    <p>For your security you're signed out after <?php echo SESSION_IDLE_TIMEOUT / 60; ?> minutes of inactivity.</p>
                </div>
            </div>
            <div class="card-body">
                <a href="<?php echo e(logout_url()); ?>" class="btn btn-danger-soft"><?php echo icon('log-out'); ?> Log out of this device</a>
            </div>
        </section>
    </div>
</div>

<?php layout_end(); ?>
