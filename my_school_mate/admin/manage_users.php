 <?php
// admin/manage_users.php
session_start();
include '../config/db_connect.php';
include '../config/functions.php';

// Check if Admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";

// --- 1. DELETE LOGIC ---
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Delete from all tables to be safe (Clean up everything connected to this user)
    $conn->query("DELETE FROM users WHERE id='$id'");
    $conn->query("DELETE FROM students WHERE user_id='$id'");
    $conn->query("DELETE FROM teacher_allocations WHERE teacher_id='$id'");
    $conn->query("DELETE FROM scores WHERE student_id IN (SELECT id FROM students WHERE user_id='$id')");
    
    header("Location: manage_users.php");
    exit();
}

// --- 2. ADD USER LOGIC ---
if(isset($_POST['add_user'])) {
    $role = clean_input($_POST['role']);
    $full_name = clean_input($_POST['full_name']);
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']); // Plain text
    
    // Check if username exists
    $check = $conn->query("SELECT * FROM users WHERE username = '$username'");
    if($check->num_rows > 0) {
        $msg = "<div class='alert-danger'>Username already exists!</div>";
    } else {
        // A. Insert into USERS table
        $conn->query("INSERT INTO users (full_name, username, password, role) VALUES ('$full_name', '$username', '$password', '$role')");
        $user_id = $conn->insert_id; // Get the ID of the new user
        
        // B. If STUDENT -> Add to 'students' table
        if($role == 'student') {
            $class_id = $_POST['class_id'];
            $adm_no = $_POST['admission_no'];
            $conn->query("INSERT INTO students (user_id, full_name, admission_no, class_id) VALUES ('$user_id', '$full_name', '$adm_no', '$class_id')");
        }
        
        // C. If TEACHER -> Add to 'teacher_allocations' table (THIS IS THE NEW PART)
        if($role == 'teacher') {
            // Only add if they actually selected a class and subject
            if(!empty($_POST['teacher_class_id']) && !empty($_POST['teacher_subject_id'])) {
                $tc_id = $_POST['teacher_class_id'];
                $ts_id = $_POST['teacher_subject_id'];
                $conn->query("INSERT INTO teacher_allocations (teacher_id, class_id, subject_id) VALUES ('$user_id', '$tc_id', '$ts_id')");
            }
        }
        
        $msg = "<div class='alert-success'>New $role added successfully!</div>";
    }
}

// --- 3. FETCH DATA ---
$classes = $conn->query("SELECT * FROM classes");
$subjects = $conn->query("SELECT * FROM subjects");

