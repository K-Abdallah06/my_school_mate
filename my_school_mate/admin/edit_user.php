<?php
// admin/edit_user.php
include '../config/db_connect.php';
include '../config/functions.php';
check_login();

if(!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$id = $_GET['id'];
$msg = "";

// 1. UPDATE LOGIC
if(isset($_POST['update_user'])) {
    $full_name = clean_input($_POST['full_name']);
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    
    // Update main user info
    $conn->query("UPDATE users SET full_name='$full_name', username='$username', password='$password' WHERE id='$id'");
    
    // If it is a STUDENT, update their Class and Admission No
    if(isset($_POST['class_id'])) {
        $class_id = $_POST['class_id'];
        $adm_no = $_POST['admission_no'];
        $conn->query("UPDATE students SET class_id='$class_id', admission_no='$adm_no' WHERE user_id='$id'");
    }
    
    $msg = "<div class='alert-success' style='color:green;'>User updated successfully!</div>";
}

// 2. FETCH CURRENT DATA
$user_q = $conn->query("SELECT * FROM users WHERE id='$id'");
$user = $user_q->fetch_assoc();
$role = $user['role'];

// If student, get student details
$student_details = [];
if($role == 'student') {
    $s_q = $conn->query("SELECT * FROM students WHERE user_id='$id'");
    $student_details = $s_q->fetch_assoc();
}

// Fetch Classes for dropdown
$classes = $conn->query("SELECT * FROM classes");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper" style="display:flex; justify-content:center; align-items:center; height:100vh;">
        <div style="background:white; padding:30px; border-radius:10px; width:400px; box-shadow:0 0 10px rgba(0,0,0,0.1);">
            <h3>Edit User: <?php echo $user['full_name']; ?></h3>
            <?php echo $msg; ?>
            
            <form method="POST">
                <label>Full Name:</label>
                <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required style="width:100%; padding:8px; margin-bottom:10px;">
                
                <label>Username:</label>
                <input type="text" name="username" value="<?php echo $user['username']; ?>" required style="width:100%; padding:8px; margin-bottom:10px;">
                
                <label>Password (Plain Text):</label>
                <input type="text" name="password" value="<?php echo $user['password']; ?>" required style="width:100%; padding:8px; margin-bottom:10px;">

                <?php if($role == 'student'): ?>
                    <label>Admission No:</label>
                    <input type="text" name="admission_no" value="<?php echo $student_details['admission_no']; ?>" style="width:100%; padding:8px; margin-bottom:10px;">
                    
                    <label>Class:</label>
                    <select name="class_id" style="width:100%; padding:8px; margin-bottom:10px;">
                        <?php while($c = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php if($student_details['class_id'] == $c['id']) echo 'selected'; ?>>
                                <?php echo $c['class_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                <?php endif; ?>

                <button type="submit" name="update_user" class="btn-primary" style="width:100%; padding:10px;">Update User</button>
            </form>
            <br>
            <a href="manage_users.php">Back to User List</a>
        </div>
    </div>
</body>
</html>