<?php
require_once 'bootstrap.php';
require_login();

$result = $conn->query("SELECT id, product_name, category, stock, price, status, last_updated FROM inventory ORDER BY product_name");
$activePage = 'inventory';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Pinkventory</title>
    <link rel="stylesheet" href="<?php echo asset('styl.css'); ?>">
    <script src="https://unpkg.com/lucide@0.359.0/dist/umd/lucide.min.js"></script>
    <style>
        /* Button Styling */
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            text-decoration: none;
            transition: 0.3s ease-in-out;
        }

        .primary {
            background: #ff4081;
            color: white;
        }

        .primary:hover {
            background: #e91e63;
        }

        .icon-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s ease;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Update Button */
        .edit-btn {
            background:rgb(64, 218, 69);
            color: white;
        }

        .edit-btn:hover {
            background:rgb(54, 220, 65);
            color: #000;
        }

        /* Delete Button */
        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        /* Icons */
        .icon-btn i {
            font-size: 16px;
        }

        .action-btn2 {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px; /* Space between icon and text */
    background-color: #ff4081;
    color: white;
    font-size: 16px;
    font-weight: bold;
    padding: 12px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
    text-decoration: none;
    width: 200px; /* Set a reasonable width */
    height: 45px; /* Consistent height */
}

.action-btn2 i {
    font-size: 18px; /* Adjust icon size */
}

.action-btn2:hover {
    background-color: #e91e63;
    transform: scale(1.05);
}

.action-btn2:focus {
    outline: none;
    box-shadow: 0px 0px 8px rgba(255, 64, 129, 0.5);
}

    </style>
</head>
<body>

<?php include 'navbar.php'; ?>
<main class="main-content">


    <section class="inventory-table-container">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Stock Level</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                    <td> 
                    <button class="action-btn2 primary" onclick="addNewItem()">
                        <i data-lucide="plus"></i> Add New Item
                    </button>
                    </td>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo e($row['product_name']); ?></td>
                        <td><?php echo e($row['category']); ?></td>
                        <td><?php echo (int) $row['stock']; ?></td>
                        <td>$<?php echo number_format((float) $row['price'], 2); ?></td>
                        <td><span class="status-badge <?php echo e(status_slug($row['status'])); ?>"><?php echo e(status_label($row['status'])); ?></span></td>
                        <td><?php echo e($row['last_updated']); ?></td>
                        <td>
                            <a href="update_product.php?id=<?php echo (int) $row['id']; ?>" class="icon-btn edit-btn">
                                <i data-lucide="edit"></i> Update
                            </a>
                            <form action="delete_product.php" method="POST" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                <button type="submit" class="icon-btn delete-btn" onclick="return confirmDelete();">
                                    <i data-lucide="trash-2"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>
</main>

<script>
    lucide.createIcons();

    function confirmDelete() {
        return confirm('⚠️ Are you sure you want to delete this item? This action cannot be undone.');
    }

    function addNewItem() {
        window.location.href = "add_product.php";
    }
</script>

</body>
</html>
