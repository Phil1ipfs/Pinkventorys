<?php
// Include the database connection file
include 'connect.php';

// Start a new session or resume the existing session
session_start();

// Check if the form was submitted for user registration
if (isset($_POST['signUp'])) {
    // Sanitize user inputs to prevent SQL injection
    $email = $conn->real_escape_string($_POST['email']);
    $fullName = $conn->real_escape_string($_POST['fullname']);
    $password = $_POST['password'];

    // Validate the email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-danger'>Invalid email address!</div>";
    } 
    // Ensure password is at least 8 characters long
    elseif (strlen($password) < 8) {
        echo "<div class='alert alert-danger'>Password must be at least 8 characters long!</div>";
    } else {
        // Hash the password using BCRYPT for security
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Prepare SQL query to check if the email already exists
        $checkEmail = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($checkEmail);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // If email exists, show error message
        if ($result->num_rows > 0) {
            echo "<div class='alert alert-danger'>Email Address Already Exists!</div>";
        } else {
            // Prepare SQL query to insert the new user into the database
            $insertQuery = "INSERT INTO users (email, fullname, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("sss", $email, $fullName, $hashedPassword);

            // Execute the query and check for success
            if ($stmt->execute()) {
                header("Location: signin.php"); // Redirect to the sign-in page
                exit();
            } else {
                error_log("Database error: " . $conn->error); // Log the error
                echo "<div class='alert alert-danger'>An error occurred. Please try again.</div>";
            }
        }
        $stmt->close(); // Close the prepared statement
    }
}

// Check if the form was submitted for admin login
if (isset($_POST['admin'])) {
    // Sanitize and get admin login details
    $username = $conn->real_escape_string($_POST['admin']);
    $password = $_POST['admin_password'];
    
    // Prepare SQL query to check if the admin username exists
    $sql = "SELECT * FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the admin username exists and verify the password
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            // Set session variable for admin username and redirect to homepage
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: homepage.php");
            exit();
        } else {
            echo "<div class='alert alert-danger'>Incorrect username or password.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Admin account not found.</div>";
    }
    $stmt->close(); // Close the prepared statement
}

// Check if the form was submitted for updating user information
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and get the user update details
    $id = $conn->real_escape_string($_POST['id']);
    $email = $conn->real_escape_string($_POST['email']);
    $fullname = $conn->real_escape_string($_POST['fullname']);

    // Validate the email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-danger'>Invalid email address!</div>";
    } else {
        // Prepare SQL query to update user information
        $updateQuery = "UPDATE users SET email = ?, fullname = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $email, $fullname, $id);

        // Execute the query and check for success
        if ($stmt->execute()) {
            header("Location: homepage.php?success=1"); // Redirect to homepage with success message
            exit();
        } else {
            echo "<div class='alert alert-danger'>An error occurred. Please try again.</div>";
        }
        $stmt->close(); // Close the prepared statement
    }
}

// Close the database connection
$conn->close();
?>
