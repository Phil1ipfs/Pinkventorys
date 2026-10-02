<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/store.php';
require_user();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$product = null;
if ($id) {
    $stmt = $conn->prepare("SELECT id, product_name, category, stock, price, status, last_updated FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
}
if (!$product) {
    flash('error', 'That product isn’t available any more.');
    redirect('shop.php');
}

$stock = (int) $product['stock'];
$level = stock_level($stock, $product['status']);
$canBuy = $level !== 'out' && $stock > 0;

$stmt = $conn->prepare("SELECT id, product_name, category, stock, price, status FROM inventory
    WHERE LOWER(category) = LOWER(?) AND id <> ? ORDER BY last_updated DESC LIMIT 4");
$stmt->bind_param("si", $product['category'], $id);
$stmt->execute();
$related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

layout_start($product['product_name'], 'shop');
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="homepage.php">Home</a> <?php echo icon('chevron-right'); ?>
    <a href="shop.php">Shop</a> <?php echo icon('chevron-right'); ?>
    <a href="shop.php?category=<?php echo urlencode(strtolower($product['category'])); ?>"><?php echo e(ucfirst($product['category'])); ?></a> <?php echo icon('chevron-right'); ?>
    <span><?php echo e($product['product_name']); ?></span>
</nav>

<section class="card product-detail">
    <div class="detail-media"><?php echo product_art($product, 'lg'); ?></div>
    <div class="detail-info">
        <span class="product-cat"><?php echo e(ucfirst($product['category'])); ?></span>
        <h1><?php echo e($product['product_name']); ?></h1>
        <span class="badge badge-<?php echo $level; ?>"><?php echo level_label($level); ?></span>
        <p class="detail-price num"><?php echo money((float) $product['price']); ?></p>

        <dl class="detail-facts">
            <div><dt>Availability</dt><dd><?php echo $canBuy ? number_format($stock) . ' in stock' : 'Currently unavailable'; ?></dd></div>
            <div><dt>Category</dt><dd><?php echo e(ucfirst($product['category'])); ?></dd></div>
            <div><dt>Product ID</dt><dd>#<?php echo (int) $product['id']; ?></dd></div>
        </dl>

        <?php if ($canBuy): ?>
            <div class="buy-row">
                <div class="field">
                    <label for="qty" class="label">Quantity</label>
                    <div class="qty qty-lg" data-stepper>
                        <button type="button" data-step="-1" aria-label="Decrease quantity"><?php echo icon('minus', 'icon-sm'); ?></button>
                        <input type="number" id="qty" value="1" min="1" max="<?php echo $stock; ?>" inputmode="numeric" class="num">
                        <button type="button" data-step="1" aria-label="Increase quantity"><?php echo icon('plus', 'icon-sm'); ?></button>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-lg" data-add-to-cart="<?php echo cart_payload($product); ?>" data-qty-from="qty">
                    <?php echo icon('shopping-cart'); ?> Add to cart
                </button>
            </div>
            <a href="cart.php" class="link-more">Go to cart <?php echo icon('arrow-right'); ?></a>
        <?php else: ?>
            <div class="alert alert-info"><?php echo icon('info'); ?><div>This product is out of stock right now. Check back soon.</div></div>
        <?php endif; ?>
    </div>
</section>

<?php if ($related): ?>
    <section class="section-block" aria-labelledby="relatedTitle">
        <div class="section-head">
            <h2 id="relatedTitle">More in <?php echo e(ucfirst($product['category'])); ?></h2>
        </div>
        <div class="product-grid">
            <?php foreach ($related as $p) echo product_card($p); ?>
        </div>
    </section>
<?php endif; ?>

<?php layout_end(['store.js']); ?>
