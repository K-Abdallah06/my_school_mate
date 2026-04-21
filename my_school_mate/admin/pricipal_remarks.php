 <?php
// admin/principal_remarks.php
session_start();
include '../config/db_connect.php';
include '../config/functions.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit();
}

// 1. GET FILTER INPUTS
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : null;
$selected_term = isset($_GET['term_id']) ? $_GET['term_id'] : 1; // Default to Term 1

$classes = $conn->query("SELECT * FROM classes");

// 2. SAVE REMARKS
if(isset($_POST['save_remarks'])) {
    $student_ids = $_POST['student_id'];
    $remarks = $_POST['remark'];
    $save_term = $_POST['save_term_id']; // Get the term ID from the hidden input
    
    for($i=0; $i<count($student_ids); $i++) {
        $s_id = $student_ids[$i];
        $rem = clean_input($remarks[$i]);
        
        // Check if row exists
        $check = $conn->query("SELECT id FROM term_remarks 
                               WHERE student_id='$s_id' 
                               AND class_id='$class_id' 
                               AND term_id='$save_term'");
        
        if($check->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE term_remarks SET principal_remark=? WHERE student_id=? AND class_id=? AND term_id=?");
            $stmt->bind_param("siii", $rem, $s_id, $class_id, $save_term);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO term_remarks (student_id, class_id, term_id, principal_remark) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $s_id, $class_id, $save_term, $rem);
            $stmt->execute();
        }
    }
    $msg = "<div style='color:green; padding:10px; background:#d4edda; margin-bottom:15px;'>Remarks Saved Successfully for Term $save_term!</div>";
}

// 3. FETCH STUDENTS & EXISTING REMARKS
$students = null;
if($class_id) {
    // Join matches Student + Class + Selected Term
    $students = $conn->query("SELECT s.id, s.full_name, s.admission_no, r.principal_remark 
                              FROM students s 
                              LEFT JOIN term_remarks r 
                              ON s.id = r.student_id 
                              AND r.class_id = '$class_id' 
                              AND r.term_id = '$selected_term'
                              WHERE s.class_id = '$class_id'");
}
?>

<!DOCTYPE html>
<html>
<head><title>Principal Remarks</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
    <div class="wrapper" style="max-width:1000px; margin:30px auto;">
        <a href="dashboard.php">&larr; Dashboard</a>
        <h2>Principal's Remarks / Approval</h2>
        
        <?php if(isset($msg)) echo $msg; ?>

        <form method="GET" style="margin-bottom: 20px; background:#eee; padding:15px; display:flex; gap:10px; align-items:center;">
            <div>
                <label>Select Class:</label>
                <select name="class_id" required style="padding:8px;">
                    <option value="">Choose Class...</option>
                    <?php while($c = $classes->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>" <?php if($class_id == $c['id']) echo 'selected'; ?>>
                            <?php echo $c['class_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label>Select Term:</label>
                <select name="term_id" required style="padding:8px;">
                    <option value="1" <?php if($selected_term==1) echo 'selected'; ?>>1st Term</option>
                    <option value="2" <?php if($selected_term==2) echo 'selected'; ?>>2nd Term</option>
                    <option value="3" <?php if($selected_term==3) echo 'selected'; ?>>3rd Term</option>
                </select>
            </div>

            <button type="submit" class="btn-primary" style="margin-top:20px;">Load Students</button>
        </form>

        
        <?php if($students && $students->num_rows > 0): ?>
        <form method="POST">
            <input type="hidden" name="save_term_id" value="<?php echo $selected_term; ?>">

            <table style="width:100%; border-collapse: collapse;">
                <tr style="background: #333; color:white;">
                    <th style="padding:10px; text-align:left;">Student</th>
                    <th style="padding:10px; text-align:left;">Action</th>
                    <th style="padding:10px; text-align:left;">Write Remark (Term <?php echo $selected_term; ?>)</th>
                </tr>
                <?php while($row = $students->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="padding:10px;">
                        <b><?php echo $row['full_name']; ?></b><br>
                        <small><?php echo $row['admission_no']; ?></small>
                    </td>
                    <td style="padding:10px;">
                        <a href="../student/view_result.php?student_id=<?php echo $row['id']; ?>&term_id=<?php echo $selected_term; ?>" 
                           target="_blank" 
                           style="color: blue; text-decoration:underline;">View Result</a>
                    </td>
                    <td style="padding:10px;">
                        <input type="hidden" name="student_id[]" value="<?php echo $row['id']; ?>">
                        <textarea name="remark[]" style="width:100%; height:40px;"><?php echo $row['principal_remark']; ?></textarea>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <button type="submit" name="save_remarks" class="btn-primary" style="margin-top:20px; width:100%; padding:15px;">SAVE REMARKS FOR TERM <?php echo $selected_term; ?></button>
        </form>
        <?php elseif($class_id): ?>
            <p>No students found in this class.</p>
        <?php endif; ?>
    </div>
</body>
</html>