<?php
require_once 'bootstrap.php';
require_login();

$error = null;
$product = ['product_name' => '', 'category' => '', 'stock' => '', 'price' => '', 'status' => 'in-stock'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    [$product, $error] = validate_product($_POST);

    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO inventory (product_name, category, stock, price, status, last_updated) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssids", $product['product_name'], $product['category'], $product['stock'], $product['price'], $product['status']);
        $stmt->execute();
        redirect('inventory.php?success=added');
    }
}

$categories = ['electronics' => 'Electronics', 'clothing' => 'Clothing', 'accessories' => 'Accessories'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Pinkventory</title>
    <link rel="stylesheet" href="<?php echo asset('styl.css'); ?>">
    <style>
        body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #ff9a9e, #fad0c4);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    max-width: 450px;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease-in-out;
}

.container:hover {
    transform: translateY(-5px);
}

h2 {
    text-align: center;
    color: #ff4081;
    font-size: 24px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    font-weight: 600;
    display: block;
    margin-bottom: 6px;
    color: #333;
}

input, select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
}

input:focus, select:focus {
    border-color: #ff4081;
    box-shadow: 0px 0px 8px rgba(255, 64, 129, 0.3);
    outline: none;
}

.form-actions {
    text-align: center;
    margin-top: 25px;
}

.btn {
    background: #ff4081;
    color: white;
    padding: 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 17px;
    font-weight: bold;
    width: 100%;
    transition: background 0.3s ease-in-out, transform 0.2s;
}

.btn:hover {
    background: #e91e63;
    transform: scale(1.05);
}

.btn:focus {
    outline: none;
    box-shadow: 0px 0px 10px rgba(233, 30, 99, 0.5);
}

@media (max-width: 480px) {
    .container {
        max-width: 90%;
        padding: 20px;
    }
}


    </style>
</head>
<body>

<div class="container">
    <h2>Add New Product</h2>
    <?php if ($error): ?>
        <p style="color: #721c24; background: #f8d7da; padding: 10px; border-radius: 6px; text-align: center;"><?php echo e($error); ?></p>
    <?php endif; ?>
    <form action="" method="POST">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>Product Name:</label>
            <input type="text" name="product_name" value="<?php echo e($product['product_name']); ?>" maxlength="150" required>
        </div>
        <div class="form-group">
            <label>Category:</label>
            <select name="category" required>
                <?php foreach ($categories as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $product['category'] === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Stock Quantity:</label>
            <input type="number" name="stock" min="0" value="<?php echo e($product['stock']); ?>" required>
        </div>
        <div class="form-group">
            <label>Price:</label>
            <input type="number" name="price" step="0.01" min="0" value="<?php echo e($product['price']); ?>" required>
        </div>
        <div class="form-group">
            <label>Status:</label>
            <select name="status" required>
                <?php foreach (INVENTORY_STATUSES as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo $product['status'] === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Add Product</button>
            <p style="margin-top: 15px;"><a href="inventory.php">Cancel</a></p>
        </div>
    </form>
</div>

</body>
</html>
