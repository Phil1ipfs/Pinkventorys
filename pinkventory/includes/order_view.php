<?php
/**
 * Order details (items, totals, customer, timeline) shared by the customer
 * page (order.php) and the admin page (admin_order.php). Expects $order from
 * fetch_order().
 */
if (!defined('SESSION_IDLE_TIMEOUT')) {
    http_response_code(404);
    exit;
}

$steps = ['pending' => 'Order placed', 'processing' => 'Processing', 'completed' => 'Completed'];
$stepKeys = array_keys($steps);
$reached = $order['status'] === 'cancelled' ? -1 : array_search($order['status'], $stepKeys, true);
?>
<div class="pos">
    <div class="stack">
        <section class="card" aria-labelledby="itemsTitle">
            <div class="card-head">
                <div>
                    <h2 id="itemsTitle">Items</h2>
                    <p>Prices as charged when the order was placed</p>
                </div>
            </div>
            <ul class="rows">
                <?php foreach ($order['items'] as $item): [$iconName, $tone] = product_visual($item['category'] ?? ''); ?>
                    <li class="row-item">
                        <span class="thumb tone-<?php echo $tone; ?>"><?php echo icon($iconName); ?></span>
                        <div class="grow">
                            <strong><?php echo e($item['product_name']); ?></strong>
                            <span><?php echo (int) $item['quantity']; ?> × <?php echo money((float) $item['unit_price']); ?><?php echo $item['product_id'] === null ? ' · product no longer listed' : ''; ?></span>
                        </div>
                        <strong class="num"><?php echo money((float) $item['line_total']); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="card" aria-labelledby="progressTitle">
            <div class="card-head">
                <div>
                    <h2 id="progressTitle">Order status</h2>
                    <p>Last updated <time datetime="<?php echo e($order['updated_at']); ?>"><?php echo e(time_ago($order['updated_at'])); ?></time></p>
                </div>
                <?php echo order_status_badge($order['status']); ?>
            </div>
            <div class="card-body">
                <?php if ($order['status'] === 'cancelled'): ?>
                    <div class="alert alert-info alert-flat"><?php echo icon('info'); ?><div>This order was cancelled and its items were returned to stock.</div></div>
                <?php else: ?>
                    <ol class="timeline">
                        <?php foreach ($stepKeys as $i => $key): ?>
                            <li class="<?php echo $i <= $reached ? 'done' : ''; ?><?php echo $i === $reached ? ' current' : ''; ?>">
                                <span class="dot"><?php echo $i <= $reached ? icon('check', 'icon-sm') : ''; ?></span>
                                <?php echo e($steps[$key]); ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="stack">
        <section class="card summary-card" aria-labelledby="summaryTitle">
            <div class="card-head">
                <div>
                    <h2 id="summaryTitle">Summary</h2>
                    <p><?php echo array_sum(array_map('intval', array_column($order['items'], 'quantity'))); ?> items</p>
                </div>
            </div>
            <div class="card-body">
                <div class="summary-row"><span>Subtotal</span><span><?php echo money((float) $order['subtotal']); ?></span></div>
                <div class="summary-row"><span>Tax</span><span><?php echo money((float) $order['tax']); ?></span></div>
                <div class="summary-row summary-total"><span>Total</span><span><?php echo money((float) $order['total']); ?></span></div>
                <dl class="detail-facts">
                    <div><dt>Payment</dt><dd><?php echo e(PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']); ?> <span class="muted">(recorded only)</span></dd></div>
                    <div><dt>Placed</dt><dd><?php echo e(date('M j, Y · g:i A', strtotime($order['created_at']))); ?></dd></div>
                    <div><dt>Customer</dt><dd><?php echo e($order['customer_name']); ?></dd></div>
                    <div><dt>Email</dt><dd><?php echo e($order['customer_email']); ?></dd></div>
                </dl>
            </div>
        </section>
    </aside>
</div>
