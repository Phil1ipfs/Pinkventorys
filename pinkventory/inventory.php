<?php
include 'connect.php';

$sql = "SELECT * FROM inventory";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Pinkventory</title>
    <link rel="stylesheet" href="styl.css?v=1.0">
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

    <!-- Navigation Bar -->
    <header class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <button class="menu-btn" aria-label="Toggle menu">
                    <i data-lucide="menu"></i>
                </button>
                 <img src="BARBIE-removebg-preview 2.png" alt="Pinkventory Logo" class="logo">
                    <h1 class="brand">Pinkventory</h1>
                <nav class="nav-links">
                <a href="homepage.php" class="nav-link active">
                    <i data-lucide="bar-chart-2"></i> Dashboard
                </a>
                <a href="inventory.php" class="nav-link">
                    <i data-lucide="package"></i> Inventory
                </a>
                <a href="Checkout.php" class="nav-link">
                    <i data-lucide="shopping-cart"></i> Checkout
                </a>
            </nav>
            </div>
            <div class="search-container1">
                <span class="search-icon1">&#128269;</span>
                <input type="text" class="search-input1" id="search" placeholder="Search products...">
                <div class="suggestions" id="suggestions"></div>
            </div>
            <div class="nav-right">
                <span class="sync-status">
                    <i data-lucide="clock"></i> Last sync: 2 min ago
                </span>
                <button class="user-btn" aria-label="User profile">
                    <i data-lucide="user"></i>
                </button>
            </div>
        </div>
    </header>
    
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
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo $row['stock']; ?></td>
                        <td>$<?php echo number_format($row['price'], 2); ?></td>
                        <td><span class="status-badge"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <td><?php echo $row['last_updated']; ?></td>
                        <td>
                            <a href="update_product.php?id=<?php echo $row['id']; ?>" class="icon-btn edit-btn">
                                <i data-lucide="edit"></i> Update
                            </a>
                            <form action="delete_product.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
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
    function confirmDelete() {
        return confirm('⚠️ Are you sure you want to delete this item? This action cannot be undone.');
    }
</script>
<script>

function addNewItem() {
    window.location.href = "add_product.php";
}
</script>

</body>
</html>
<?php $conn->close(); ?>
