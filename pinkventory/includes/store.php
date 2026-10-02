<?php
/**
 * Storefront components shared by the customer pages.
 */

if (!defined('SESSION_IDLE_TIMEOUT')) {
    http_response_code(404);
    exit;
}

const STORE_TAX_RATE = 0.10; // same 10% the original checkout used

/** Data for an "Add to cart" button (read by assets/js/app.js). */
function cart_payload(array $p): string
{
    return e(json_encode([
        'id' => (int) $p['id'],
        'name' => $p['product_name'],
        'price' => round((float) $p['price'], 2),
        'stock' => max(0, (int) $p['stock']),
    ]));
}

/** Square product visual: category icon on a soft tone (products have no photos). */
function product_art(array $p, string $size = ''): string
{
    [$iconName, $tone] = product_visual($p['category']);
    return '<span class="product-art tone-' . $tone . ($size ? ' product-art-' . $size : '') . '" aria-hidden="true">' . icon($iconName) . '</span>';
}

function product_card(array $p): string
{
    $stock = (int) $p['stock'];
    $level = stock_level($stock, $p['status']);
    $url = 'product.php?id=' . (int) $p['id'];
    ob_start(); ?>
    <article class="product-card">
        <a href="<?php echo $url; ?>" class="product-media" tabindex="-1" aria-hidden="true"><?php echo product_art($p); ?></a>
        <div class="product-body">
            <span class="product-cat"><?php echo e(ucfirst($p['category'])); ?></span>
            <h3 class="product-name"><a href="<?php echo $url; ?>"><?php echo e($p['product_name']); ?></a></h3>
            <div class="product-meta">
                <span class="product-price num"><?php echo money((float) $p['price']); ?></span>
                <span class="badge badge-<?php echo $level; ?>"><?php echo level_label($level); ?></span>
            </div>
            <button type="button" class="btn btn-primary btn-sm btn-block" data-add-to-cart="<?php echo cart_payload($p); ?>" <?php echo $level === 'out' || $stock <= 0 ? 'disabled' : ''; ?>>
                <?php echo icon($stock > 0 ? 'plus' : 'x-circle', 'icon-sm'); ?> <?php echo $stock > 0 ? 'Add to cart' : 'Out of stock'; ?>
            </button>
        </div>
    </article>
    <?php
    return ob_get_clean();
}
