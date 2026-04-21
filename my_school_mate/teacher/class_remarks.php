 <?php
// teacher/class_remarks.php
session_start();
include '../config/db_connect.php';
include '../config/functions.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') { header("Location: ../login.php"); exit(); }

$class_id = $_GET['class_id'];
$teacher_id = $_SESSION['user_id'];

// 1. GET SYSTEM SETTINGS (Current Session & Term)
$settings_q = $conn->query("SELECT * FROM system_settings WHERE id=1");
$settings = $settings_q->fetch_assoc();
$current_session = $settings['current_session'];
$current_term_text = $settings['current_term']; // e.g. "2nd Term"

// Convert text to Number (The database needs 1, 2, or 3)
$term_id = 1; 
if(stripos($current_term_text, "2nd") !== false) { $term_id = 2; }
if(stripos($current_term_text, "3rd") !== false) { $term_id = 3; }


// 2. Verify this teacher actually owns this class
$check = $conn->query("SELECT class_name FROM classes WHERE id='$class_id' AND class_teacher_id='$teacher_id'");
if($check->num_rows == 0) { die("You are not the class teacher for this class."); }
$class_info = $check->fetch_assoc();


// 3. SAVE REMARKS LOGIC
if(isset($_POST['save_remarks'])) {
    $student_ids = $_POST['student_id'];
    $remarks = $_POST['remark'];
    
    for($i=0; $i<count($student_ids); $i++) {
        $s_id = $student_ids[$i];
        $rem = clean_input($remarks[$i]);
        
        // Check if exists FOR THIS TERM
        $exists = $conn->query("SELECT id FROM term_remarks 
                                WHERE student_id='$s_id' 
                                AND class_id='$class_id' 
                                AND term_id='$term_id'"); // <--- ADDED TERM CHECK

        if($exists->num_rows > 0) {
            // Update existing remark for this term
            $conn->query("UPDATE term_remarks SET teacher_remark='$rem' 
                          WHERE student_id='$s_id' AND class_id='$class_id' AND term_id='$term_id'");
        } else {
            // Insert new remark for this term
            $conn->query("INSERT INTO term_remarks (student_id, class_id, term_id, teacher_remark) 
                          VALUES ('$s_id', '$class_id', '$term_id', '$rem')");
        }
    }
    $msg = "<div style='color:green; background:#d4edda; padding:10px; border:1px solid green; margin-bottom:10px;'>Remarks Saved Successfully for Term $term_id!</div>";
}


// 4. Fetch Students & Existing Remarks FOR THIS TERM
$query = "SELECT s.id, s.full_name, s.admission_no, r.teacher_remark 
          FROM students s 
          LEFT JOIN term_remarks r ON s.id = r.student_id 
          AND r.class_id = '$class_id' 
          AND r.term_id = '$term_id'  -- <--- CRITICAL FIX
          WHERE s.class_id = '$class_id'";

$students = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Class Remarks - <?php echo $current_term_text; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper" style="max-width:900px; margin:30px auto;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <a href="dashboard.php" style="text-decoration:none; color:#333;">&larr; Back to Dashboard</a>
                <h2>Class Teacher Remarks</h2>
                <p>Class: <strong><?php echo $class_info['class_name']; ?></strong> | Term: <strong><?php echo $current_term_text; ?></strong></p>
            </div>
        </div>

        <?php if(isset($msg)) echo $msg; ?>
        
        <form method="POST">
            <div style="background:white; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                <table style="width:100%; border-collapse: collapse;">
                    <tr style="background:#f4f4f4; text-align:left;">
                        <th style="padding:10px;">Student Details</th>
                        <th style="padding:10px;">Teacher's Remark</th>
                    </tr>
                    
                    <?php while($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:15px; border-bottom:1px solid #eee; vertical-align:top; width:40%;">
                            <b style="font-size: 16px;"><?php echo $row['full_name']; ?></b><br>
                            <small style="color: #666;"><?php echo $row['admission_no']; ?></small>
                            <br><br>
                            
                            <a href="../student/view_result.php?student_id=<?php echo $row['id']; ?>&term_id=<?php echo $term_id; ?>" 
                               target="_blank" 
                               style="background: #007bff; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 12px;">
                               View Performance Sheet &rarr;
                            </a>
                        </td>
                        <td style="padding:15px; border-bottom:1px solid #eee;">
                            <input type="hidden" name="student_id[]" value="<?php echo $row['id']; ?>">
                            <textarea name="remark[]" placeholder="Enter remark for <?php echo $current_term_text; ?>..." 
                                style="width:100%; height:80px; padding:10px; border:1px solid #ccc; border-radius:4px; font-family:inherit;"><?php echo $row['teacher_remark']; ?></textarea>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                
                <div style="margin-top:20px; text-align:right;">
                    <button type="submit" name="save_remarks" class="btn-primary" style="padding:12px 30px;">Save All Remarks</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>