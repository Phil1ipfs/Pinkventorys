<?php
require_once 'bootstrap.php';
require_admin();

$users = $conn->query("SELECT id, email, fullname, gender FROM users ORDER BY id");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pinkventory</title>
    <link rel="stylesheet" type="text/css" href="<?php echo asset('styles.css'); ?>">
</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2 class="logo">Pinkventory</h2>
            <nav>
                <ul>
                    <li class="active">
                        <a href="admin_dashboard.php"><i class="nav-icon">🏠</i> Home</a>
                    </li>
                    <li>
                        <a href="Admin.php"><i class="nav-icon">🛡️</i> Create Admin</a>
                    </li>
                    <li>
                        <a href="#"><i class="nav-icon">⚙️</i> Settings</a>
                    </li>
                    <li>
                        <a href="<?php echo e(logout_url()); ?>"><i class="nav-icon">🚪</i> Logout</a>
                    </li>

                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h2>Welcome <?php echo e($_SESSION['admin_username']); ?>!</h2>
            <h1>Dashboard Overview</h1>
            <a href="add_user.php" class="btn-add">ADD USER</a>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Gender</th>
                            <th colspan="2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int) $row['id']; ?></td>
                            <td><?php echo e($row['email']); ?></td>
                            <td><?php echo e($row['fullname']); ?></td>
                            <td><?php echo e($row['gender']); ?></td>
                            <td><a href="update_user.php?id=<?php echo (int) $row['id']; ?>" class="btn update">Update</a></td>
                            <td><button class="btn delete" onclick="deleteUser(<?php echo (int) $row['id']; ?>)">Delete</button></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
  const csrfToken = <?php echo json_encode(csrf_token()); ?>;
  const logoutUrl = <?php echo json_encode(logout_url()); ?>;

  function deleteUser(userId) {
    if (confirm("Are you sure you want to delete this user?")) {
      // Send an AJAX request to delete_user.php with the user ID
      var xhr = new XMLHttpRequest();
      xhr.open("POST", "delete_user.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.onload = function() {
        if (xhr.status === 200) {
          alert("User deleted successfully");
          window.location.reload(); // Reload the page to update the list
        } else {
          alert("Error deleting user");
        }
      };
      xhr.send("id=" + encodeURIComponent(userId) + "&csrf_token=" + encodeURIComponent(csrfToken));
    }
  }
</script>

    <!-- Inactivity Warning Dialog -->
    <div id="inactivityWarning" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); z-index: 1000; text-align: center;">
        <p>You have been inactive for a while. You will be logged out in <span id="countdown">5</span> seconds.</p>
    </div>

    <script>
        // Inactivity timeout: 3 minutes (180,000 milliseconds)
        const inactivityTimeout = 180000;
        const countdownDuration = 5; // Countdown duration in seconds
        let inactivityTimer;
        let countdownTimer;

        // Function to reset the inactivity timer
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            clearInterval(countdownTimer);
            document.getElementById('inactivityWarning').style.display = 'none';
            inactivityTimer = setTimeout(showInactivityWarning, inactivityTimeout);
        }

        // Function to show the inactivity warning dialog and start countdown
        function showInactivityWarning() {
            document.getElementById('inactivityWarning').style.display = 'block';
            startCountdown();
        }

        // Function to start the countdown
        function startCountdown() {
            let timeLeft = countdownDuration;
            document.getElementById('countdown').textContent = timeLeft;

            countdownTimer = setInterval(() => {
                timeLeft--;
                document.getElementById('countdown').textContent = timeLeft;

                if (timeLeft <= 0) {
                    clearInterval(countdownTimer);
                    logoutUser();
                }
            }, 1000);
        }

        // Function to log out the user
        function logoutUser() {
            window.location.href = logoutUrl;
        }

        // Reset the timer on user activity (e.g., mouse movement, clicks, keypress)
        document.addEventListener('mousemove', resetInactivityTimer);
        document.addEventListener('keydown', resetInactivityTimer);
        document.addEventListener('click', resetInactivityTimer);

        // Initialize the inactivity timer when the page loads
        resetInactivityTimer();
    </script>


</body>

</html>
