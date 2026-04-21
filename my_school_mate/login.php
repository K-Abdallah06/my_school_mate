 <?php
// login.php
session_start();
include 'config/db_connect.php';
include 'config/functions.php';

$msg = ""; 

if(isset($_POST['login'])) {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']); 
    
    // 1. Get the user from the database
    $query = $conn->query("SELECT * FROM users WHERE username = '$username'");
    
    if($query->num_rows > 0) {
        $row = $query->fetch_assoc();
        
        $login_successful = false; // A flag to track if password is correct

        // --- HYBRID CHECK LOGIC ---
        
        if ($row['role'] == 'admin') {
            // ADMIN: Use Secure Hash Check
            if (password_verify($password, $row['password'])) {
                $login_successful = true;
            }
        } else {
            // TEACHERS/STUDENTS: Use Simple Plain Text Check
            if ($password == $row['password']) {
                $login_successful = true;
            }
        }

        // --- FINAL RESULT ---
        if ($login_successful) {
            
            // Set Session Variables
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            
            // Redirect based on role
            if($row['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } elseif($row['role'] == 'teacher') {
                header("Location: teacher/dashboard.php");
            } elseif($row['role'] == 'student') {
                header("Location: student/dashboard.php");
            }
            exit();
            
        } else {
            $msg = "<div style='color:red; background:#ffd2d2; padding:10px; border:1px solid red; border-radius:5px; margin-bottom:15px;'>Incorrect Password!</div>";
        }
    } else {
        $msg = "<div style='color:red; background:#ffd2d2; padding:10px; border:1px solid red; border-radius:5px; margin-bottom:15px;'>User not found!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School System Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; }
        .form-group input { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; 
        }
        .btn-login {
            width: 100%; padding: 10px; background: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
        }
        .btn-login:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="assets/images/logo.png" style="width: 80px; margin-bottom: 10px;">
        <h2>SALLDANS ROYAL ACADEMY</h2>
        <p>Striving Toward Excellence</p>
        <hr>
        <h3>Login</h3>
        <?php echo $msg; ?>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" placeholder="Username" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" placeholder="Password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn-login">Sign In</button>
        </form>
    </div>
</body>
</html>