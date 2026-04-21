 <?php
// admin/manage_classes.php
include '../config/db_connect.php';
include '../config/functions.php';
check_login();

// 1. UPDATE CLASS TEACHER LOGIC
if(isset($_POST['assign_teacher'])) {
    $class_id = $_POST['class_id'];
    $teacher_id = $_POST['teacher_id'];
    $conn->query("UPDATE classes SET class_teacher_id='$teacher_id' WHERE id='$class_id'");
    $msg = "<div class='alert-success'>Class Teacher Assigned!</div>";
}

// 2. ADD CLASS
if(isset($_POST['add_class'])) {
    $class_name = clean_input($_POST['class_name']);
    $conn->query("INSERT INTO classes (class_name) VALUES ('$class_name')");
    header("Location: manage_classes.php"); exit();
}

// 3. DELETE CLASS
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM classes WHERE id='$id'");
    header("Location: manage_classes.php"); exit();
}

// Fetch Classes & Teachers
$classes = $conn->query("SELECT c.*, u.full_name as teacher_name 
                         FROM classes c 
                         LEFT JOIN users u ON c.class_teacher_id = u.id");
$teachers = $conn->query("SELECT * FROM users WHERE role='teacher'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Classes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style> .alert-success { color: green; padding: 10px; background: #e6fffa; margin-bottom: 10px; } </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_classes.php" class="active">Manage Classes</a>
            <a href="../logout.php">Logout</a>
        </div>

        <div class="main-content">
            <h2>Manage Classes & Form Masters</h2>
            <?php if(isset($msg)) echo $msg; ?>
            
            <div style="background:white; padding:20px; border-radius:8px; margin-bottom:20px;">
                <form method="POST" style="display:flex; gap:10px;">
                    <input type="text" name="class_name" placeholder="New Class Name" required style="padding:10px;">
                    <button type="submit" name="add_class" class="btn-primary">Add Class</button>
                </form>
            </div>

            <table class="table" style="width:100%; background:white;">
                <thead>
                    <tr style="background:#eee;">
                        <th>Class Name</th>
                        <th>Current Class Teacher</th>
                        <th>Assign New Teacher</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $classes->fetch_assoc()): ?>
                    <tr>
                        <td><b><?php echo $row['class_name']; ?></b></td>
                        
                        <td style="color: blue;">
                            <?php echo $row['teacher_name'] ? $row['teacher_name'] : "Not Assigned"; ?>
                        </td>

                        <td>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="class_id" value="<?php echo $row['id']; ?>">
                                <select name="teacher_id" required style="padding:5px;">
                                    <option value="">Select Teacher...</option>
                                    <?php 
                                    $teachers->data_seek(0); 
                                    while($t = $teachers->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo $t['full_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" name="assign_teacher" style="cursor:pointer; background:#333; color:white; border:none; padding:5px;">Save</button>
                            </form>
                        </td>

                        <td><a href="manage_classes.php?delete=<?php echo $row['id']; ?>" style="color:red;">Delete</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>