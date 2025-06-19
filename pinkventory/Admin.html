<?php
// Start a secure session and regenerate session ID to prevent session fixation attacks
session_start();
session_regenerate_id(true);
include 'connect.php';

// Set security headers to protect against clickjacking, caching issues, and content injection attacks
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self';");
header("X-Frame-Options: DENY");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Generate a CSRF token if not already set to prevent cross-site request forgery attacks
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token before processing the request
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Request failed. Please try again.");
    }

    // Sanitize and validate input data
    $username = htmlspecialchars($_POST['admin_username'], ENT_QUOTES, 'UTF-8');
    $fullname = mysqli_real_escape_string($conn, $_POST['admin_fullname'], ENT_QUOTES, 'UTF-8');
    $password = $_POST['admin_password'];

    // Ensure username contains only alphanumeric characters and underscores
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        die("Invalid username format.");
    }
    
    // Ensure full name contains only letters and spaces
    if (!preg_match('/^[a-zA-Z\s]+$/', $fullname)) {
        die("Invalid full name format.");
    }    

    // Ensure password meets security requirements
    if (strlen($password) < 8) {
        die("Password must be at least 8 characters long.");
    }
    
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        die("Password must include uppercase letters, numbers, and special characters.");
    }
    
    // Hash the password before storing it in the database
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Check if username already exists in the database
    $checkUsername = "SELECT * FROM admin WHERE username = ?";
    $stmt = $conn->prepare($checkUsername);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<div class='alert alert-danger'>Username Already Exists!</div>";
    } else {
        // Close previous statement and insert the new admin into the database
        $stmt->close(); 

        $insertQuery = "INSERT INTO admin (username, password, fullname) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sss", $username, $hashedPassword, $fullname);
        $stmt->execute();

        $insertQuery = "INSERT INTO admin (username, password, fullname) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sss", $username, $hashedPassword, $fullname);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Admin account successfully created. Please <a href='index.php?action=signin'>Sign in</a>.</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="page-container">
        <div class="right-panel">
        <div class="header">
                    <div class="brand">
                        <img src="BARBIE-removebg-preview 2.png" alt="Pinkventory Logo" class="logo">
                        <span class="brand-name">Pinkventory</span>
                    </div>
                </div>
            <h1>Create Admin Account</h1>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="text" name="admin_username" placeholder="Admin Username" required>
                <input type="text" name="admin_fullname" placeholder="Full Name" required>
                <input type="password" name="admin_password" placeholder="Password" required>
                <button type="submit-create" name="adminSignUp">Create Admin Account</button>
            </form>
            <p class="already-member">Already a member? <a href="index.php?action=signin">Sign In</a></p>

        </div>
        <div class="left-panel">
            <h1>Welcome, Admin!</h1>
            <p>Already have an admin account? Log in now.</p>
            <a href="index.php?action=signin">
                <button id="adminbtn">Admin Sign In</button>
            </a>
        </div>

    </div>
</body>
</html>
