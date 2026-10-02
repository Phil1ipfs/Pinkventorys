<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/store.php';
require_user();

$customer = current_user_row($conn);

layout_start('Checkout', 'cart');
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="cart.php">Cart</a> <?php echo icon('chevron-right'); ?> <span>Checkout</span>
</nav>
<div class="page-head">
    <div>
        <h1>Checkout</h1>
        <p>Confirm your details and review your order.</p>
    </div>
</div>

<div class="pos" data-cart-page data-checkout data-tax-rate="<?php echo STORE_TAX_RATE; ?>">
    <div class="stack">
        <section class="card" aria-labelledby="customerTitle">
            <div class="card-head">
                <div>
                    <h2 id="customerTitle">Customer information</h2>
                    <p>From your account</p>
                </div>
                <a href="profile.php" class="btn btn-secondary btn-sm no-print"><?php echo icon('pencil', 'icon-sm'); ?> Edit</a>
            </div>
            <dl class="card-body detail-facts detail-facts-2">
                <div><dt>Full name</dt><dd><?php echo e($customer['fullname'] ?? ''); ?></dd></div>
                <div><dt>Email address</dt><dd><?php echo e($customer['email'] ?? ''); ?></dd></div>
            </dl>
        </section>

        <section class="card" aria-labelledby="itemsTitle">
            <div class="card-head">
                <div>
                    <h2 id="itemsTitle">Order items</h2>
                    <p>Prices are checked against current stock</p>
                </div>
                <a href="cart.php" class="btn btn-secondary btn-sm no-print"><?php echo icon('shopping-cart', 'icon-sm'); ?> Edit cart</a>
            </div>
            <div class="cart-loading empty empty-sm" data-cart-loading>
                <span class="spinner"></span>
                <p>Checking current prices and stock…</p>
            </div>
            <div class="empty empty-sm" data-cart-empty hidden>
                <span class="empty-icon"><?php echo icon('shopping-cart'); ?></span>
                <h3>Your cart is empty</h3>
                <p>Add products before checking out.</p>
                <a href="shop.php" class="btn btn-primary btn-sm"><?php echo icon('store', 'icon-sm'); ?> Go to shop</a>
            </div>
            <div data-cart-notices></div>
            <ul class="rows" data-checkout-items></ul>
        </section>

        <section class="card no-print" aria-labelledby="paymentTitle">
            <div class="card-head">
                <div>
                    <h2 id="paymentTitle">Payment method</h2>
                    <p>Recorded with your order. No payment is taken online.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="choices choices-2" role="radiogroup" aria-labelledby="paymentTitle">
                    <label class="choice">
                        <input type="radio" name="payment_method" value="cash" checked>
                        <span><?php echo icon('banknote', 'icon-sm'); ?> Cash</span>
                    </label>
                    <label class="choice">
                        <input type="radio" name="payment_method" value="card">
                        <span><?php echo icon('credit-card', 'icon-sm'); ?> Card</span>
                    </label>
                </div>
            </div>
        </section>
    </div>

    <aside class="card summary-card" aria-labelledby="summaryTitle">
        <div class="receipt-head">
            <h2>Pinkventory</h2>
            <p class="muted">Order summary · <span data-receipt-date></span></p>
        </div>
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
            <div class="pos-actions no-print">
                <button type="button" class="btn btn-primary btn-lg" disabled aria-describedby="placeOrderNote"><?php echo icon('lock'); ?> Place order</button>
                <button type="button" class="btn btn-secondary" data-print disabled><?php echo icon('printer'); ?> Print summary</button>
            </div>
            <p class="note no-print" id="placeOrderNote">
                <?php echo icon('info'); ?>
                <span>Online ordering isn't switched on yet, so orders can't be placed or saved. Your cart is kept on this device.</span>
            </p>
        </div>
    </aside>
</div>

<?php layout_end(['store.js']); ?>
