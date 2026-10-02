<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_admin();

$users = $conn->query("SELECT id, email, fullname, gender FROM users ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$adminCount = (int) $conn->query("SELECT COUNT(*) FROM admin")->fetch_row()[0];
$productCount = (int) $conn->query("SELECT COUNT(*) FROM inventory")->fetch_row()[0];

layout_start('Users', 'users');
?>

<div class="page-head">
    <div>
        <h1>Users</h1>
        <p>Manage the people who can sign in to your store.</p>
    </div>
    <div class="page-actions">
        <a href="Admin.php" class="btn btn-secondary"><?php echo icon('shield-check'); ?> Add admin</a>
        <a href="add_user.php" class="btn btn-primary"><?php echo icon('user-plus'); ?> Add user</a>
    </div>
</div>

<section class="stats stats-3" aria-label="Account summary">
    <div class="card stat">
        <div class="stat-top">Registered users <span class="stat-icon"><?php echo icon('users'); ?></span></div>
        <div class="stat-value num"><?php echo number_format(count($users)); ?></div>
        <div class="stat-meta">Can use the store dashboard</div>
    </div>
    <div class="card stat">
        <div class="stat-top">Administrators <span class="stat-icon is-info"><?php echo icon('shield-check'); ?></span></div>
        <div class="stat-value num"><?php echo number_format($adminCount); ?></div>
        <div class="stat-meta">Can manage users and admins</div>
    </div>
    <a href="inventory.php" class="card stat">
        <div class="stat-top">Products <span class="stat-icon is-ok"><?php echo icon('package'); ?></span></div>
        <div class="stat-value num"><?php echo number_format($productCount); ?></div>
        <div class="stat-meta">In your inventory</div>
    </a>
</section>

<section class="card" aria-labelledby="usersTitle">
    <div class="toolbar">
        <h2 id="usersTitle" class="sr-only">All users</h2>
        <div class="search">
            <?php echo icon('search', 'icon-sm'); ?>
            <label for="userFilter" class="sr-only">Filter users by name or email</label>
            <input type="search" id="userFilter" class="input" placeholder="Filter by name or email…" <?php echo $users ? '' : 'disabled'; ?>>
        </div>
        <span class="muted" id="userCount" aria-live="polite"><?php echo count($users); ?> <?php echo count($users) === 1 ? 'user' : 'users'; ?></span>
    </div>

    <?php if (!$users): ?>
        <div class="empty">
            <span class="empty-icon"><?php echo icon('users'); ?></span>
            <h3>No users yet</h3>
            <p>Users can sign up from the login page, or you can add them yourself.</p>
            <a href="add_user.php" class="btn btn-primary"><?php echo icon('user-plus'); ?> Add the first user</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-cards" id="usersTable">
                <thead>
                    <tr>
                        <th scope="col">User</th>
                        <th scope="col">Gender</th>
                        <th scope="col">ID</th>
                        <th scope="col" class="actions"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $row): ?>
                        <tr data-search="<?php echo e(mb_strtolower($row['fullname'] . ' ' . $row['email'])); ?>">
                            <td class="cell-first">
                                <div class="cell-main">
                                    <span class="avatar avatar-lg"><?php echo e(initials($row['fullname'])); ?></span>
                                    <div>
                                        <strong><?php echo e($row['fullname']); ?></strong>
                                        <span><?php echo e($row['email']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Gender">
                                <?php if ($row['gender']): ?>
                                    <span class="badge badge-neutral badge-plain"><?php echo e($row['gender']); ?></span>
                                <?php else: ?>
                                    <span class="muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="ID" class="muted num">#<?php echo (int) $row['id']; ?></td>
                            <td class="actions">
                                <div class="actions-inner">
                                    <a href="update_user.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-secondary btn-sm" aria-label="Edit <?php echo e($row['fullname']); ?>">
                                        <?php echo icon('pencil', 'icon-sm'); ?><span class="label-sm">Edit</span>
                                    </a>
                                    <button type="button" class="btn btn-danger-soft btn-sm" data-delete-user="<?php echo (int) $row['id']; ?>"
                                            data-name="<?php echo e($row['fullname']); ?>" aria-label="Delete <?php echo e($row['fullname']); ?>">
                                        <?php echo icon('trash-2', 'icon-sm'); ?><span class="label-sm">Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="empty empty-sm" id="noUserMatches" hidden>
            <span class="empty-icon"><?php echo icon('search'); ?></span>
            <h3>No users match</h3>
            <p>Try a different name or email address.</p>
        </div>
    <?php endif; ?>
</section>

<?php layout_end(['users.js']); ?>
