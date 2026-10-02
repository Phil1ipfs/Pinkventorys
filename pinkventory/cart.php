<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/store.php';
require_user();

layout_start('Your cart', 'cart');
?>

<div class="page-head">
    <div>
        <h1>Your cart</h1>
        <p>Review your items and proceed to checkout.</p>
    </div>
</div>

<div class="pos" data-cart-page data-tax-rate="<?php echo STORE_TAX_RATE; ?>">
    <section class="card" aria-labelledby="cartTitle">
        <h2 id="cartTitle" class="sr-only">Items</h2>
        <div class="cart-loading empty empty-sm" data-cart-loading>
            <span class="spinner"></span>
            <p>Checking current prices and stock…</p>
        </div>
        <div class="empty" data-cart-empty hidden>
            <span class="empty-icon"><?php echo icon('shopping-cart'); ?></span>
            <h3>Your cart is empty</h3>
            <p>Browse the shop and add products to see them here.</p>
            <a href="shop.php" class="btn btn-primary"><?php echo icon('store'); ?> Start shopping</a>
        </div>
        <div data-cart-notices></div>
        <div class="table-wrap" data-cart-table hidden>
            <table class="table table-cards">
                <thead>
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col" class="right">Price</th>
                        <th scope="col">Quantity</th>
                        <th scope="col" class="right">Subtotal</th>
                        <th scope="col" class="actions"><span class="sr-only">Remove</span></th>
                    </tr>
                </thead>
                <tbody data-cart-items></tbody>
            </table>
        </div>
    </section>

    <aside class="card summary-card" aria-labelledby="summaryTitle">
        <div class="card-head">
            <div>
                <h2 id="summaryTitle">Order summary</h2>
                <p><span data-summary-count>0</span> items</p>
            </div>
        </div>
        <div class="card-body">
            <div class="summary-row"><span>Subtotal</span><span data-summary-subtotal>$0.00</span></div>
            <div class="summary-row"><span>Tax (<?php echo STORE_TAX_RATE * 100; ?>%)</span><span data-summary-tax>$0.00</span></div>
            <div class="summary-row summary-total"><span>Total</span><span data-summary-total>$0.00</span></div>
            <div class="pos-actions">
                <a href="Checkout.php" class="btn btn-primary btn-lg" data-checkout-link aria-disabled="true"><?php echo icon('lock'); ?> Proceed to checkout</a>
                <a href="shop.php" class="btn btn-secondary"><?php echo icon('store'); ?> Continue shopping</a>
            </div>
        </div>
    </aside>
</div>

<?php layout_end(['store.js']); ?>
