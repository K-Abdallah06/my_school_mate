<?php
// admin/approve_results.php
include '../config/db_connect.php';
include '../config/functions.php';
check_login();

// 1. Get System Settings for display only
$settings = $conn->query("SELECT * FROM system_settings LIMIT 1")->fetch_assoc();
$display_session = $settings['current_session'];

// 2. Fetch Classes for the Dropdown
$classes = $conn->query("SELECT * FROM classes");

// --- GET SELECTED CLASS & TERM ---
$selected_class = isset($_GET['class_id']) ? $_GET['class_id'] : 0;
$selected_term_id = isset($_GET['term_id']) ? $_GET['term_id'] : 1; 

// 3. Handle Approval & Principal Remark Saving
if(isset($_POST['update_results'])) {
    if(isset($_POST['student_id'])) {
        
        $save_term_id = $_POST['save_term_id']; 
        $class_id = $_POST['save_class_id'];

        foreach($_POST['student_id'] as $s_id) {
            
            // Get the remark for this specific student ID
            $p_remark = clean_input($_POST['p_remark_'.$s_id]);
            
            // --- FIX: Checkbox Logic ---
            // We check specifically for "approve_STUDENTID"
            $approve = isset($_POST['approve_'.$s_id]) ? 1 : 0;
            
            $check = $conn->query("SELECT id FROM term_remarks WHERE student_id='$s_id' AND term_id='$save_term_id'");
            
            if($check->num_rows > 0) {
                // Update (We now save the 'is_approved' status!)
                $conn->query("UPDATE term_remarks SET principal_remark='$p_remark', is_approved='$approve' 
                              WHERE student_id='$s_id' AND term_id='$save_term_id'");
            } else {
                // Insert
                $conn->query("INSERT INTO term_remarks (student_id, class_id, term_id, principal_remark, is_approved) 
                              VALUES ('$s_id', '$class_id', '$save_term_id', '$p_remark', '$approve')");
            }
        }
        $msg = "<p style='color:green; font-weight:bold; background:#d4edda; padding:10px; border-radius:5px;'>Results Updated Successfully for Term $save_term_id!</p>";
    }
}

// 4. Fetch Students if a Class is Selected
$students = [];
if($selected_class) {
    // --- FIX: We now select 'r.is_approved' so we can show the tick correctly ---
    $students = $conn->query("SELECT s.id, s.full_name, r.teacher_remark, r.principal_remark, r.is_approved 
                              FROM students s 
                              LEFT JOIN term_remarks r ON s.id = r.student_id AND r.term_id='$selected_term_id'
                              WHERE s.class_id = '$selected_class'");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approve Results - SALLDANS ROYAL ACADEMY</title>
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
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_classes.php">Manage Classes</a>
            <a href="manage_subjects.php">Manage Subjects</a>
             <a href="approve_results.php" class="active"> Approve Results</a>
            <a href="manage_settings.php">School Settings</a>
            <a href="../logout.php">Logout</a>
        </div>

        <div class="main-content">
            <h2>Approve Results</h2>
            <?php if(isset($msg)) echo $msg; ?>
            
            <div style="background:white; padding:15px; border-radius:5px; margin-bottom:20px;">
                <form method="GET">
                    <label><strong>Select Class:</strong></label>
                    <select name="class_id" required style="padding:5px;">
                        <option value="">-- Select Class --</option>
                        <?php while($c = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php if($selected_class == $c['id']) echo 'selected'; ?>>
                                <?php echo $c['class_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label style="margin-left:15px;"><strong>Select Term:</strong></label>
                    <select name="term_id" required style="padding:5px;">
                        <option value="1" <?php if($selected_term_id == 1) echo 'selected'; ?>>1st Term</option>
                        <option value="2" <?php if($selected_term_id == 2) echo 'selected'; ?>>2nd Term</option>
                        <option value="3" <?php if($selected_term_id == 3) echo 'selected'; ?>>3rd Term</option>
                    </select>

                    <button type="submit" class="btn-primary" style="margin-left:10px; padding:5px 15px;">Load</button>
                </form>
            </div>

            <?php if($selected_class && $students->num_rows > 0): ?>
                <form method="POST">
                    <input type="hidden" name="save_class_id" value="<?php echo $selected_class; ?>">
                    <input type="hidden" name="save_term_id" value="<?php echo $selected_term_id; ?>">

                    <table>
                        <thead>
                            <tr>
                                <th style="width:20%;">Student Name</th>
                                <th style="width:25%;">Class Teacher's Remark</th>
                                <th style="width:25%;">Principal's Remark</th>
                                <th style="width:10%; text-align:center;">Approve</th>
                                <th style="width:20%; text-align:center;">Action</th> </tr>
                        </thead>
                        <tbody>
                            <?php while($s = $students->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $s['full_name']; ?></strong>
                                    <input type="hidden" name="student_id[]" value="<?php echo $s['id']; ?>">
                                </td>
                                <td style="color:#555; font-style:italic; font-size:0.9em;">
                                    <?php 
                                        if($s['teacher_remark']) {
                                            echo $s['teacher_remark'];
                                        } else {
                                            echo "<span style='color:red;'>Not entered yet</span>";
                                        }
                                    ?>
                                </td>
                                <td>
                                    <input type="text" name="p_remark_<?php echo $s['id']; ?>" value="<?php echo $s['principal_remark']; ?>" placeholder="Enter Principal's Comment" style="width:100%;">
                                </td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="approve_<?php echo $s['id']; ?>" value="1" 
                                    <?php if(isset($s['is_approved']) && $s['is_approved'] == 1) echo "checked"; ?>>
                                </td>
                                <td style="text-align:center;">
                                    <a href="../student/view_result.php?student_id=<?php echo $s['id']; ?>&term_id=<?php echo $selected_term_id; ?>" target="_blank" class="btn-primary" style="padding:6px 12px; font-size:12px; text-decoration:none;">
                                        <i class="fas fa-print"></i> Preview/Print
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top:20px; padding:20px; background:#f9f9f9; border-top:2px solid #ddd;">
                        <button type="submit" name="update_results" class="btn-primary" style="width:auto; padding:10px 30px;">
                            <i class="fas fa-save"></i> Save Remarks for Term <?php echo $selected_term_id; ?>
                        </button>
                    </div>
                </form>

            <?php elseif($selected_class): ?>
                <div style="background:white; padding:20px; text-align:center; color:gray;">
                    <h3>No students found in this class.</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>