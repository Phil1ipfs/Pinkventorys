<?php
// Include your database connection
include('connect.php'); 

if (isset($_POST['id'])) {
  $userId = $_POST['id'];
  
  // Prepare the delete query
  $query = "DELETE FROM users WHERE id = ?";
  
  // Prepare the statement
  if ($stmt = $conn->prepare($query)) {
    // Bind parameters and execute
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
      echo "User deleted successfully";
    } else {
      echo "Error deleting user: " . $stmt->error;
    }
    // Close the statement
    $stmt->close();
  } else {
    echo "Error preparing query: " . $conn->error;
  }
}

// Close the database connection
$conn->close();
?>
