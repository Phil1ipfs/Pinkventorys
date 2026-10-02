<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/orders.php';
require_user();

const ORDERS_PER_PAGE = 15;

$customer = current_user_row($conn);
$ordering = orders_enabled($conn);
$orders = [];
$total = 0;
$page = max(1, (int) ($_GET['page'] ?? 1));
$pages = 1;

if ($ordering && $customer) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->bind_param("i", $customer['id']);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_row()[0];
    $pages = max(1, (int) ceil($total / ORDERS_PER_PAGE));
    $page = min($page, $pages);
    $offset = ($page - 1) * ORDERS_PER_PAGE;

    $stmt = $conn->prepare("SELECT o.id, o.status, o.payment_method, o.total, o.created_at,
            (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = o.id) AS items
        FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC, o.id DESC LIMIT ? OFFSET ?");
    $limit = ORDERS_PER_PAGE;
    $stmt->bind_param("iii", $customer['id'], $limit, $offset);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

layout_start('My orders', 'orders');
?>

<div class="page-head">
    <div>
        <h1>My orders</h1>
        <p><?php echo $ordering ? 'Track and review the orders you have placed.' : 'Your order history will appear here.'; ?></p>
    </div>
</div>

<section class="card">
    <?php if (!$ordering): ?>
        <div class="empty">
            <span class="empty-icon"><?php echo icon('receipt'); ?></span>
            <h3>Online ordering isn't switched on yet</h3>
            <p>Once ordering is enabled, every order you place will be listed here.</p>
            <a href="shop.php" class="btn btn-primary"><?php echo icon('store'); ?> Browse products</a>
        </div>
    <?php elseif (!$orders): ?>
        <div class="empty">
            <span class="empty-icon"><?php echo icon('receipt'); ?></span>
            <h3>No orders yet</h3>
            <p>When you place an order it will show up here with its status.</p>
            <a href="shop.php" class="btn btn-primary"><?php echo icon('store'); ?> Start shopping</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-cards">
                <thead>
                    <tr>
                        <th scope="col">Order</th>
                        <th scope="col">Items</th>
                        <th scope="col">Payment</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="right">Total</th>
                        <th scope="col" class="actions"><span class="sr-only">View</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="cell-first">
                                <div class="cell-main">
                                    <span class="thumb"><?php echo icon('receipt'); ?></span>
                                    <div>
                                        <strong><a href="order.php?id=<?php echo (int) $o['id']; ?>"><?php echo order_number((int) $o['id']); ?></a></strong>
                                        <span><time datetime="<?php echo e($o['created_at']); ?>"><?php echo e(date('M j, Y · g:i A', strtotime($o['created_at']))); ?></time></span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Items" class="num"><?php echo (int) $o['items']; ?></td>
                            <td data-label="Payment"><?php echo e(PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method']); ?></td>
                            <td data-label="Status"><?php echo order_status_badge($o['status']); ?></td>
                            <td data-label="Total" class="right num"><strong><?php echo money((float) $o['total']); ?></strong></td>
                            <td class="actions">
                                <a href="order.php?id=<?php echo (int) $o['id']; ?>" class="btn btn-secondary btn-sm"><?php echo icon('eye', 'icon-sm'); ?> View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
            <div class="pagination">
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $pages; ?> · <?php echo $total; ?> orders</span>
                <nav class="pager" aria-label="Pagination">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <?php if ($p === $page): ?><span aria-current="page"><?php echo $p; ?></span>
                        <?php else: ?><a href="orders.php?page=<?php echo $p; ?>"><?php echo $p; ?></a><?php endif; ?>
                    <?php endfor; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php layout_end(['store.js']); ?>
