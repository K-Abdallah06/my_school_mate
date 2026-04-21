 <?php
// admin/manage_subjects.php
include '../config/db_connect.php';
include '../config/functions.php';
check_login();

// --- 1. DELETE LOGIC ---
if(isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Delete the subject
    $del = $conn->query("DELETE FROM subjects WHERE id = '$delete_id'");
    
    if($del) {
        // Also delete any connections to classes (optional cleanup)
        $conn->query("DELETE FROM class_subjects WHERE subject_id = '$delete_id'");
        
        header("Location: manage_subjects.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// --- 2. ADD SUBJECT LOGIC ---
$msg = "";
if(isset($_POST['add_subject'])) {
    $subject_name = clean_input($_POST['subject_name']);
    
    $check = $conn->query("SELECT * FROM subjects WHERE subject_name = '$subject_name'");
    if($check->num_rows > 0) {
        $msg = "<div class='alert-danger'>Subject already exists!</div>";
    } else {
        $insert = $conn->query("INSERT INTO subjects (subject_name) VALUES ('$subject_name')");
        if($insert) {
            $msg = "<div class='alert-success'>Subject added successfully!</div>";
        } else {
            $msg = "<div class='alert-danger'>Error adding subject.</div>";
        }
    }
}

// Fetch all subjects
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Subjects</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .alert-success { color: green; padding: 10px; border: 1px solid green; margin-bottom: 10px; }
        .alert-danger { color: red; padding: 10px; border: 1px solid red; margin-bottom: 10px; }
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
            <a href="manage_subjects.php" class="active">Manage Subjects</a>
            <a href="assign_subjects.php">Assign Teachers</a>
             <a href="approve_results.php"> Approve Results</a>
            <a href="manage_settings.php">School Settings</a>
            <a href="../logout.php">Logout</a>
        </div>

        <div class="main-content">
            <h2>Manage Subjects</h2>
            <?php echo $msg; ?>

            <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <form method="POST" style="display: flex; gap: 10px;">
                    <input type="text" name="subject_name" placeholder="Enter Subject Name (e.g. Mathematics)" required style="flex: 1; padding: 10px;">
                    <button type="submit" name="add_subject" style="width: 500px;" class="btn-primary">Add Subject</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($subjects->num_rows > 0): ?>
                        <?php while($row = $subjects->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['subject_name']; ?></td>
                            <td>
                                <a href="manage_subjects.php?delete=<?php echo $row['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this subject?')"
                                   style="color: red; text-decoration: none; font-weight: bold;">
                                   Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No subjects found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>