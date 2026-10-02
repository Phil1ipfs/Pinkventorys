<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_admin();

$errors = [];
$id = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    redirect('inventory.php');
}

$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    flash('error', 'That product no longer exists.');
    redirect('inventory.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$product, $errors] = validate_product($_POST);

    if (!$errors) {
        $updateSql = "UPDATE inventory SET product_name=?, category=?, stock=?, price=?, status=?, last_updated=NOW() WHERE id=?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ssidsi", $product['product_name'], $product['category'], $product['stock'], $product['price'], $product['status'], $id);
        $stmt->execute();
        flash('success', '“' . $product['product_name'] . '” was updated.');
        redirect('inventory.php');
    }
}

$values = [
    'product_name' => $item['product_name'],
    'category'     => strtolower((string) $item['category']),
    'stock'        => (string) $item['stock'],
    'price'        => number_format((float) $item['price'], 2, '.', ''),
    'status'       => status_slug($item['status']),
];
// Keep what the user typed if validation failed.
if ($errors) {
    $values = array_merge($values, array_map('strval', array_intersect_key($_POST, $values)));
}

$categories = product_categories($conn);
$formAction = 'update_product.php';
$submitLabel = 'Save changes';
$productId = $id;

layout_start('Edit ' . $item['product_name'], 'inventory');
?>

<div class="page-head">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="inventory.php">Inventory</a> <?php echo icon('chevron-right'); ?> <span>Edit product</span>
        </nav>
        <h1><?php echo e($item['product_name']); ?></h1>
        <p>Last updated <time datetime="<?php echo e($item['last_updated']); ?>" title="<?php echo e($item['last_updated']); ?>"><?php echo e(time_ago($item['last_updated'])); ?></time></p>
    </div>
    <form action="delete_product.php" method="post" class="page-actions"
          data-confirm="“<?php echo e($item['product_name']); ?>” will be permanently removed from your inventory."
          data-confirm-title="Delete this product?" data-confirm-label="Delete product">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
        <button type="submit" class="btn btn-danger-soft"><?php echo icon('trash-2'); ?> Delete product</button>
    </form>
</div>

<?php include 'includes/product_form.php'; ?>

<?php layout_end(['product.js']); ?>
