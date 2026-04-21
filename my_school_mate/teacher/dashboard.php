 <?php
// teacher/dashboard.php
session_start();
include '../config/db_connect.php';
include '../config/functions.php';

// 1. Check if user is logged in AND is a teacher
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'];

// 2. Fetch Assigned Classes & Subjects from the NEW table
$query = "SELECT ta.*, c.class_name, s.subject_name 
          FROM teacher_allocations ta
          JOIN classes c ON ta.class_id = c.id
          JOIN subjects s ON ta.subject_id = s.id
          WHERE ta.teacher_id = '$teacher_id'";

$allocations = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; }
        .header { background-color: #0056b3; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; color:white;}
        .logout-btn { color: white; text-decoration: none; background: #d9534f; padding: 8px 15px; border-radius: 4px; }
        
        .container { max-width: 1000px; margin: 30px auto; padding: 20px; }
        .welcome-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        /* Card Grid for Subjects */
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid #28a745; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card h3 { margin-top: 0; color: #333; }
        .card p { color: #666; margin-bottom: 20px; }
        .btn-enter { display: inline-block; background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-enter:hover { background: #0056b3; }
    </style>
</head>
<body>

    <div class="header">
        <div style="display:flex; align-items:center; gap:10px;">
            <img src="../assets/images/logo.png" style="height: 40px;">
            <h2>Teacher Portal</h2>
        </div>
        <div>
            <span>Welcome, <?php echo $teacher_name; ?></span> | 
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-box">
            <h3>My Assigned Subjects</h3>
            <p>Select a class below to enter results for students.</p>
        </div>

        <?php
// ... keep your existing top code ...

// CHECK IF TEACHER IS A FORM MASTER (CLASS TEACHER)
$my_id = $_SESSION['user_id'];
$class_teacher_check = $conn->query("SELECT * FROM classes WHERE class_teacher_id = '$my_id'");
?>

<?php if($class_teacher_check->num_rows > 0): ?>
    <div style="background: #fff3cd; border: 1px solid #ffeeba; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="color: #0056b3; margin-top: 0;">You are a Class Teacher!</h3>
        <?php while($c_row = $class_teacher_check->fetch_assoc()): ?>
            <p>You are managing: <strong><?php echo $c_row['class_name']; ?></strong></p>
            <a href="class_remarks.php?class_id=<?php echo $c_row['id']; ?>" 
               style="background: #0056b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
               Manage Student Remarks
            </a>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

        <div class="grid-container">
            <?php if($allocations->num_rows > 0): ?>
                <?php while($row = $allocations->fetch_assoc()): ?>
                    
                    <div class="card">
                        <h3><?php echo $row['subject_name']; ?></h3>
                        <p>Class: <strong><?php echo $row['class_name']; ?></strong></p>
                        
                        <a href="enter_scores.php?class_id=<?php echo $row['class_id']; ?>&subject_id=<?php echo $row['subject_id']; ?>" class="btn-enter">
                            Enter Scores &rarr;
                        </a>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666; background: white; border-radius: 8px;">
                    <h3>You have not been assigned to any subjects yet.</h3>
                    <p>Please contact the Administrator.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>