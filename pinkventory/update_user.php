<?php
include('connect.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM users WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $user = mysqli_fetch_assoc($result);
    } else {
        die("User not found: " . mysqli_error($conn));
    }
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $email = $_POST['email'];
    $fullname = $_POST['fullname'];
    $gender = $_POST['gender'];
    
    $query = "UPDATE users SET email='$email', fullname='$fullname', gender='$gender' WHERE id=$id";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('User updated successfully'); window.location.href='homepage.php';</script>";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <link rel="stylesheet" type="text/css" href="update.css">
</head>
<body>
    <div class="container">
        <h2>Update User</h2>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
            <label>Full Name:</label>
            <input type="text" name="fullname" value="<?php echo $user['fullname']; ?>" required>
            <label>Gender:</label>
            <select name="gender" required>
                <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
            </select>
            <button type="submit" name="update">Update</button>
        </form>
    </div>
</body>
</html>