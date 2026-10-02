<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/orders.php';
require_user();

$customer = current_user_row($conn);
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
// Customers can only open their own orders: anything else looks like "not found".
$order = ($id && $customer && orders_enabled($conn)) ? fetch_order($conn, $id, (int) $customer['id']) : null;
if (!$order) {
    flash('error', 'We couldn’t find that order.');
    redirect('orders.php');
}
$justPlaced = isset($_GET['placed']);

layout_start('Order ' . order_number((int) $order['id']), 'orders');
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="orders.php">My orders</a> <?php echo icon('chevron-right'); ?> <span><?php echo order_number((int) $order['id']); ?></span>
</nav>

<?php if ($justPlaced): ?>
    <div class="placed-banner" data-clear-cart>
        <span class="placed-icon"><?php echo icon('check-circle-2'); ?></span>
        <div>
            <h1>Thank you for your order!</h1>
            <p>Order <strong><?php echo order_number((int) $order['id']); ?></strong> was placed and is now <?php echo e(strtolower(ORDER_STATUSES[$order['status']][0])); ?>.</p>
        </div>
        <a href="shop.php" class="btn btn-secondary"><?php echo icon('store'); ?> Continue shopping</a>
    </div>
<?php else: ?>
    <div class="page-head">
        <div>
            <h1>Order <?php echo order_number((int) $order['id']); ?></h1>
            <p>Placed <?php echo e(date('F j, Y', strtotime($order['created_at']))); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/order_view.php'; ?>

<?php layout_end(['store.js']); ?>
