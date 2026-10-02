<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/orders.php';
require_admin();

$fullname = current_display_name($conn);

// Every figure below comes from the inventory and users tables.
$levels = stock_level_sql();
$lowSql = $levels['low'];
$outSql = $levels['out'];

$totals = $conn->query("SELECT
        COUNT(*) AS products,
        COALESCE(SUM(stock), 0) AS units,
        COALESCE(SUM(stock * price), 0) AS value,
        COALESCE(SUM($lowSql), 0) AS low,
        COALESCE(SUM($outSql), 0) AS out_of_stock,
        COUNT(DISTINCT category) AS categories
    FROM inventory")->fetch_assoc();

$products = (int) $totals['products'];
$low = (int) $totals['low'];
$out = (int) $totals['out_of_stock'];
$healthy = max($products - $low - $out, 0);
$customers = (int) $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

// Inventory value by category (top 6, the rest folded into "Other")
$categoryRows = $conn->query("SELECT category, COUNT(*) AS items, COALESCE(SUM(stock * price), 0) AS value
    FROM inventory GROUP BY category ORDER BY value DESC")->fetch_all(MYSQLI_ASSOC);
if (count($categoryRows) > 6) {
    $rest = array_slice($categoryRows, 5);
    $categoryRows = array_slice($categoryRows, 0, 5);
    $categoryRows[] = [
        'category' => 'Other',
        'items' => array_sum(array_column($rest, 'items')),
        'value' => array_sum(array_column($rest, 'value')),
    ];
}
$maxCategoryValue = max(array_merge([0.01], array_map('floatval', array_column($categoryRows, 'value'))));

$attention = $conn->query("SELECT id, product_name, category, stock, status FROM inventory
    WHERE $lowSql OR $outSql ORDER BY stock ASC, product_name LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$recent = $conn->query("SELECT id, product_name, category, stock, status, last_updated FROM inventory
    ORDER BY last_updated DESC, id DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

$statusParts = [
    ['In stock', $healthy, 'var(--ok-mark)', 'in-stock'],
    ['Low stock', $low, 'var(--warn-mark)', 'low-stock'],
    ['Out of stock', $out, 'var(--danger-mark)', 'out-of-stock'],
];

// Orders & revenue (only once the orders migration has been applied).
// Revenue counts every order that wasn't cancelled.
$ordering = orders_enabled($conn);
$orderStats = ['orders' => 0, 'revenue' => 0.0, 'pending' => 0];
$salesDays = [];
$recentOrders = [];
$periodRevenue = 0.0;
$periodOrders = 0;
const SALES_DAYS = 14;
if ($ordering) {
    $row = $conn->query("SELECT COUNT(*) AS orders,
            COALESCE(SUM(CASE WHEN " . revenue_sql() . " THEN total END), 0) AS revenue,
            COALESCE(SUM(status = 'pending'), 0) AS pending
        FROM orders")->fetch_assoc();
    $orderStats = ['orders' => (int) $row['orders'], 'revenue' => (float) $row['revenue'], 'pending' => (int) $row['pending']];

    // Daily revenue for the last SALES_DAYS days (days without orders show as 0).
    // Use the database's date so the days line up with created_at whatever the PHP time zone.
    $today = (string) $conn->query("SELECT CURDATE()")->fetch_row()[0];
    $from = (new DateTime($today))->modify('-' . (SALES_DAYS - 1) . ' days');
    for ($d = clone $from, $i = 0; $i < SALES_DAYS; $i++, $d->modify('+1 day')) {
        $salesDays[$d->format('Y-m-d')] = ['revenue' => 0.0, 'orders' => 0, 'label' => $d->format('M j'), 'short' => $d->format('j')];
    }
    $fromSql = $from->format('Y-m-d 00:00:00');
    $stmt = $conn->prepare("SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total) AS revenue
        FROM orders WHERE created_at >= ? AND " . revenue_sql() . " GROUP BY DATE(created_at)");
    $stmt->bind_param("s", $fromSql);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        if (isset($salesDays[$r['day']])) {
            $salesDays[$r['day']]['revenue'] = (float) $r['revenue'];
            $salesDays[$r['day']]['orders'] = (int) $r['orders'];
        }
    }
    $periodRevenue = array_sum(array_column($salesDays, 'revenue'));
    $periodOrders = array_sum(array_column($salesDays, 'orders'));

    $recentOrders = $conn->query("SELECT id, customer_name, status, total, created_at FROM orders
        ORDER BY created_at DESC, id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
}
$maxDay = max(array_merge([0.01], array_column($salesDays, 'revenue')));

layout_start('Dashboard', 'dashboard');
?>

<div class="welcome">
    <div>
        <h1>Welcome back, <?php echo e($fullname); ?>!</h1>
        <p>Here's your inventory overview for today.</p>
    </div>
    <div class="page-actions">
        <a href="inventory.php" class="btn btn-secondary"><?php echo icon('package'); ?> View inventory</a>
        <a href="add_product.php" class="btn btn-primary"><?php echo icon('plus'); ?> Add product</a>
    </div>
</div>

<section class="stats" aria-label="Inventory summary">
    <a href="inventory.php" class="card stat">
        <div class="stat-top">Total products <span class="stat-icon"><?php echo icon('package'); ?></span></div>
        <div class="stat-value num"><?php echo number_format($products); ?></div>
        <div class="stat-meta">Across <?php echo (int) $totals['categories']; ?> <?php echo (int) $totals['categories'] === 1 ? 'category' : 'categories'; ?></div>
    </a>
    <div class="card stat">
        <div class="stat-top">Total stock quantity <span class="stat-icon is-info"><?php echo icon('boxes'); ?></span></div>
        <div class="stat-value num"><?php echo number_format((int) $totals['units']); ?></div>
        <div class="stat-meta">Units on hand</div>
    </div>
    <?php if ($ordering): ?>
        <a href="admin_orders.php" class="card stat">
            <div class="stat-top">Total orders <span class="stat-icon is-warn"><?php echo icon('receipt'); ?></span></div>
            <div class="stat-value num"><?php echo number_format($orderStats['orders']); ?></div>
            <div class="stat-meta"><?php echo number_format($orderStats['pending']); ?> pending</div>
        </a>
        <a href="admin_orders.php" class="card stat">
            <div class="stat-top">Total revenue <span class="stat-icon is-ok"><?php echo icon('wallet'); ?></span></div>
            <div class="stat-value num"><?php echo money($orderStats['revenue']); ?></div>
            <div class="stat-meta">Excludes cancelled orders</div>
        </a>
    <?php else: ?>
        <div class="card stat">
            <div class="stat-top">Inventory value <span class="stat-icon is-ok"><?php echo icon('wallet'); ?></span></div>
            <div class="stat-value num"><?php echo money((float) $totals['value']); ?></div>
            <div class="stat-meta">Stock on hand × price</div>
        </div>
        <a href="inventory.php?status=attention" class="card stat">
            <div class="stat-top">Needs attention <span class="stat-icon is-warn"><?php echo icon('bell-ring'); ?></span></div>
            <div class="stat-value num"><?php echo number_format($low + $out); ?></div>
            <div class="stat-meta"><strong class="is-warn"><?php echo $low; ?> low</strong> · <strong class="is-danger"><?php echo $out; ?> out of stock</strong></div>
        </a>
    <?php endif; ?>
</section>

<div class="grid-2">
    <section class="card" aria-labelledby="salesTitle">
        <div class="card-head">
            <div>
                <h2 id="salesTitle">Sales overview</h2>
                <p><?php echo $ordering ? 'Daily revenue, last ' . SALES_DAYS . ' days' : 'Orders and revenue'; ?></p>
            </div>
            <?php if ($ordering): ?>
                <a href="admin_orders.php" class="link-more">All orders <?php echo icon('arrow-right'); ?></a>
            <?php else: ?>
                <span class="placeholder-tag"><?php echo icon('info', 'icon-sm'); ?> Not set up</span>
            <?php endif; ?>
        </div>
        <?php if (!$ordering): ?>
            <div class="empty empty-sm">
                <span class="empty-icon"><?php echo icon('bar-chart-3'); ?></span>
                <h3>No sales data yet</h3>
                <p>Run the orders migration to switch on ordering. Revenue and order history will appear here.</p>
            </div>
        <?php else: ?>
            <div class="card-body">
                <div class="sales-kpis">
                    <div><span>Revenue</span><strong class="num"><?php echo money($periodRevenue); ?></strong></div>
                    <div><span>Orders</span><strong class="num"><?php echo number_format($periodOrders); ?></strong></div>
                    <div><span>Avg. order</span><strong class="num"><?php echo $periodOrders ? money($periodRevenue / $periodOrders) : '—'; ?></strong></div>
                </div>
                <?php if ($periodOrders === 0): ?>
                    <p class="muted sales-empty">No orders in the last <?php echo SALES_DAYS; ?> days.</p>
                <?php endif; ?>
                <ol class="vbars" aria-label="Daily revenue, last <?php echo SALES_DAYS; ?> days">
                    <?php foreach ($salesDays as $day): $h = $day['revenue'] / $maxDay * 100; ?>
                        <li tabindex="0" title="<?php echo e($day['label'] . ': ' . money($day['revenue']) . ' from ' . $day['orders'] . ' ' . ($day['orders'] === 1 ? 'order' : 'orders')); ?>"
                            aria-label="<?php echo e($day['label'] . ': ' . money($day['revenue']) . ', ' . $day['orders'] . ' orders'); ?>">
                            <span class="vbar"><span style="height: <?php echo round($h, 2); ?>%"></span></span>
                            <span class="vbar-label"><?php echo e($day['short']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="statusTitle">
        <div class="card-head">
            <div>
                <h2 id="statusTitle">Stock status</h2>
                <p><?php echo number_format($products); ?> products · <?php echo number_format($customers); ?> registered <?php echo $customers === 1 ? 'customer' : 'customers'; ?></p>
            </div>
        </div>
        <div class="card-body">
            <?php if ($products > 0): ?>
                <div class="segbar" role="img" aria-label="<?php echo e("$healthy in stock, $low low stock, $out out of stock"); ?>">
                    <?php foreach ($statusParts as [$label, $count, $color]): if ($count > 0): ?>
                        <span style="flex: <?php echo $count; ?>; background: <?php echo $color; ?>" title="<?php echo e("$label: $count"); ?>"></span>
                    <?php endif; endforeach; ?>
                </div>
            <?php endif; ?>
            <ul class="legend">
                <?php foreach ($statusParts as [$label, $count, $color, $slug]): ?>
                    <li>
                        <span class="swatch" style="background: <?php echo $color; ?>"></span>
                        <a class="label" href="inventory.php?status=<?php echo $slug; ?>"><?php echo e($label); ?></a>
                        <span class="value"><?php echo number_format($count); ?></span>
                        <span class="pct"><?php echo $products ? round($count / $products * 100) : 0; ?>%</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
</div>

<div class="grid-2">
    <section class="card" aria-labelledby="valueTitle">
        <div class="card-head">
            <div>
                <h2 id="valueTitle">Inventory value by category</h2>
                <p>Stock on hand × unit price</p>
            </div>
        </div>
        <div class="card-body">
            <?php if (!$categoryRows): ?>
                <div class="empty empty-sm">
                    <span class="empty-icon"><?php echo icon('bar-chart-3'); ?></span>
                    <h3>No data yet</h3>
                    <p>Add products to see how your stock value splits across categories.</p>
                    <a href="add_product.php" class="btn btn-primary btn-sm"><?php echo icon('plus', 'icon-sm'); ?> Add product</a>
                </div>
            <?php else: ?>
                <ul class="hbars">
                    <?php foreach ($categoryRows as $row):
                        $value = (float) $row['value'];
                        $label = ucfirst((string) $row['category']);
                        $pct = max(0, min(100, $value / $maxCategoryValue * 100)); ?>
                        <li class="hbar" tabindex="0" title="<?php echo e($label . ': ' . money($value) . ' across ' . $row['items'] . ' products'); ?>">
                            <span class="hbar-label"><?php echo e($label); ?></span>
                            <span class="hbar-track"><span class="hbar-fill" style="width: <?php echo round($pct, 2); ?>%"></span></span>
                            <span class="hbar-value"><?php echo money($value); ?><span class="hbar-sub"><?php echo (int) $row['items']; ?> <?php echo (int) $row['items'] === 1 ? 'product' : 'products'; ?></span></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <section class="card" aria-labelledby="lowTitle">
        <div class="card-head">
            <div>
                <h2 id="lowTitle">Low stock alerts</h2>
                <p>At or below <?php echo LOW_STOCK_THRESHOLD; ?> units, or marked low/out</p>
            </div>
            <a href="inventory.php?status=attention" class="link-more">View all <?php echo icon('arrow-right'); ?></a>
        </div>
        <?php if (!$attention): ?>
            <div class="empty empty-sm">
                <span class="empty-icon"><?php echo icon('package-check'); ?></span>
                <h3>All stocked up</h3>
                <p>No products are running low right now.</p>
            </div>
        <?php else: ?>
            <ul class="rows">
                <?php foreach ($attention as $item):
                    $stock = (int) $item['stock'];
                    $level = stock_level($stock, $item['status']);
                    $fill = min(100, $stock / max(LOW_STOCK_THRESHOLD, 1) * 100); ?>
                    <li class="row-item">
                        <div class="grow">
                            <strong><a href="update_product.php?id=<?php echo (int) $item['id']; ?>"><?php echo e($item['product_name']); ?></a></strong>
                            <span><?php echo e(ucfirst($item['category'])); ?></span>
                        </div>
                        <span class="meter" title="<?php echo $stock; ?> of <?php echo LOW_STOCK_THRESHOLD; ?>"><span style="width: <?php echo $fill; ?>%; background: <?php echo $level === 'out' ? 'var(--danger-mark)' : 'var(--warn-mark)'; ?>"></span></span>
                        <span class="badge badge-<?php echo $level; ?>"><?php echo $stock; ?> left</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php if ($ordering): ?>
    <section class="card section-gap" aria-labelledby="recentOrdersTitle">
        <div class="card-head">
            <div>
                <h2 id="recentOrdersTitle">Recent orders</h2>
                <p>Latest customer orders</p>
            </div>
            <a href="admin_orders.php" class="link-more">View all <?php echo icon('arrow-right'); ?></a>
        </div>
        <?php if (!$recentOrders): ?>
            <div class="empty empty-sm">
                <span class="empty-icon"><?php echo icon('receipt'); ?></span>
                <h3>No orders yet</h3>
                <p>Orders placed by customers will show up here.</p>
            </div>
        <?php else: ?>
            <ul class="rows">
                <?php foreach ($recentOrders as $o): ?>
                    <li class="row-item">
                        <span class="thumb"><?php echo icon('receipt'); ?></span>
                        <div class="grow">
                            <strong><a href="admin_order.php?id=<?php echo (int) $o['id']; ?>"><?php echo order_number((int) $o['id']); ?></a> · <?php echo e($o['customer_name']); ?></strong>
                            <span><time datetime="<?php echo e($o['created_at']); ?>"><?php echo e(time_ago($o['created_at'])); ?></time></span>
                        </div>
                        <?php echo order_status_badge($o['status']); ?>
                        <strong class="num"><?php echo money((float) $o['total']); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="grid-2">
    <section class="card" aria-labelledby="recentTitle">
        <div class="card-head">
            <div>
                <h2 id="recentTitle">Recent activity</h2>
                <p>Latest product changes</p>
            </div>
            <a href="inventory.php?sort=updated&amp;dir=desc" class="link-more">View all <?php echo icon('arrow-right'); ?></a>
        </div>
        <?php if (!$recent): ?>
            <div class="empty empty-sm">
                <span class="empty-icon"><?php echo icon('history'); ?></span>
                <h3>No activity yet</h3>
                <p>Products you add or edit will show up here.</p>
            </div>
        <?php else: ?>
            <ul class="rows">
                <?php foreach ($recent as $item): $level = stock_level((int) $item['stock'], $item['status']); [$pIcon, $tone] = product_visual($item['category']); ?>
                    <li class="row-item">
                        <span class="thumb tone-<?php echo $tone; ?>"><?php echo icon($pIcon); ?></span>
                        <div class="grow">
                            <strong><a href="update_product.php?id=<?php echo (int) $item['id']; ?>"><?php echo e($item['product_name']); ?></a></strong>
                            <span><?php echo e(ucfirst($item['category'])); ?> · <?php echo (int) $item['stock']; ?> in stock</span>
                        </div>
                        <span class="badge badge-<?php echo $level; ?>"><?php echo level_label($level); ?></span>
                        <time datetime="<?php echo e($item['last_updated']); ?>" title="<?php echo e($item['last_updated']); ?>"><?php echo e(time_ago($item['last_updated'])); ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="quickTitle">
        <div class="card-head">
            <div>
                <h2 id="quickTitle">Quick actions</h2>
                <p>Common admin tasks</p>
            </div>
        </div>
        <div class="card-body quick-actions">
            <a href="add_product.php" class="quick"><span class="stat-icon"><?php echo icon('plus'); ?></span><span><strong>Add product</strong><small>Create a new inventory item</small></span></a>
            <a href="inventory.php?status=attention" class="quick"><span class="stat-icon is-warn"><?php echo icon('bell-ring'); ?></span><span><strong>Review low stock</strong><small><?php echo $low + $out; ?> need attention</small></span></a>
            <a href="add_user.php" class="quick"><span class="stat-icon is-info"><?php echo icon('user-plus'); ?></span><span><strong>Add user</strong><small>Create a customer account</small></span></a>
            <a href="users.php" class="quick"><span class="stat-icon is-ok"><?php echo icon('users'); ?></span><span><strong>Manage users</strong><small><?php echo number_format($customers); ?> registered</small></span></a>
        </div>
    </section>
</div>

<?php layout_end(); ?>
