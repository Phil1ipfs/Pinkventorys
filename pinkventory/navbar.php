<?php
// Shared top navigation. Set $activePage to 'dashboard', 'inventory' or 'checkout' before including.
if (!defined('SESSION_IDLE_TIMEOUT')) {
    http_response_code(404);
    exit;
}
$activePage = $activePage ?? '';
$navLinks = [
    'dashboard' => ['homepage.php', 'bar-chart-2', 'Dashboard'],
    'inventory' => ['inventory.php', 'package', 'Inventory'],
    'checkout'  => ['Checkout.php', 'shopping-cart', 'Checkout'],
];
?>
    <!-- Navigation Bar -->
    <header class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <button class="menu-btn" aria-label="Toggle menu">
                    <i data-lucide="menu"></i>
                </button>
                 <img src="pinkventory-logo.png" alt="Pinkventory Logo" class="logo">
                    <h1 class="brand">Pinkventory</h1>
                <nav class="nav-links">
                <?php foreach ($navLinks as $key => [$href, $icon, $label]): ?>
                <a href="<?php echo $href; ?>" class="nav-link<?php echo $key === $activePage ? ' active' : ''; ?>">
                    <i data-lucide="<?php echo $icon; ?>"></i> <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </nav>
            </div>
            <div class="search-container1">
                <span class="search-icon1">&#128269;</span>
                <input type="text" class="search-input1" id="search" placeholder="Search products..." autocomplete="off">
                <div class="suggestions" id="suggestions"></div>
            </div>
            <div class="nav-right">
                <span class="sync-status">
                    <i data-lucide="clock"></i> Last sync: 2 min ago
                </span>
                <button class="user-btn" aria-label="User profile">
                    <i data-lucide="user"></i>
                </button>
            </div>
        </div>
    </header>
    <script src="<?php echo asset('search.js'); ?>" defer></script>
