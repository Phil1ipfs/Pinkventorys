<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/orders.php';
require_admin();

if (!orders_enabled($conn)) {
    redirect('admin_orders.php');
}

$id = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    redirect('admin_orders.php');
}

// Status change (CSRF is verified in bootstrap.php for every POST request).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = (string) ($_POST['status'] ?? '');
    $error = change_order_status($conn, $id, $newStatus);
    if ($error) {
        flash('error', $error);
    } elseif ($newStatus === 'cancelled') {
        flash('success', 'Order ' . order_number($id) . ' was cancelled and its items were returned to stock.');
    } else {
        flash('success', 'Order ' . order_number($id) . ' is now ' . strtolower(ORDER_STATUSES[$newStatus][0]) . '.');
    }
    redirect('admin_order.php?id=' . $id);
}

$order = fetch_order($conn, $id);
if (!$order) {
    flash('error', 'That order no longer exists.');
    redirect('admin_orders.php');
}
$next = ORDER_TRANSITIONS[$order['status']] ?? [];
$actionLabels = [
    'processing' => ['Mark as processing', 'refresh-cw', 'btn-secondary'],
    'completed'  => ['Mark as completed', 'package-check', 'btn-primary'],
    'cancelled'  => ['Cancel order', 'x-circle', 'btn-danger-soft'],
];

layout_start('Order ' . order_number((int) $order['id']), 'orders');
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="admin_orders.php">Orders</a> <?php echo icon('chevron-right'); ?> <span><?php echo order_number((int) $order['id']); ?></span>
</nav>
<div class="page-head">
    <div>
        <h1>Order <?php echo order_number((int) $order['id']); ?></h1>
        <p><?php echo e($order['customer_name']); ?> · placed <?php echo e(date('M j, Y · g:i A', strtotime($order['created_at']))); ?></p>
    </div>
    <div class="page-actions">
        <?php if (!$next): ?>
            <span class="muted">This order is <?php echo e(strtolower(ORDER_STATUSES[$order['status']][0])); ?> and can no longer be changed.</span>
        <?php endif; ?>
        <?php foreach ($next as $status): [$label, $iconName, $class] = $actionLabels[$status]; ?>
            <form method="post" action="admin_order.php"
                <?php if ($status === 'cancelled'): ?>
                    data-confirm="<?php echo e(order_number((int) $order['id'])); ?> will be cancelled and its items returned to stock. This can't be undone."
                    data-confirm-title="Cancel this order?" data-confirm-label="Cancel order"
                <?php endif; ?>>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int) $order['id']; ?>">
                <input type="hidden" name="status" value="<?php echo $status; ?>">
                <button type="submit" class="btn <?php echo $class; ?>"><?php echo icon($iconName); ?> <?php echo e($label); ?></button>
            </form>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/order_view.php'; ?>

<?php layout_end(); ?>
