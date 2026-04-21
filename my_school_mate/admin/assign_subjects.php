<?php
// admin/assign_subjects.php
include '../config/db_connect.php';
include '../config/functions.php';
check_login();

// 1. DELETE ALLOCATION
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM teacher_allocations WHERE id='$id'");
    header("Location: assign_subjects.php"); exit();
}

// 2. ADD ALLOCATION
$msg = "";
if(isset($_POST['assign'])) {
    $teacher_id = $_POST['teacher_id'];
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];
    
    // Check if already assigned
    $check = $conn->query("SELECT * FROM teacher_allocations WHERE class_id='$class_id' AND subject_id='$subject_id'");
    
    if($check->num_rows > 0) {
        $msg = "<div class='alert-danger'>This subject is already assigned to a teacher for this class!</div>";
    } else {
        $conn->query("INSERT INTO teacher_allocations (teacher_id, class_id, subject_id) VALUES ('$teacher_id', '$class_id', '$subject_id')");
        $msg = "<div class='alert-success'>Assigned successfully!</div>";
    }
}

// Fetch Data for Dropdowns
$teachers = $conn->query("SELECT * FROM users WHERE role='teacher'");
$classes = $conn->query("SELECT * FROM classes");
$subjects = $conn->query("SELECT * FROM subjects");

// Fetch Existing Allocations to Display
$allocations = $conn->query("SELECT ta.*, u.full_name, c.class_name, s.subject_name 
                             FROM teacher_allocations ta
                             JOIN users u ON ta.teacher_id = u.id
                             JOIN classes c ON ta.class_id = c.id
                             JOIN subjects s ON ta.subject_id = s.id
                             ORDER BY c.class_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Subjects</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .alert-success { color: green; padding: 10px; border: 1px solid green; margin-bottom: 10px; }
        .alert-danger { color: red; padding: 10px; border: 1px solid red; margin-bottom: 10px; }
        select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; flex: 1; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #3061bd; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <div style="text-align:center; padding-bottom: 20px;">
                <img src="../assets/images/logo.png" style="width:80px; height:80px;">
                <h4>ADMIN PANEL</h4>
            </div>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_classes.php">Manage Classes</a>
            <a href="manage_subjects.php">Manage Subjects</a>
            <a href="assign_subjects.php" class="active">Assign Teachers</a> <a href="approve_results.php">Approve Results</a>
            <a href="manage_settings.php">School Settings</a>
            <a href="../logout.php">Logout</a>
        </div>

        <div class="main-content">
            <h2>Assign Subject to Teacher</h2>
            <?php echo $msg; ?>

            <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <form method="POST" style="display: flex; gap: 10px;">
                    
                    <select name="teacher_id" required>
                        <option value="">Select Teacher...</option>
                        <?php while($t = $teachers->fetch_assoc()): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo $t['full_name']; ?></option>
                        <?php endwhile; ?>
                    </select>

                    <select name="class_id" required>
                        <option value="">Select Class...</option>
                        <?php 
                        $classes->data_seek(0); // Reset pointer
                        while($c = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $c['class_name']; ?></option>
                        <?php endwhile; ?>
                    </select>

                    <select name="subject_id" required>
                        <option value="">Select Subject...</option>
                        <?php 
                        $subjects->data_seek(0); // Reset pointer
                        while($s = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['subject_name']; ?></option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" name="assign" style= "width:400px;" class="btn-primary">Assign</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Teacher Assigned</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($allocations->num_rows > 0): ?>
                        <?php while($row = $allocations->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['class_name']; ?></td>
                            <td><?php echo $row['subject_name']; ?></td>
                            <td><b><?php echo $row['full_name']; ?></b></td>
                            <td>
                                <a href="assign_subjects.php?delete=<?php echo $row['id']; ?>" 
                                   onclick="return confirm('Remove this assignment?')"
                                   style="color: red; text-decoration: none; font-weight: bold;">
                                   Remove
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No teachers assigned yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>