<?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);
    $price = floatval($_POST['price']);
    $status = trim($_POST['status']);

    // Validate inputs
    if (empty($name) || empty($category) || $stock < 0 || $price < 0) {
        echo "Invalid input data. Please check your fields.";
        exit();
    }

    // Insert data
    $stmt = $conn->prepare("INSERT INTO inventory (product_name, category, stock, price, status, last_updated) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssids", $name, $category, $stock, $price, $status);

    if ($stmt->execute()) {
        header("Location: inventory.php?success=Item added successfully");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Pinkventory</title>
    <link rel="stylesheet" href="styl.css?v=1.0">
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
    <form action="" method="POST">
        <div class="form-group">
            <label>Product Name:</label>
            <input type="text" name="product_name" required>
        </div>
        <div class="form-group">
            <label>Category:</label>
            <select name="category" required>
                <option value="electronics">Electronics</option>
                <option value="clothing">Clothing</option>
                <option value="accessories">Accessories</option>
            </select>
        </div>
        <div class="form-group">
            <label>Stock Quantity:</label>
            <input type="number" name="stock" min="0" required>
        </div>
        <div class="form-group">
            <label>Price:</label>
            <input type="number" name="price" step="0.01" min="0" required>
        </div>
        <div class="form-group">
            <label>Status:</label>
            <select name="status" required>
                <option value="in-stock">In Stock</option>
                <option value="low-stock">Low Stock</option>
                <option value="out-of-stock">Out of Stock</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Add Product</button>
        </div>
    </form>
</div>

</body>
</html>
