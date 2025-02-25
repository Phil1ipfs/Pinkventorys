<?php
session_start();
require_once 'connect.php'; // Ensure proper inclusion of database connection

// Check if user is logged in
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    // Fetch user data from the database
    $sql = "SELECT fullname FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $fullname = $user['fullname']; // Store full name
    } else {
        $fullname = "Guest"; // Default if user not found
    }
} else {
    $fullname = "Guest"; // Default if not logged in
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Checkout System</title>
    <link rel="stylesheet" href="styl.css?v=<?php echo time(); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.359.0/umd/lucide.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 1024px) {
            .grid {
                grid-template-columns: 2fr 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-container {
            position: relative;
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 10px 10px 10px 40px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid transparent;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: #ED2860;
            color: white;
        }

        .btn-primary:hover {
            background-color: #ED2860;
        }

        .btn-outline {
            background-color: white;
            border-color: #e5e7eb;
        }

        .btn-outline:hover {
            background-color: #f9fafb;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .products-table th {
            background-color: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 500;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }

        .products-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quantity-btn {
            padding: 4px 8px;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .quantity-text {
            width: 40px;
            text-align: center;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .summary-total {
            border-top: 1px solid #e5e7eb;
            margin-top: 1rem;
            padding-top: 1rem;
            font-weight: 600;
        }

        .payment-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #f3f4f6;
            border-radius: 9999px;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .remove-btn {
            color: #ef4444;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
        }

        .remove-btn:hover {
            background-color: #fee2e2;
            border-radius: 4px;

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
                <nav class="nav-links">
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

    <div class="container">
        <div class="grid">
            
            <!-- Main Checkout Area -->
            <div class="card">
                <h2 class="card-title">Checkout System</h2>
                <form class="search-form" id="barcodeForm">
                    <div class="search-container">
                        <i class="search-icon" data-lucide="scan-barcode"></i>
                        <input type="text" id="barcodeInput" class="search-input" placeholder="Scan or enter barcode...">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="search"></i>
                        Search
                    </button>
                </form>

                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cartItems">
                        <tr>
                            <td colspan="5" class="text-center">No items in cart. Scan or enter a barcode to begin.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            
<!-- Payment Summary -->
<div class="card">
    <h2 class="card-title">Payment Summary</h2>
    <div class="summary-row">
        <span>Subtotal</span>
        <span id="subtotal">$0.00</span>
    </div>
    <div class="summary-row">
        <span>Tax (10%)</span>
        <span id="tax">$0.00</span>
    </div>
    <div class="summary-row summary-total">
        <span>Total</span>
        <span id="total">$0.00</span>
    </div>

    <div class="payment-buttons">
        <button class="btn btn-primary">
            <i data-lucide="credit-card"></i>
            Card Payment
        </button>
        <button class="btn btn-outline">
            <i data-lucide="banknote"></i>
            Cash Payment
        </button>
        <button class="btn btn-outline">
            <i data-lucide="receipt"></i>
            Print Receipt
        </button>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <span class="badge">Items in cart: <span id="itemCount">0</span></span>
    </div>

    <!-- Profile section aligned to the top right -->

    </div>
</div>

<div class="profile-right">
        <div class="profile">
            <img src="cillak.PNG" alt="User Profile" class="profile-img">
            <span class="profile-name"><?php echo htmlspecialchars($fullname); ?></span>

            <!-- Dropdown Menu -->
            <div class="profile-dropdown">
                <a href="#">⚙️ Settings</a>
                <a href="index.php">🚪 Logout</a>
            </div>
        </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Sample product database
        const products = {
            '123456789': { name: 'Organic Coffee', price: 12.99, stock: 45 },
            '987654321': { name: 'Premium Tea', price: 8.99, stock: 30 },
            '456789123': { name: 'Mineral Water', price: 2.99, stock: 100 }
        };

        let cart = [];

        function updateCart() {
            const cartElement = document.getElementById('cartItems');
            const subtotalElement = document.getElementById('subtotal');
            const taxElement = document.getElementById('tax');
            const totalElement = document.getElementById('total');
            const itemCountElement = document.getElementById('itemCount');

            // Calculate totals
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const tax = subtotal * 0.1;
            const total = subtotal + tax;

            // Update summary
            subtotalElement.textContent = `$${subtotal.toFixed(2)}`;
            taxElement.textContent = `$${tax.toFixed(2)}`;
            totalElement.textContent = `$${total.toFixed(2)}`;
            itemCountElement.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);

            // Update cart items
            if (cart.length === 0) {
                cartElement.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            No items in cart. Scan or enter a barcode to begin.
                        </td>
                    </tr>
                `;
                return;
            }

            cartElement.innerHTML = cart.map(item => `
                <tr>
                    <td>${item.name}</td>
                    <td>$${item.price.toFixed(2)}</td>
                    <td>
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn" onclick="updateQuantity('${item.barcode}', -1)">
                                <i data-lucide="minus"></i>
                            </button>
                            <span class="quantity-text">${item.quantity}</span>
                            <button type="button" class="quantity-btn" onclick="updateQuantity('${item.barcode}', 1)">
                                <i data-lucide="plus"></i>
                            </button>
                        </div>
                    </td>
                    <td>$${(item.price * item.quantity).toFixed(2)}</td>
                    <td>
                        <button type="button" class="remove-btn" onclick="removeItem('${item.barcode}')">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            // Reinitialize icons in the updated cart
            lucide.createIcons();
        }

        function addToCart(barcode) {
            const product = products[barcode];
            const existingItem = cart.find(item => item.barcode === barcode);

            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    barcode,
                    name: product.name,
                    price: product.price,
                    quantity: 1
                });
            }

            updateCart();
        }

        function updateQuantity(barcode, change) {
            const item = cart.find(item => item.barcode === barcode);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    cart = cart.filter(i => i.barcode !== barcode);
                }
            }
            updateCart();
        }

        function removeItem(barcode) {
            cart = cart.filter(item => item.barcode !== barcode);
            updateCart();
        }

        // Form submission handler
        document.getElementById('barcodeForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const input = document.getElementById('barcodeInput');
            const barcode = input.value;

            if (products[barcode]) {
                addToCart(barcode);
                input.value = '';
            } else {
                alert('Product not found!');
            }
        });

        // Initialize the cart display
        updateCart();
    </script>
</body>
</html>