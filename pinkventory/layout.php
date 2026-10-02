<?php
/**
 * Shared page shell for every signed-in page: sidebar navigation, top bar
 * with product search and user menu, toast messages and shared dialogs.
 *
 * Usage:
 *   require_once 'layout.php';
 *   layout_start('Inventory', 'inventory');
 *   ...page content...
 *   layout_end(['inventory.js']);
 */

if (!defined('SESSION_IDLE_TIMEOUT')) {
    http_response_code(404);
    exit;
}

function nav_groups(): array
{
    $groups = [
        'Store' => [
            'dashboard' => ['homepage.php', 'layout-dashboard', 'Dashboard'],
            'inventory' => ['inventory.php', 'package', 'Inventory'],
            'checkout'  => ['Checkout.php', 'scan-barcode', 'Checkout'],
        ],
    ];
    if (is_admin()) {
        $groups['Administration'] = [
            'users'    => ['admin_dashboard.php', 'users', 'Users'],
            'add-user' => ['add_user.php', 'user-plus', 'Add user'],
            'admins'   => ['Admin.php', 'shield-check', 'Add admin'],
        ];
    }
    return $groups;
}

function layout_start(string $title, string $active): void
{
    global $conn;
    $name = current_display_name($conn);
    $role = is_admin() ? 'Administrator' : 'Store user';
    $searchValue = basename($_SERVER['SCRIPT_NAME']) === 'inventory.php' ? ($_GET['q'] ?? '') : '';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title); ?> · Pinkventory</title>
    <link rel="icon" type="image/png" href="pinkventory-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap">
    <link rel="stylesheet" href="<?php echo asset('app.css'); ?>">
    <script src="<?php echo asset('app.js'); ?>" defer></script>
</head>
<body data-idle-timeout="<?php echo SESSION_IDLE_TIMEOUT; ?>" data-logout-url="<?php echo e(logout_url()); ?>" data-icons="<?php echo asset('icons.svg'); ?>">
<a href="#main" class="skip-link">Skip to content</a>
<div class="app">
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <div class="sidebar-brand">
            <a href="<?php echo home_url(); ?>" class="brand-home">
                <img src="pinkventory-logo.png" alt="" width="34" height="34">
                Pinkventory
            </a>
            <button type="button" class="btn btn-ghost btn-icon sidebar-close" data-nav-close aria-label="Close menu"><?php echo icon('x'); ?></button>
        </div>
        <nav class="sidebar-nav">
            <?php foreach (nav_groups() as $label => $items): ?>
                <div class="nav-group">
                    <p class="nav-label"><?php echo e($label); ?></p>
                    <?php foreach ($items as $key => [$href, $iconName, $text]): ?>
                        <a href="<?php echo $href; ?>" class="nav-item" <?php echo $key === $active ? 'aria-current="page"' : ''; ?>>
                            <?php echo icon($iconName); ?> <?php echo e($text); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-foot">
            <div class="sidebar-user">
                <span class="avatar"><?php echo e(initials($name)); ?></span>
                <div class="who">
                    <strong><?php echo e($name); ?></strong>
                    <span><?php echo e($role); ?></span>
                </div>
                <a href="<?php echo e(logout_url()); ?>" class="btn btn-ghost btn-icon btn-sm" title="Sign out" aria-label="Sign out"><?php echo icon('log-out', 'icon-sm'); ?></a>
            </div>
        </div>
    </aside>
    <div class="backdrop" data-nav-close></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="btn btn-ghost btn-icon menu-btn" data-nav-open aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><?php echo icon('menu'); ?></button>

            <form class="global-search" action="inventory.php" method="get" role="search">
                <?php echo icon('search', 'icon-sm'); ?>
                <label for="globalSearch" class="sr-only">Search products</label>
                <input type="search" id="globalSearch" name="q" value="<?php echo e($searchValue); ?>" placeholder="Search products…" autocomplete="off"
                       role="combobox" aria-expanded="false" aria-controls="globalSuggest" aria-autocomplete="list">
                <kbd aria-hidden="true">/</kbd>
                <span class="spinner" hidden></span>
                <ul class="suggest" id="globalSuggest" role="listbox" hidden></ul>
            </form>

            <div class="topbar-right">
                <div class="user-menu">
                    <button type="button" class="user-trigger" aria-haspopup="true" aria-expanded="false" aria-controls="userMenu">
                        <span class="avatar"><?php echo e(initials($name)); ?></span>
                        <span class="name"><?php echo e($name); ?></span>
                        <?php echo icon('chevron-down', 'icon-sm'); ?>
                    </button>
                    <div class="menu" id="userMenu" hidden>
                        <div class="menu-head">
                            <strong><?php echo e($name); ?></strong>
                            <span><?php echo e($role); ?></span>
                        </div>
                        <a href="<?php echo home_url(); ?>"><?php echo icon('layout-dashboard', 'icon-sm'); ?> <?php echo is_admin() ? 'Admin dashboard' : 'Dashboard'; ?></a>
                        <a href="<?php echo e(logout_url()); ?>" class="danger"><?php echo icon('log-out', 'icon-sm'); ?> Sign out</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="content fade-in" id="main" tabindex="-1">
    <?php
}

function layout_end(array $scripts = []): void
{
    $toastIcons = ['success' => 'check-circle-2', 'error' => 'alert-circle', 'info' => 'info'];
    ?>
        </main>
    </div>
</div>

<div class="toasts" id="toasts" aria-live="polite" aria-atomic="false">
    <?php foreach (take_flashes() as $flash): $type = isset($toastIcons[$flash['type']]) ? $flash['type'] : 'info'; ?>
        <div class="toast toast-<?php echo $type; ?>" role="<?php echo $type === 'error' ? 'alert' : 'status'; ?>">
            <?php echo icon($toastIcons[$type]); ?>
            <p><?php echo e($flash['message']); ?></p>
            <button type="button" class="toast-close" aria-label="Dismiss"><?php echo icon('x', 'icon-sm'); ?></button>
        </div>
    <?php endforeach; ?>
</div>

<dialog class="dialog" id="confirmDialog" aria-labelledby="confirmTitle" aria-describedby="confirmMessage">
    <form method="dialog">
        <div class="dialog-body">
            <span class="dialog-icon"><?php echo icon('alert-triangle'); ?></span>
            <div>
                <h2 id="confirmTitle">Are you sure?</h2>
                <p id="confirmMessage"></p>
            </div>
        </div>
        <div class="dialog-actions">
            <button type="submit" value="cancel" class="btn btn-secondary">Cancel</button>
            <button type="submit" value="confirm" class="btn btn-danger" id="confirmOk">Delete</button>
        </div>
    </form>
</dialog>

<dialog class="dialog" id="idleDialog" aria-labelledby="idleTitle">
    <form method="dialog">
        <div class="dialog-body">
            <span class="dialog-icon is-info"><?php echo icon('clock'); ?></span>
            <div>
                <h2 id="idleTitle">Still there?</h2>
                <p>For your security you'll be signed out in <strong id="idleCountdown">60</strong> seconds.</p>
            </div>
        </div>
        <div class="dialog-actions">
            <button type="submit" value="logout" class="btn btn-secondary">Sign out</button>
            <button type="submit" value="stay" class="btn btn-primary">Stay signed in</button>
        </div>
    </form>
</dialog>

<?php foreach ($scripts as $script): ?>
<script src="<?php echo asset($script); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}