// Fetch Users
$users = $conn->query("SELECT u.*, s.class_id, c.class_name 
                       FROM users u 
                       LEFT JOIN students s ON u.id = s.user_id 
                       LEFT JOIN classes c ON s.class_id = c.id 
                       ORDER BY u.id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Styling for the form elements */
        select, input { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .hidden-group { display: none; background: #f9f9f9; padding: 15px; border: 1px dashed #ccc; margin-bottom: 15px; border-radius: 5px; }
        .alert-success { color: green; padding: 10px; border: 1px solid green; margin-bottom: 10px; background: #e6fffa; }
        .alert-danger { color: red; padding: 10px; border: 1px solid red; margin-bottom: 10px; background: #ffe6e6; }
    </style>
    
    <script>
        // This JavaScript hides/shows dropdowns based on Role selection
        function toggleFields() {
            var role = document.getElementById('role_select').value;
            
            // Hide everything first
            document.getElementById('student_fields').style.display = 'none';
            document.getElementById('teacher_fields').style.display = 'none';
            
            // Show the correct box
            if(role === 'student') {
                document.getElementById('student_fields').style.display = 'block';
            } else if(role === 'teacher') {
                document.getElementById('teacher_fields').style.display = 'block';
            }
        }
    </script>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <div style="text-align:center; padding-bottom: 20px;">
                <img src="../assets/images/logo.png" style="width:80px; height:80px;">
                <h4>ADMIN PANEL</h4>
            </div>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php" class="active">Manage Users</a>
            <a href="manage_classes.php">Manage Classes</a>
            <a href="manage_subjects.php">Manage Subjects</a>
            <a href="assign_subjects.php">Assign Teachers</a>
            <a href="approve_results.php">Approve Results</a>
            <a href="manage_settings.php">School Settings</a>
            <a href="../logout.php">Logout</a>
        </div>

        <div class="main-content">
            <h2>Manage Users</h2>
            <?php echo $msg; ?>
            
            <div style="background:white; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1); margin-bottom:20px;">
                <h3 style="margin-top:0;">Add New User</h3>
                <form method="POST">
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="full_name" placeholder="Full Name" required>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="password" placeholder="Password (Plain Text)" required>
                        <select name="role" id="role_select" onchange="toggleFields()" required>
                            <option value="">Select Role...</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div id="student_fields" class="hidden-group">
                        <label style="font-weight:bold; color:blue;">Student Details:</label>
                        <input type="text" name="admission_no" placeholder="Admission Number">
                        <select name="class_id">
                            <option value="">Select Class...</option>
                            <?php 
                            $classes->data_seek(0); // Reset pointer
                            while($c = $classes->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="teacher_fields" class="hidden-group">
                        <label style="font-weight:bold; color:green;">Assign Initial Subject (Optional):</label>
                        <p style="margin: 0 0 10px 0; font-size: 13px; color: #666;">Assign the teacher to a class and subject now. You can add more later.</p>
                        <div style="display:flex; gap:10px;">
                            <select name="teacher_class_id">
                                <option value="">Select Class...</option>
                                <?php 
                                $classes->data_seek(0); // Reset pointer
                                while($c = $classes->fetch_assoc()): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                            
                            <select name="teacher_subject_id">
                                <option value="">Select Subject...</option>
                                <?php 
                                $subjects->data_seek(0); // Reset pointer
                                while($s = $subjects->fetch_assoc()): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="add_user" class="btn-primary" style="width:100%;">Create User</button>
                </form>
            </div>

            <table class="table" style="width:100%; border-collapse:collapse; background:white;">
                <thead>
                    <tr style="background:#eee; text-align:left;">
                        <th>Role</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Assigned To</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($users->num_rows > 0): ?>
                        <?php while($row = $users->fetch_assoc()): ?>
                        <tr style="border-bottom:1px solid #ddd;">
                            <td><strong style="text-transform:capitalize;"><?php echo $row['role']; ?></strong></td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['password']; ?></td>
                            
                            <td style="font-size: 14px;">
                                <?php 
                                if($row['role'] == 'student') {
                                    // Show Student Class
                                    echo $row['class_name'] ? "<b>".$row['class_name']."</b>" : "<span style='color:red'>No Class</span>";
                                } 
                                elseif($row['role'] == 'teacher') {
                                    // FIX: Look up the teacher_allocations table
                                    $tid = $row['id'];
                                    $alloc_q = $conn->query("SELECT c.class_name, s.subject_name 
                                                             FROM teacher_allocations ta 
                                                             JOIN classes c ON ta.class_id = c.id 
                                                             JOIN subjects s ON ta.subject_id = s.id 
                                                             WHERE ta.teacher_id = '$tid'");
                                    
                                    if($alloc_q->num_rows > 0) {
                                        // List all assigned subjects
                                        while($a = $alloc_q->fetch_assoc()) {
                                            echo "<div style='border-bottom:1px solid #eee; padding:2px;'>• " . $a['subject_name'] . " (" . $a['class_name'] . ")</div>";
                                        }
                                    } else {
                                        echo "<span style='color:gray; font-style:italic;'>Not assigned yet</span>";
                                    }
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <td style="text-align: center;">
                                <?php if($row['role'] != 'admin'): ?> 
                                    
                                    <?php if($row['role'] == 'student'): ?>
                                        <a href="../student/view_result.php?student_id=<?php echo $row['id']; ?>" style="color: blue; margin-right: 10px;">Result</a>
                                    <?php endif; ?>

                                    <a href="edit_user.php?id=<?php echo $row['id']; ?>" style="color: green; margin-right: 10px; font-weight: bold;">Edit</a>

                                    <a href="manage_users.php?delete=<?php echo $row['id']; ?>" 
                                       onclick="return confirm('Are you sure? This will delete the user and all their data!')"
                                       style="color: red; text-decoration: none; font-weight: bold;">
                                       Delete
                                    </a>
                                <?php else: ?>
                                    <span style="color:#999; font-size:12px;">(Protected)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>