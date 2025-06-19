<?php
include('connect.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $fullName = $conn->real_escape_string($_POST['fullname']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-danger'>Invalid email address!</div>";
    } elseif (strlen($password) < 8) {
        echo "<div class='alert alert-danger'>Password must be at least 8 characters long!</div>";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Check if email already exists
        $checkEmail = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($checkEmail);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<div class='alert alert-danger'>Email Address Already Exists!</div>";
        } else {
            $insertQuery = "INSERT INTO users (email, fullname, gender, password) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ssss", $email, $fullName, $gender, $hashedPassword);

            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>User added successfully!</div>";
            } else {
                error_log("Database error: " . $conn->error);
                echo "<div class='alert alert-danger'>An error occurred. Please try again.</div>";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to right, #FFDDDC, #fff);
            color: #333;
        }

        h1 {
            text-align: center;
            margin-top: 20px;
            color: #fff;
        }

        form {
            max-width: 300px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        form input {
            width: 280px; /* or specify a fixed width like 250px */
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #F35578;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background-color: #FFDDDC;
        }

        .alert {
            max-width: 400px;
            margin: 20px auto;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            text-align: center;
        }

    </style>    
</head>
<body>
    <h1>Add User</h1>
    <form method="POST">
        <label>Email: </label>
        <input type="email" name="email" required><br>
        <label>Full Name: </label>
        <input type="text" name="fullname" required><br>
        <label>Gender: </label>
        <select name="gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select><br>
        <label>Password: </label>
        <input type="password" name="password" required><br>
        <button type="submit">Add User</button>
    </form>

      <!-- Session Timeout Modal -->
      <div id="sessionTimeoutModal" class="modal">
          <div class="modal-content">
              <h2>Session Timeout</h2>
              <p>Your session is about to expire due to inactivity. You will be logged out in <span id="countdown">60</span> seconds.</p>
          </div>
      </div>

    <script>
    let timeout;
    let countdown;
    const sessionTimeout = 20; // Session timeout in seconds
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
