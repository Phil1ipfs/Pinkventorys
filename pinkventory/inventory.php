<?php
require_once 'bootstrap.php';
require_once 'layout.php';
require_login();

const PER_PAGE = 10;

$levels = stock_level_sql();
$statusFilters = [
    ''             => ['All products', null],
    'in-stock'     => ['In stock', $levels['ok']],
    'low-stock'    => ['Low stock', $levels['low']],
    'out-of-stock' => ['Out of stock', $levels['out']],
    'attention'    => ['Needs attention', "({$levels['low']} OR {$levels['out']})"],
];
$sorts = [
    'name'     => 'product_name',
    'category' => 'category',
    'stock'    => 'stock',
    'price'    => 'price',
    'updated'  => 'last_updated',
];

$q = trim((string) ($_GET['q'] ?? ''));
$category = strtolower(trim((string) ($_GET['category'] ?? '')));
$status = isset($statusFilters[$_GET['status'] ?? '']) ? (string) ($_GET['status'] ?? '') : '';
$sort = isset($sorts[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'name';
$dir = ($_GET['dir'] ?? ($sort === 'updated' ? 'desc' : 'asc')) === 'desc' ? 'desc' : 'asc';
$page = max(1, (int) ($_GET['page'] ?? 1));

/** Current listing URL with some parameters changed. */
function inventory_url(array $changes = []): string
{
    global $q, $category, $status, $sort, $dir, $page;
    $params = array_merge(compact('q', 'category', 'status', 'sort', 'dir', 'page'), $changes);
    if ($params['sort'] === 'name' && $params['dir'] === 'asc') {
        unset($params['sort'], $params['dir']);
    }
    if ((int) ($params['page'] ?? 1) <= 1) {
        unset($params['page']);
    }
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return 'inventory.php' . ($params ? '?' . http_build_query($params) : '');
}

// Search + category apply to the counts and the listing; the status tab narrows the listing.
$where = [];
$types = '';
$params = [];
if ($q !== '') {
    $where[] = '(product_name LIKE ? OR category LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($params, $like, $like);
    $types .= 'ss';
}
if ($category !== '') {
    $where[] = 'LOWER(category) = ?';
    $params[] = $category;
    $types .= 's';
}
$baseWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $conn->prepare("SELECT COUNT(*) AS total,
        COALESCE(SUM({$levels['ok']}), 0) AS ok,
        COALESCE(SUM({$levels['low']}), 0) AS low,
        COALESCE(SUM({$levels['out']}), 0) AS out_of_stock
    FROM inventory $baseWhere");
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();
$tabCounts = [
    '' => (int) $counts['total'],
    'in-stock' => (int) $counts['ok'],
    'low-stock' => (int) $counts['low'],
    'out-of-stock' => (int) $counts['out_of_stock'],
    'attention' => (int) $counts['low'] + (int) $counts['out_of_stock'],
];
$totalStore = (int) $conn->query("SELECT COUNT(*) FROM inventory")->fetch_row()[0];

$listWhere = $where;
if ($statusFilters[$status][1]) {
    $listWhere[] = $statusFilters[$status][1];
}
$listWhereSql = $listWhere ? 'WHERE ' . implode(' AND ', $listWhere) : '';
$total = $tabCounts[$status];
$pages = max(1, (int) ceil($total / PER_PAGE));
$page = min($page, $pages);
$offset = ($page - 1) * PER_PAGE;

$orderSql = $sorts[$sort] . ' ' . strtoupper($dir) . ', id ' . strtoupper($dir);
$stmt = $conn->prepare("SELECT id, product_name, category, stock, price, status, last_updated
    FROM inventory $listWhereSql ORDER BY $orderSql LIMIT ? OFFSET ?");
$listTypes = $types . 'ii';
$listParams = array_merge($params, [PER_PAGE, $offset]);
$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categories = product_categories($conn);
$filtered = $q !== '' || $category !== '' || $status !== '';

function sort_header(string $key, string $label, string $class = ''): string
{
    global $sort, $dir;
    $isActive = $sort === $key;
    $nextDir = $isActive && $dir === 'asc' ? 'desc' : 'asc';
    if (!$isActive && in_array($key, ['updated', 'stock', 'price'], true)) {
        $nextDir = $key === 'updated' ? 'desc' : 'asc';
    }
    $iconName = $isActive ? ($dir === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrow-up-down';
    $aria = $isActive ? ' aria-sort="' . ($dir === 'asc' ? 'ascending' : 'descending') . '"' : '';
    return '<th scope="col" class="' . $class . '"' . $aria . '><a class="sort-link' . ($isActive ? ' is-active' : '') . '" href="'
        . e(inventory_url(['sort' => $key, 'dir' => $nextDir, 'page' => 1])) . '">' . e($label) . icon($iconName) . '</a></th>';
}

layout_start('Inventory', 'inventory');
?>

<div class="page-head">
    <div>
        <h1>Inventory</h1>
        <p><?php echo number_format($totalStore); ?> <?php echo $totalStore === 1 ? 'product' : 'products'; ?> in your store</p>
    </div>
    <div class="page-actions">
        <a href="add_product.php" class="btn btn-primary"><?php echo icon('plus'); ?> Add product</a>
    </div>
</div>

<section class="card">
    <nav class="tabs" aria-label="Filter by stock status">
        <?php foreach ($statusFilters as $key => [$label]): ?>
            <a class="tab" href="<?php echo e(inventory_url(['status' => $key, 'page' => 1])); ?>" <?php echo $status === $key ? 'aria-current="true"' : ''; ?>>
                <?php echo e($label); ?> <span class="count"><?php echo number_format($tabCounts[$key]); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <form class="toolbar" method="get" action="inventory.php" role="search">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?php echo e($status); ?>"><?php endif; ?>
        <?php if ($sort !== 'name' || $dir !== 'asc'): ?>
            <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
            <input type="hidden" name="dir" value="<?php echo e($dir); ?>">
        <?php endif; ?>
        <div class="search">
            <?php echo icon('search', 'icon-sm'); ?>
            <label for="invSearch" class="sr-only">Search by name or category</label>
            <input type="search" id="invSearch" name="q" class="input" value="<?php echo e($q); ?>" placeholder="Search by name or category…">
        </div>
        <label for="invCategory" class="sr-only">Category</label>
        <select id="invCategory" name="category" class="select" data-autosubmit>
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo e($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo e(ucfirst($cat)); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary"><?php echo icon('filter', 'icon-sm'); ?> Apply</button>
        <?php if ($filtered): ?>
            <a href="inventory.php" class="btn btn-ghost"><?php echo icon('x', 'icon-sm'); ?> Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($totalStore === 0): ?>
        <div class="empty">
            <span class="empty-icon"><?php echo icon('package'); ?></span>
            <h3>No products yet</h3>
            <p>Add your first product to start tracking stock levels, prices and low-stock alerts.</p>
            <a href="add_product.php" class="btn btn-primary"><?php echo icon('plus'); ?> Add your first product</a>
        </div>
    <?php elseif (!$items): ?>
        <div class="empty">
            <span class="empty-icon"><?php echo icon('search'); ?></span>
            <h3>No products match</h3>
            <p>Nothing matches <?php echo $q !== '' ? '“' . e($q) . '”' : 'these filters'; ?>. Try a different search or clear the filters.</p>
            <a href="inventory.php" class="btn btn-secondary"><?php echo icon('x', 'icon-sm'); ?> Clear filters</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table-cards">
                <caption class="sr-only">Products, sorted by <?php echo e($sort); ?> <?php echo $dir === 'asc' ? 'ascending' : 'descending'; ?></caption>
                <thead>
                    <tr>
                        <?php echo sort_header('name', 'Product'); ?>
                        <?php echo sort_header('category', 'Category'); ?>
                        <?php echo sort_header('stock', 'Stock'); ?>
                        <?php echo sort_header('price', 'Price', 'right'); ?>
                        <th scope="col">Status</th>
                        <?php echo sort_header('updated', 'Updated'); ?>
                        <th scope="col" class="actions"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row):
                        $stock = (int) $row['stock'];
                        $level = stock_level($stock, $row['status']); ?>
                        <tr>
                            <td class="cell-first">
                                <div class="cell-main">
                                    <span class="thumb"><?php echo icon('package'); ?></span>
                                    <div>
                                        <strong><?php echo e($row['product_name']); ?></strong>
                                        <span>#<?php echo (int) $row['id']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Category"><span class="chip"><?php echo e(ucfirst($row['category'])); ?></span></td>
                            <td data-label="Stock">
                                <span class="stock-cell num"><span class="dot dot-<?php echo $level; ?>"></span><?php echo number_format($stock); ?></span>
                            </td>
                            <td data-label="Price" class="right num"><?php echo money((float) $row['price']); ?></td>
                            <td data-label="Status"><span class="badge badge-<?php echo $level; ?>"><?php echo level_label($level); ?></span></td>
                            <td data-label="Updated"><time class="muted" datetime="<?php echo e($row['last_updated']); ?>" title="<?php echo e($row['last_updated']); ?>"><?php echo e(time_ago($row['last_updated'])); ?></time></td>
                            <td class="actions">
                                <div class="actions-inner">
                                    <a href="update_product.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-secondary btn-sm" aria-label="Edit <?php echo e($row['product_name']); ?>">
                                        <?php echo icon('pencil', 'icon-sm'); ?><span class="label-sm">Edit</span>
                                    </a>
                                    <form action="delete_product.php" method="post"
                                          data-confirm="“<?php echo e($row['product_name']); ?>” will be permanently removed from your inventory."
                                          data-confirm-title="Delete this product?" data-confirm-label="Delete product">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <input type="hidden" name="return" value="<?php echo e(inventory_url()); ?>">
                                        <button type="submit" class="btn btn-danger-soft btn-sm" aria-label="Delete <?php echo e($row['product_name']); ?>">
                                            <?php echo icon('trash-2', 'icon-sm'); ?><span class="label-sm">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <span class="page-info">
                Showing <strong><?php echo $offset + 1; ?>–<?php echo $offset + count($items); ?></strong> of <strong><?php echo number_format($total); ?></strong>
            </span>
            <?php if ($pages > 1): ?>
                <nav class="pager" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo e(inventory_url(['page' => $page - 1])); ?>" aria-label="Previous page"><?php echo icon('chevron-left', 'icon-sm'); ?></a>
                    <?php else: ?>
                        <span class="is-disabled" aria-hidden="true"><?php echo icon('chevron-left', 'icon-sm'); ?></span>
                    <?php endif; ?>
                    <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                        <?php if ($p === $page): ?>
                            <span aria-current="page"><?php echo $p; ?></span>
                        <?php else: ?>
                            <a href="<?php echo e(inventory_url(['page' => $p])); ?>"><?php echo $p; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $pages): ?>
                        <a href="<?php echo e(inventory_url(['page' => $page + 1])); ?>" aria-label="Next page"><?php echo icon('chevron-right', 'icon-sm'); ?></a>
                    <?php else: ?>
                        <span class="is-disabled" aria-hidden="true"><?php echo icon('chevron-right', 'icon-sm'); ?></span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php layout_end(['inventory.js']); ?>
