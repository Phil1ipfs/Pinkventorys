<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/orders.php';
require_admin();

const ADMIN_ORDERS_PER_PAGE = 20;

$ordering = orders_enabled($conn);
$status = isset(ORDER_STATUSES[$_GET['status'] ?? '']) ? (string) $_GET['status'] : '';
$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

function orders_url(array $changes = []): string
{
    global $status, $q, $page;
    $params = array_merge(compact('status', 'q', 'page'), $changes);
    if ((int) ($params['page'] ?? 1) <= 1) {
        unset($params['page']);
    }
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return 'admin_orders.php' . ($params ? '?' . http_build_query($params) : '');
}

$stats = ['orders' => 0, 'revenue' => 0.0, 'pending' => 0, 'completed' => 0];
$counts = array_fill_keys(array_keys(ORDER_STATUSES), 0);
$orders = [];
$total = 0;
$pages = 1;

if ($ordering) {
    $row = $conn->query("SELECT COUNT(*) AS orders,
            COALESCE(SUM(CASE WHEN " . revenue_sql() . " THEN total END), 0) AS revenue
        FROM orders")->fetch_assoc();
    $stats['orders'] = (int) $row['orders'];
    $stats['revenue'] = (float) $row['revenue'];
    foreach ($conn->query("SELECT status, COUNT(*) AS n FROM orders GROUP BY status")->fetch_all(MYSQLI_ASSOC) as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }

    // Search: order number (PV-00012 or 12), customer name or email.
    $where = [];
    $types = '';
    $params = [];
    if ($q !== '') {
        $digits = ltrim(preg_replace('/\D+/', '', $q), '0');
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(o.customer_name LIKE ? OR o.customer_email LIKE ?' . ($digits !== '' ? ' OR o.id = ?' : '') . ')';
        array_push($params, $like, $like);
        $types .= 'ss';
        if ($digits !== '') {
            $params[] = (int) $digits;
            $types .= 'i';
        }
    }
    if ($status !== '') {
        $where[] = 'o.status = ?';
        $params[] = $status;
        $types .= 's';
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders o $whereSql");
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_row()[0];
    $pages = max(1, (int) ceil($total / ADMIN_ORDERS_PER_PAGE));
    $page = min($page, $pages);
    $offset = ($page - 1) * ADMIN_ORDERS_PER_PAGE;

    $stmt = $conn->prepare("SELECT o.id, o.customer_name, o.customer_email, o.status, o.payment_method, o.total, o.created_at,
            (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = o.id) AS items
        FROM orders o $whereSql ORDER BY o.created_at DESC, o.id DESC LIMIT ? OFFSET ?");
    $limit = ADMIN_ORDERS_PER_PAGE;
    $listParams = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($types . 'ii', ...$listParams);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

layout_start('Orders', 'orders');
?>

<div class="page-head">
    <div>
        <h1>Orders</h1>
        <p>Every order placed by your customers.</p>
    </div>
</div>

<?php if (!$ordering): ?>
    <section class="card empty">
        <span class="empty-icon"><?php echo icon('receipt'); ?></span>
        <h3>Orders aren't set up yet</h3>
        <p>Run <code>database/migrations/001_add_orders.sql</code> in phpMyAdmin to switch on ordering. Existing data is not changed.</p>
    </section>
<?php else: ?>
    <section class="stats" aria-label="Order summary">
        <div class="card stat">
            <div class="stat-top">Total orders <span class="stat-icon"><?php echo icon('receipt'); ?></span></div>
            <div class="stat-value num"><?php echo number_format($stats['orders']); ?></div>
            <div class="stat-meta">All time</div>
        </div>
        <div class="card stat">
            <div class="stat-top">Revenue <span class="stat-icon is-ok"><?php echo icon('wallet'); ?></span></div>
            <div class="stat-value num"><?php echo money($stats['revenue']); ?></div>
            <div class="stat-meta">Excludes cancelled orders</div>
        </div>
        <a href="<?php echo e(orders_url(['status' => 'pending', 'page' => 1])); ?>" class="card stat">
            <div class="stat-top">Pending <span class="stat-icon is-warn"><?php echo icon('clock'); ?></span></div>
            <div class="stat-value num"><?php echo number_format($counts['pending']); ?></div>
            <div class="stat-meta">Waiting to be processed</div>
        </a>
        <a href="<?php echo e(orders_url(['status' => 'completed', 'page' => 1])); ?>" class="card stat">
            <div class="stat-top">Completed <span class="stat-icon is-info"><?php echo icon('package-check'); ?></span></div>
            <div class="stat-value num"><?php echo number_format($counts['completed']); ?></div>
            <div class="stat-meta"><?php echo number_format($counts['cancelled']); ?> cancelled</div>
        </a>
    </section>

    <section class="card">
        <nav class="tabs" aria-label="Filter by status">
            <a class="tab" href="<?php echo e(orders_url(['status' => '', 'page' => 1])); ?>" <?php echo $status === '' ? 'aria-current="true"' : ''; ?>>All <span class="count"><?php echo number_format($stats['orders']); ?></span></a>
            <?php foreach (ORDER_STATUSES as $key => [$label]): ?>
                <a class="tab" href="<?php echo e(orders_url(['status' => $key, 'page' => 1])); ?>" <?php echo $status === $key ? 'aria-current="true"' : ''; ?>><?php echo e($label); ?> <span class="count"><?php echo number_format($counts[$key]); ?></span></a>
            <?php endforeach; ?>
        </nav>
        <form class="toolbar" method="get" action="admin_orders.php" role="search">
            <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?php echo e($status); ?>"><?php endif; ?>
            <div class="search">
                <?php echo icon('search', 'icon-sm'); ?>
                <label for="orderSearch" class="sr-only">Search orders</label>
                <input type="search" id="orderSearch" name="q" class="input" value="<?php echo e($q); ?>" placeholder="Order number, customer name or email…">
            </div>
            <button type="submit" class="btn btn-secondary"><?php echo icon('filter', 'icon-sm'); ?> Search</button>
            <?php if ($q !== '' || $status !== ''): ?><a href="admin_orders.php" class="btn btn-ghost"><?php echo icon('x', 'icon-sm'); ?> Clear</a><?php endif; ?>
        </form>

        <?php if (!$orders): ?>
            <div class="empty">
                <span class="empty-icon"><?php echo icon($stats['orders'] ? 'search' : 'receipt'); ?></span>
                <h3><?php echo $stats['orders'] ? 'No orders match' : 'No orders yet'; ?></h3>
                <p><?php echo $stats['orders'] ? 'Try a different search or status.' : 'Orders placed by customers will appear here.'; ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table table-cards">
                    <thead>
                        <tr>
                            <th scope="col">Order</th>
                            <th scope="col">Customer</th>
                            <th scope="col">Items</th>
                            <th scope="col">Payment</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="right">Total</th>
                            <th scope="col" class="actions"><span class="sr-only">Manage</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="cell-first">
                                    <div class="cell-main">
                                        <span class="thumb"><?php echo icon('receipt'); ?></span>
                                        <div>
                                            <strong><a href="admin_order.php?id=<?php echo (int) $o['id']; ?>"><?php echo order_number((int) $o['id']); ?></a></strong>
                                            <span><time datetime="<?php echo e($o['created_at']); ?>" title="<?php echo e($o['created_at']); ?>"><?php echo e(time_ago($o['created_at'])); ?></time></span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Customer"><strong><?php echo e($o['customer_name']); ?></strong><br><span class="muted"><?php echo e($o['customer_email']); ?></span></td>
                                <td data-label="Items" class="num"><?php echo (int) $o['items']; ?></td>
                                <td data-label="Payment"><?php echo e(PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method']); ?></td>
                                <td data-label="Status"><?php echo order_status_badge($o['status']); ?></td>
                                <td data-label="Total" class="right num"><strong><?php echo money((float) $o['total']); ?></strong></td>
                                <td class="actions"><a href="admin_order.php?id=<?php echo (int) $o['id']; ?>" class="btn btn-secondary btn-sm"><?php echo icon('pencil', 'icon-sm'); ?> Manage</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <span class="page-info">Showing <strong><?php echo $offset + 1; ?>–<?php echo $offset + count($orders); ?></strong> of <strong><?php echo number_format($total); ?></strong></span>
                <?php if ($pages > 1): ?>
                    <nav class="pager" aria-label="Pagination">
                        <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                            <?php if ($p === $page): ?><span aria-current="page"><?php echo $p; ?></span>
                            <?php else: ?><a href="<?php echo e(orders_url(['page' => $p])); ?>"><?php echo $p; ?></a><?php endif; ?>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php layout_end(); ?>
