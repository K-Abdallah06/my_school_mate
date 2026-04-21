 <?php
// student/dashboard.php
session_start();
include '../config/db_connect.php';

// Check if Student
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$s_q = $conn->query("SELECT * FROM students WHERE user_id='$user_id'");
$student = $s_q->fetch_assoc();

// Get School Info for display
$school_q = $conn->query("SELECT * FROM school_settings LIMIT 1");
$school = $school_q->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        .dash-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .welcome-box { text-align: center; margin-bottom: 30px; }
        .welcome-box h2 { color: #003366; margin-bottom: 5px; }
        
        .btn-term {
            display: block;
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            background: #003366;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-term:hover { background: #002244; }
        .logout-link { display: block; text-align: center; margin-top: 20px; color: red; }
    </style>
</head>
<body>

    <div class="dash-container">
        <div class="welcome-box">
            <h2>Welcome, <?php echo $student['full_name']; ?></h2>
            <p>Admission Number: <?php echo $student['admission_no']; ?></p>
            
            <hr>
            <p>Select a term below to view your performance:</p>
        </div>

        <a href="view_result.php?term_id=1" class="btn-term">View 1st Term Result</a>
        <a href="view_result.php?term_id=2" class="btn-term">View 2nd Term Result</a>
        <a href="view_result.php?term_id=3" class="btn-term">View 3rd Term Result</a>

        <a href="../logout.php" class="logout-link">Logout</a>
    </div>

</body>
</html>