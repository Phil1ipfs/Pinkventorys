<?php
include 'connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM inventory WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
} 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $product_name = $_POST['product_name'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $updateSql = "UPDATE inventory SET product_name=?, category=?, stock=?, price=?, status=?, last_updated=NOW() WHERE id=?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ssidsi", $product_name, $category, $stock, $price, $status, $id);

    if ($stmt->execute()) {
        header("Location: inventory.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - Pinkventory</title>
    <link rel="stylesheet" href="styl.css?v=<?php echo time(); ?>">

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
    <form action="update_product.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
        <label>Product Name:</label>
        <input type="text" name="product_name" value="<?php echo $item['product_name']; ?>" required>
        
        <label>Category:</label>
        <input type="text" name="category" value="<?php echo $item['category']; ?>" required>

        <label>Stock Quantity:</label>
        <input type="number" name="stock" value="<?php echo $item['stock']; ?>" required>

        <label>Price:</label>
        <input type="number" name="price" step="0.01" value="<?php echo $item['price']; ?>" required>

        <label>Status:</label>
        <select name="status">
            <option value="In Stock" <?php if ($item['status'] == 'In Stock') echo 'selected'; ?>>In Stock</option>
            <option value="Low Stock" <?php if ($item['status'] == 'Low Stock') echo 'selected'; ?>>Low Stock</option>
            <option value="Out of Stock" <?php if ($item['status'] == 'Out of Stock') echo 'selected'; ?>>Out of Stock</option>
        </select>

        <button type="submit" class="btn primary">Update Item</button>
        <a href="inventory.php" class="btn secondary">Cancel</a>
    </form>
</main>

</body>
</html>
