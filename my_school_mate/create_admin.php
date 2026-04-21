<?php
// create_admin.php
include 'config/db_connect.php';

// 1. Define the temporary admin details
$username = "admin";
$password = "12345"; 
$role = "admin";
$full_name = "System Admin";

// 2. Encrypt the password (Crucial Step!)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 3. Delete old admin if exists (to avoid duplicates)
$conn->query("DELETE FROM users WHERE username='admin'");

// 4. Insert the new Admin
$sql = "INSERT INTO users (username, password, role, full_name) 
        VALUES ('$username', '$hashed_password', '$role', '$full_name')";

if ($conn->query($sql) === TRUE) {
    echo "<h1 style='color:green; text-align:center; margin-top:50px;'>Success!</h1>";
    echo "<p style='text-align:center;'>User: <b>admin</b><br>Pass: <b>12345</b></p>";
    echo "<p style='text-align:center;'><a href='login.php'>Go to Login Page</a></p>";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}
?>