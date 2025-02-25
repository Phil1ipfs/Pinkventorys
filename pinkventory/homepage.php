<?php
session_start(); // Start the session to track user state
require_once 'connect.php'; // Ensure proper inclusion of database connection

// Check if user is logged in (session has email)
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    // Fetch user data from the database using prepared statement for security
    $sql = "SELECT fullname FROM users WHERE email = ?"; 
    $stmt = $conn->prepare($sql); // Prepare SQL statement
    $stmt->bind_param("s", $email); // Bind parameter to prevent SQL injection
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result of the query
    
    // Check if the user exists in the database
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc(); // Fetch user data
        $fullname = $user['fullname']; // Store the user's full name
    } else {
        $fullname = "Guest"; // If user not found, default to "Guest"
    }
} else {
    $fullname = "Guest"; // Default to "Guest" if user is not logged in
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pinkventory - Retail Store Inventory Management System">
    <meta name="keywords" content="inventory, retail, management, sales, dashboard">
    <meta name="author" content="Your Name">
    <title>Retail Store Inventory Management</title>
    <link rel="stylesheet" href="styl.css?v=<?php echo time(); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.359.0/umd/lucide.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  
      
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Quick Actions -->
        <section class="quick-actions">
            <button class="action-btn primary">
                <i data-lucide="scan-barcode"></i>
                New Sale
            </button>
            <button class="action-btn secondary">
                <i data-lucide="package"></i>
                Manage Inventory
            </button>
            <!-- Profile Section -->
            <div class="profile">
              <img src="cillak.PNG" alt="User Profile" class="profile-img">
              <span class="profile-name"><?php echo htmlspecialchars($fullname); ?></span>

              <!-- Dropdown Menu -->
              <div class="profile-dropdown">
                  <a href="#">⚙️ Settings</a>
                  <a href="index.php">🚪 Logout</a>
              </div>
          </div>
        </section>

        <!-- Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Daily Revenue</h3>
                    <i data-lucide="bar-chart-2"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">$2,850.00</div>
                    <div class="stat-trend positive">
                        <i data-lucide="trending-up"></i>
                        +20.1% from yesterday
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3>Orders Today</h3>
                    <i data-lucide="shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">145</div>
                    <div class="stat-subtext">Avg. order value: $19.65</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3>Active Items</h3>
                    <i data-lucide="package"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">1,234</div>
                    <div class="stat-trend negative">28 items low on stock</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3>Customers</h3>
                    <i data-lucide="users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">89</div>
                    <div class="stat-subtext">12 new today</div>
                </div>
            </div>
        </section>

        <!-- Charts and Alerts -->
        <section class="dashboard-grid">
            <!-- Sales Performance Chart -->
    <div class="chart-card">
    <div class="card-header">
        <h2>Sales Performance</h2>
        <p>Weekly revenue and order trends</p>
    </div>
    <div class="card-content">
        <!-- Stats with Mini Line Charts -->
        <div class="stats">
            <div class="stat-item">
                <h3>Total Revenue</h3>
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="stat-item">
                <h3>Total Orders</h3>
                <canvas id="ordersChart"></canvas>
            </div>
            <div class="stat-item">
                <h3>Avg Order Value</h3>
                <canvas id="aovChart"></canvas>
            </div>
        </div>
    </div>
</div>

            <!-- Low Stock Alerts -->
            <div class="alerts-card">
                <div class="card-header">
                    <div>
                        <h2>Low Stock Alerts</h2>
                        <p>Items requiring attention</p>
                    </div>
                    <i data-lucide="alert-triangle" class="warning-icon"></i>
                </div>
                <div class="alerts-content">
                    <div class="alert-item">
                        <div class="alert-info">
                            <h4>Organic Coffee Beans</h4>
                            <p>Beverages</p>
                            <div class="stock-level1">Stock: 5/10</div>
                        </div>
                        <span class="alert-badge">Reorder</span>
                    </div>
                    <div class="alert-item">
                        <div class="alert-info">
                            <h4>Premium Tea Bags</h4>
                            <p>Beverages</p>
                            <div class="stock-level1">Stock: 8/15</div>
                        </div>
                        <span class="alert-badge">Reorder</span>
                    </div>
                    <div class="alert-item">
                        <div class="alert-info">
                            <h4>Mineral Water 500ml</h4>
                            <p>Beverages</p>
                            <div class="stock-level1">Stock: 12/20</div>
                        </div>
                        <span class="alert-badge">Reorder</span>
                    </div>
                </div>
            </div>
        </section>
    </main>
  
    <!-- Footer -->
    <footer class="footer">
        <p>© 2023 Pinkventory. All rights reserved.</p>
    </footer>

      <!-- Session Timeout Modal -->
      <div id="sessionTimeoutModal" class="modal">
          <div class="modal-content">
              <h2>Session Timeout</h2>
              <p>Your session is about to expire due to inactivity. You will be logged out in <span id="countdown">60</span> seconds.</p>
          </div>
      </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Initialize Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Revenue',
                    data: [4000, 3000, 5000, 2780, 6890, 8390, 3490],
                    borderColor: '#2563eb',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>

<script>
        document.getElementById('search').addEventListener('input', function() {
            let query = this.value;
            let suggestionsBox = document.getElementById('suggestions');
            
            if (query.length > 2) {
                fetch(`search_api.php?q=${query}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsBox.innerHTML = '';
                        data.slice(0, 5).forEach(item => {
                            let div = document.createElement('div');
                            div.textContent = item;
                            div.classList.add('suggestion-item');
                            div.onclick = () => {
                                document.getElementById('search').value = item;
                                suggestionsBox.style.display = 'none';
                            };
                            suggestionsBox.appendChild(div);
                        });
                        suggestionsBox.style.display = 'block';
                    });
            } else {
                suggestionsBox.style.display = 'none';
            }
        });
    </script>

<script>
function createMiniChart(canvasId, data, color, prefix = '') {
    const { max, min } = findMinMax(data);
    const stepSize = calculateStepSize(min, max);
    
    new Chart(document.getElementById(canvasId), {
        type: 'line',
        data: {
            labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            datasets: [{
                data: data,
                borderColor: color,
                borderWidth: 2,
                fill: true,
                backgroundColor: `${color}10`,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: color
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { 
                    display: true,
                    grid: {
                        display: true,
                        color: '#f0f0f0',
                        drawBorder: false
                    },
                    ticks: {
                        display: true,
                        font: {
                            size: 10
                        },
                        color: '#666'
                    }
                },
                y: { 
                    display: true,
                    grid: {
                        display: true,
                        color: '#f0f0f0',
                        drawBorder: false
                    },
                    ticks: {
                        display: true,
                        font: {
                            size: 10
                        },
                        color: '#666',
                        callback: function(value) {
                            if (value >= 1000) {
                                return prefix + (value/1000) + 'k';
                            }
                            return prefix + value;
                        },
                        stepSize: stepSize
                    },
                    min: min - (max - min) * 0.1,
                    max: max + (max - min) * 0.1
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#333',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: (context) => {
                            return prefix + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Helper function to find min and max values
function findMinMax(data) {
    return {
        max: Math.max(...data),
        min: Math.min(...data)
    };
}

// Helper function to calculate appropriate step size for y-axis
function calculateStepSize(min, max) {
    const range = max - min;
    const targetSteps = 4; // We want about 4-5 steps on the y-axis
    const roughStepSize = range / targetSteps;
    
    // Round to a nice number
    const magnitude = Math.pow(10, Math.floor(Math.log10(roughStepSize)));
    const normalizedStepSize = roughStepSize / magnitude;
    
    let niceStepSize;
    if (normalizedStepSize < 1.5) niceStepSize = 1;
    else if (normalizedStepSize < 3) niceStepSize = 2;
    else if (normalizedStepSize < 7) niceStepSize = 5;
    else niceStepSize = 10;
    
    return niceStepSize * magnitude;
}

// Create the mini charts with the enhanced styling
createMiniChart("revenueChart", [12000, 13500, 11000, 15000, 14500, 15500, 16000], "#4169E1", "$");
createMiniChart("ordersChart", [280, 290, 270, 300, 310, 320, 330], "#2E8B57");
createMiniChart("aovChart", [42, 45, 41, 48, 47, 49, 50], "#CD5C5C", "$");
</script>

<script>
    let timeout;
    let countdown;
    const sessionTimeout = 900; // Session timeout in seconds
    const warningTime = 10; // Time to show warning before timeout (in seconds)

    function startTimer() {
        clearTimeout(timeout);
        timeout = setTimeout(logout, sessionTimeout * 1000);
    }

    function resetTimer() {
        clearTimeout(timeout);
        startTimer();
        hideModal();
    }

    function logout() {
        window.location.href = 'index.php'; // Redirect to logout page
    }

    function showModal() {
        const modal = document.getElementById('sessionTimeoutModal');
        modal.style.display = 'flex';
        startCountdown(warningTime);
    }

    function hideModal() {
        const modal = document.getElementById('sessionTimeoutModal');
        modal.style.display = 'none';
        clearInterval(countdown);
    }

    function startCountdown(seconds) {
        let remainingTime = seconds;
        const countdownElement = document.getElementById('countdown');
        countdownElement.textContent = remainingTime;

        countdown = setInterval(() => {
            remainingTime--;
            countdownElement.textContent = remainingTime;

            if (remainingTime <= 0) {
                clearInterval(countdown);
                logout();
            }
        }, 1000);
    }

    // Event listeners for user activity
    document.addEventListener('mousemove', resetTimer);
    document.addEventListener('keypress', resetTimer);
    document.addEventListener('click', resetTimer);

    // Start the timer when the page loads
    startTimer();

    // Show the modal when the session is about to expire
    setTimeout(showModal, (sessionTimeout - warningTime) * 1000);
</script>


</body>
</html>