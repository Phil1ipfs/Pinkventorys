<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_once 'includes/store.php';
require_user();

const SHOP_PER_PAGE = 12;

$levels = stock_level_sql();
$availability = [
    ''             => ['All availability', null],
    'in-stock'     => ['In stock', $levels['ok']],
    'low-stock'    => ['Low stock', $levels['low']],
    'out-of-stock' => ['Out of stock', $levels['out']],
];
$sorts = [
    'newest'     => ['Newest', 'last_updated DESC, id DESC'],
    'price-asc'  => ['Price: low to high', 'price ASC, id ASC'],
    'price-desc' => ['Price: high to low', 'price DESC, id DESC'],
    'name'       => ['Name A–Z', 'product_name ASC, id ASC'],
];

$q = trim((string) ($_GET['q'] ?? ''));
$category = strtolower(trim((string) ($_GET['category'] ?? '')));
$status = isset($availability[$_GET['status'] ?? '']) ? (string) ($_GET['status'] ?? '') : '';
$sort = isset($sorts[$_GET['sort'] ?? '']) ? (string) $_GET['sort'] : 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));

function shop_url(array $changes = []): string
{
    global $q, $category, $status, $sort, $page;
    $params = array_merge(compact('q', 'category', 'status', 'sort', 'page'), $changes);
    if ($params['sort'] === 'newest') {
        unset($params['sort']);
    }
    if ((int) ($params['page'] ?? 1) <= 1) {
        unset($params['page']);
    }
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return 'shop.php' . ($params ? '?' . http_build_query($params) : '');
}

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
if ($availability[$status][1]) {
    $where[] = $availability[$status][1];
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $conn->prepare("SELECT COUNT(*) FROM inventory $whereSql");
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_row()[0];
$pages = max(1, (int) ceil($total / SHOP_PER_PAGE));
$page = min($page, $pages);
$offset = ($page - 1) * SHOP_PER_PAGE;

$stmt = $conn->prepare("SELECT id, product_name, category, stock, price, status FROM inventory $whereSql
    ORDER BY {$sorts[$sort][1]} LIMIT ? OFFSET ?");
$listParams = array_merge($params, [SHOP_PER_PAGE, $offset]);
$stmt->bind_param($types . 'ii', ...$listParams);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categories = product_categories($conn);
$filtered = $q !== '' || $category !== '' || $status !== '';

layout_start('Shop', 'shop');
?>

<div class="page-head">
    <div>
        <h1><?php echo $category !== '' ? e(ucfirst($category)) : 'Our products'; ?></h1>
        <p>Browse our latest products and find what you need.</p>
    </div>
</div>

<form class="card shop-filters" method="get" action="shop.php" role="search">
    <div class="search">
        <?php echo icon('search', 'icon-sm'); ?>
        <label for="shopSearch" class="sr-only">Search products</label>
        <input type="search" id="shopSearch" name="q" class="input" value="<?php echo e($q); ?>" placeholder="Search products…">
    </div>
    <label for="shopCategory" class="sr-only">Category</label>
    <select id="shopCategory" name="category" class="select" data-autosubmit>
        <option value="">All categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo e($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo e(ucfirst($cat)); ?></option>
        <?php endforeach; ?>
    </select>
    <label for="shopStatus" class="sr-only">Availability</label>
    <select id="shopStatus" name="status" class="select" data-autosubmit>
        <?php foreach ($availability as $key => [$label]): ?>
            <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>
    <label for="shopSort" class="sr-only">Sort by</label>
    <select id="shopSort" name="sort" class="select" data-autosubmit>
        <?php foreach ($sorts as $key => [$label]): ?>
            <option value="<?php echo $key; ?>" <?php echo $sort === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><?php echo icon('filter', 'icon-sm'); ?> Filter</button>
</form>

<div class="results-line">
    <span><?php echo number_format($total); ?> <?php echo $total === 1 ? 'product' : 'products'; ?><?php echo $q !== '' ? ' for “' . e($q) . '”' : ''; ?></span>
    <?php if ($filtered): ?><a href="shop.php" class="link-more"><?php echo icon('x', 'icon-sm'); ?> Clear filters</a><?php endif; ?>
</div>

<?php if (!$products): ?>
    <div class="card empty">
        <span class="empty-icon"><?php echo icon($filtered ? 'search' : 'package'); ?></span>
        <h3><?php echo $filtered ? 'No products match' : 'No products yet'; ?></h3>
        <p><?php echo $filtered ? 'Try a different search, category or availability.' : 'Check back soon. Products appear here as soon as they are added.'; ?></p>
        <?php if ($filtered): ?><a href="shop.php" class="btn btn-secondary"><?php echo icon('x', 'icon-sm'); ?> Clear filters</a><?php endif; ?>
    </div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $p) echo product_card($p); ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pager pager-center" aria-label="Pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo e(shop_url(['page' => $page - 1])); ?>" aria-label="Previous page"><?php echo icon('chevron-left', 'icon-sm'); ?></a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span aria-current="page"><?php echo $p; ?></span>
                <?php elseif ($p === 1 || $p === $pages || abs($p - $page) <= 1): ?>
                    <a href="<?php echo e(shop_url(['page' => $p])); ?>"><?php echo $p; ?></a>
                <?php elseif (abs($p - $page) === 2): ?>
                    <span class="is-disabled" aria-hidden="true">…</span>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <a href="<?php echo e(shop_url(['page' => $page + 1])); ?>" aria-label="Next page"><?php echo icon('chevron-right', 'icon-sm'); ?></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php layout_end(['store.js']); ?>
