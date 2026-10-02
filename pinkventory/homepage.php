<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/orders.php';
require_user();

$myOrders = null;
if (orders_enabled($conn) && ($me = current_user_row($conn))) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->bind_param("i", $me['id']);
    $stmt->execute();
    $myOrders = (int) $stmt->get_result()->fetch_row()[0];
}

$fullname = current_display_name($conn);
$firstName = strtok($fullname, ' ') ?: $fullname;
$levels = stock_level_sql();

$available = (int) $conn->query("SELECT COUNT(*) FROM inventory WHERE NOT {$levels['out']}")->fetch_row()[0];
$categories = $conn->query("SELECT LOWER(category) AS category, COUNT(*) AS items FROM inventory
    GROUP BY LOWER(category) ORDER BY items DESC, category")->fetch_all(MYSQLI_ASSOC);
$newArrivals = $conn->query("SELECT id, product_name, category, stock, price, status FROM inventory
    WHERE NOT {$levels['out']} ORDER BY last_updated DESC, id DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

layout_start('Home', 'home');
?>

<section class="store-hero">
    <div class="store-hello">
        <h1>Hello, <?php echo e($firstName); ?>!</h1>
        <p>Welcome back to Pinkventory. Find the products you need.</p>
        <a href="shop.php" class="btn btn-primary"><?php echo icon('store'); ?> Shop now</a>
    </div>
    <a href="shop.php" class="promo">
        <div>
            <strong>Everything in stock, one click away</strong>
            <span><?php echo number_format($available); ?> products ready to order</span>
            <span class="promo-cta">Browse products <?php echo icon('arrow-right', 'icon-sm'); ?></span>
        </div>
        <span class="promo-art" aria-hidden="true"><?php echo icon('shopping-bag'); ?></span>
    </a>
</section>

<section class="stats" aria-label="Your store at a glance">
    <a href="shop.php" class="card stat">
        <div class="stat-top">Products available <span class="stat-icon"><?php echo icon('package'); ?></span></div>
        <div class="stat-value num"><?php echo number_format($available); ?></div>
        <div class="stat-meta link-more">Browse shop <?php echo icon('arrow-right'); ?></div>
    </a>
    <a href="shop.php" class="card stat">
        <div class="stat-top">Categories <span class="stat-icon is-info"><?php echo icon('layers'); ?></span></div>
        <div class="stat-value num"><?php echo count($categories); ?></div>
        <div class="stat-meta link-more">See all <?php echo icon('arrow-right'); ?></div>
    </a>
    <a href="cart.php" class="card stat">
        <div class="stat-top">In your cart <span class="stat-icon is-warn"><?php echo icon('shopping-cart'); ?></span></div>
        <div class="stat-value num" data-cart-total-count>0</div>
        <div class="stat-meta link-more">View cart <?php echo icon('arrow-right'); ?></div>
    </a>
    <?php if ($myOrders !== null): ?>
        <a href="orders.php" class="card stat">
            <div class="stat-top">Total orders <span class="stat-icon is-ok"><?php echo icon('receipt'); ?></span></div>
            <div class="stat-value num"><?php echo number_format($myOrders); ?></div>
            <div class="stat-meta link-more">View orders <?php echo icon('arrow-right'); ?></div>
        </a>
    <?php else: ?>
        <a href="profile.php" class="card stat">
            <div class="stat-top">Your account <span class="stat-icon is-ok"><?php echo icon('user'); ?></span></div>
            <div class="stat-value stat-value-sm"><?php echo e($firstName); ?></div>
            <div class="stat-meta link-more">Manage profile <?php echo icon('arrow-right'); ?></div>
        </a>
    <?php endif; ?>
</section>

<section class="section-block" aria-labelledby="arrivalsTitle">
    <div class="section-head">
        <h2 id="arrivalsTitle">New arrivals</h2>
        <a href="shop.php" class="link-more">View all <?php echo icon('arrow-right'); ?></a>
    </div>
    <?php if (!$newArrivals): ?>
        <div class="card empty">
            <span class="empty-icon"><?php echo icon('package'); ?></span>
            <h3>No products available yet</h3>
            <p>Check back soon. New products appear here as soon as they're in stock.</p>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($newArrivals as $p) echo product_card($p); ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($categories): ?>
    <section class="section-block" aria-labelledby="categoriesTitle">
        <div class="section-head">
            <h2 id="categoriesTitle">Shop by category</h2>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $cat): [$catIcon, $tone] = product_visual($cat['category']); ?>
                <a href="shop.php?category=<?php echo urlencode($cat['category']); ?>" class="category-card card">
                    <span class="thumb tone-<?php echo $tone; ?>"><?php echo icon($catIcon); ?></span>
                    <span>
                        <strong><?php echo e(ucfirst($cat['category'])); ?></strong>
                        <small><?php echo (int) $cat['items']; ?> <?php echo (int) $cat['items'] === 1 ? 'product' : 'products'; ?></small>
                    </span>
                    <?php echo icon('chevron-right', 'icon-sm'); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php layout_end(['store.js']); ?>
