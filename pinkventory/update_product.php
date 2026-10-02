<?php
require_once 'bootstrap.php';
require_login();

$error = null;
$id = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    redirect('inventory.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$product, $error] = validate_product($_POST);

    if (!$error) {
        $updateSql = "UPDATE inventory SET product_name=?, category=?, stock=?, price=?, status=?, last_updated=NOW() WHERE id=?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ssidsi", $product['product_name'], $product['category'], $product['stock'], $product['price'], $product['status'], $id);
        $stmt->execute();
        redirect('inventory.php');
    }
}

$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    http_response_code(404);
    exit('Item not found. <a href="inventory.php">Back to inventory</a>');
}

// Keep what the user typed if validation failed.
if ($error) {
    $item = array_merge($item, array_intersect_key($_POST, array_flip(['product_name', 'category', 'stock', 'price', 'status'])));
}
$itemStatus = status_slug($item['status']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - Pinkventory</title>
    <link rel="stylesheet" href="<?php echo asset('styl.css'); ?>">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .main-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
            text-align: left;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s ease-in-out;
            font-weight: bold;
        }

        .primary {
            background: #ff4081;
            color: white;
        }

        .primary:hover {
            background: #e91e63;
        }

        .secondary {
            background: #6c757d;
            color: white;
        }

        .secondary:hover {
            background: #5a6268;
        }
    </style>

</head>
<body>

<main class="main-content">
    <h2>Edit Item</h2>
    <?php if ($error): ?>
        <p style="color: #721c24; background: #f8d7da; padding: 10px; border-radius: 6px;"><?php echo e($error); ?></p>
    <?php endif; ?>
    <form action="update_product.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
        <label>Product Name:</label>
        <input type="text" name="product_name" value="<?php echo e($item['product_name']); ?>" maxlength="150" required>
        
        <label>Category:</label>
        <input type="text" name="category" value="<?php echo e($item['category']); ?>" maxlength="50" required>

        <label>Stock Quantity:</label>
        <input type="number" name="stock" min="0" value="<?php echo e($item['stock']); ?>" required>

        <label>Price:</label>
        <input type="number" name="price" step="0.01" min="0" value="<?php echo e($item['price']); ?>" required>

        <label>Status:</label>
        <select name="status">
            <?php foreach (INVENTORY_STATUSES as $value => $label): ?>
                <option value="<?php echo $value; ?>" <?php if ($itemStatus === $value) echo 'selected'; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn primary">Update Item</button>
        <a href="inventory.php" class="btn secondary">Cancel</a>
    </form>
</main>

</body>
</html>
