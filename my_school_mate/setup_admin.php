   <?php
// my_school_mate/setup_admin.php
include 'config/db_connect.php';

// This generates the secure hash for "password123"
$pass = password_hash("password123", PASSWORD_DEFAULT);

// This updates the user 'admin' with the new working password
$sql = "UPDATE users SET password='$pass' WHERE username='admin'";

if($conn->query($sql)) {
    echo "<h1>Success!</h1>";
    echo "<p>Admin password has been reset to: <strong>password123</strong></p>";
    echo "<a href='login.php'>Go to Login Page</a>";
} else {
    echo "Error updating record: " . $conn->error;
}
?>