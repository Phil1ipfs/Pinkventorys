<?php
require_once 'bootstrap.php';
require_once 'includes/layout.php';
require_admin();

$errors = [];
$values = ['product_name' => '', 'category' => '', 'stock' => '', 'price' => '', 'status' => 'in-stock'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    [$product, $errors] = validate_product($_POST);

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO inventory (product_name, category, stock, price, status, last_updated) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssids", $product['product_name'], $product['category'], $product['stock'], $product['price'], $product['status']);
        $stmt->execute();
        flash('success', '“' . $product['product_name'] . '” was added to your inventory.');
        redirect('inventory.php');
    }

    // Show exactly what was typed so nothing is lost.
    $values = array_merge($values, array_map('strval', array_intersect_key($_POST, $values)));
}

$categories = product_categories($conn);
$formAction = 'add_product.php';
$submitLabel = 'Add product';

layout_start('Add product', 'inventory');
?>

<div class="page-head">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="inventory.php">Inventory</a> <?php echo icon('chevron-right'); ?> <span>Add product</span>
        </nav>
        <h1>Add product</h1>
        <p>Add a new item to your inventory.</p>
    </div>
</div>

<?php include 'includes/product_form.php'; ?>

<?php layout_end(['product.js']); ?>
