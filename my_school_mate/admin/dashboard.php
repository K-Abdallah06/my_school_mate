 <?php
// admin/dashboard.php
include '../config/db_connect.php';
include '../config/functions.php';
check_login();

// Only Admin access
if ($_SESSION['role'] != 'admin') { header("Location: ../login.php"); exit(); }

// --- 1. NEW LOGIC: Handle Quick Update of Session/Term ---
if(isset($_POST['update_settings'])) {
    $new_sess = $_POST['sess'];
    $new_term = $_POST['term'];
    // Update the system_settings table
    $conn->query("UPDATE system_settings SET current_session='$new_sess', current_term='$new_term' WHERE id=1");
    // Refresh the page to see changes
    header("Location: dashboard.php"); 
    exit();
}
// -----------------------------------------------------------

// Fetch Quick Stats
$count_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
$count_teachers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='teacher'")->fetch_assoc()['c'];
$count_classes = $conn->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'];

// Get System Settings
$settings = $conn->query("SELECT * FROM system_settings LIMIT 1")->fetch_assoc();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - SALLDANS ROYAL ACADEMY</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> 
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <div style="text-align:center; padding-bottom: 20px;">
                <img src="../assets/images/logo.png" style="width:80px; height:80px;">
                <h4>ADMIN PANEL</h4>
            </div>
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
            <a href="manage_classes.php"><i class="fas fa-chalkboard"></i> Classes</a>
            <a href="manage_subjects.php"><i class="fas fa-book"></i> Subjects</a>
            <a href="assign_subjects.php"><i class="fas fa-user-tie"></i> Assign Teachers</a>
            <a href="approve_results.php"><i class="fas fa-check-circle"></i> Approve Results</a>
            <a href="manage_settings.php"><i class="fas fa-cogs"></i> System Settings</a>
            <a href="../logout.php" style="background:var(--danger); margin-top:20px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <h2>Welcome, <?php echo $_SESSION['full_name']; ?></h2>
            
            <div style="background: #e3f2fd; padding: 15px; border-left: 5px solid #003366; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    Current Session: <strong><?php echo $settings['current_session']; ?></strong> | 
                    Term: <strong><?php echo $settings['current_term']; ?></strong>
                </div>
                
                <form method="POST" style="display:flex; gap:5px;">
    <select name="sess" style="padding:5px;">
        <option value="2023/2024" <?php if($settings['current_session'] == '2023/2024') echo 'selected'; ?>>2023/2024</option>
        <option value="2024/2025" <?php if($settings['current_session'] == '2024/2025') echo 'selected'; ?>>2024/2025</option>
        <option value="2025/2026" <?php if($settings['current_session'] == '2025/2026') echo 'selected'; ?>>2025/2026</option>
    </select>

    <select name="term" style="padding:5px;">
        <option value="1st Term" <?php if($settings['current_term'] == '1st Term') echo 'selected'; ?>>1st Term</option>
        <option value="2nd Term" <?php if($settings['current_term'] == '2nd Term') echo 'selected'; ?>>2nd Term</option>
        <option value="3rd Term" <?php if($settings['current_term'] == '3rd Term') echo 'selected'; ?>>3rd Term</option>
    </select>
    
    <button type="submit" name="update_settings" class="btn-primary" style="padding:5px 10px;">Update</button>
</form>
            </div>
            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div class="card" style="background:white; padding:20px; flex:1; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid var(--primary-color);">
                    <h3><?php echo $count_students; ?></h3>
                    <p>Total Students</p>
                </div>
                <div class="card" style="background:white; padding:20px; flex:1; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid var(--secondary-color);">
                    <h3><?php echo $count_teachers; ?></h3>
                    <p>Total Teachers</p>
                </div>
                <div class="card" style="background:white; padding:20px; flex:1; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid var(--success);">
                    <h3><?php echo $count_classes; ?></h3>
                    <p>Active Classes</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>