<?php
// teacher/manage_remarks.php
include '../config/db_connect.php';
include '../config/functions.php';
check_login();

$class_id = $_GET['class_id'];
$teacher_id = $_SESSION['user_id'];

// Verify Class Teacher
$verify = $conn->query("SELECT * FROM classes WHERE id='$class_id' AND class_teacher_id='$teacher_id'");
if($verify->num_rows == 0) { die("Access Denied"); }
$class_info = $verify->fetch_assoc();

$settings = $conn->query("SELECT * FROM system_settings LIMIT 1")->fetch_assoc();
$session = $settings['current_session'];
$term = $settings['current_term'];

// Save Remarks
if(isset($_POST['save_remarks'])) {
    foreach($_POST['student_id'] as $index => $s_id) {
        $remark = clean_input($_POST['remark'][$index]);
        if(!empty($remark)) {
            // Check if exists
            $check = $conn->query("SELECT id FROM remarks WHERE student_id='$s_id' AND term='$term' AND session='$session'");
            if($check->num_rows > 0) {
                // If exists, update ONLY if not approved yet
                $conn->query("UPDATE remarks SET teacher_remark='$remark', class_id='$class_id' WHERE student_id='$s_id' AND term='$term' AND session='$session' AND is_approved=0");
            } else {
                $conn->query("INSERT INTO remarks (student_id, class_id, term, session, teacher_remark) VALUES ('$s_id', '$class_id', '$term', '$session', '$remark')");
            }
        }
    }
    echo "<script>alert('Remarks Saved!');</script>";
}

// 1. Fetch all students in class
$students = $conn->query("SELECT * FROM students WHERE class_id='$class_id'");
$student_data = [];

// 2. Calculate Totals and Averages for Ranking
while($s = $students->fetch_assoc()) {
    // Get all scores for this student this term
    $scores = $conn->query("SELECT total FROM scores WHERE student_id='".$s['id']."' AND term='$term' AND session='$session'");
    $total_score = 0;
    $subject_count = 0;
    while($sc = $scores->fetch_assoc()) {
        $total_score += $sc['total'];
        $subject_count++;
    }
    
    $average = ($subject_count > 0) ? ($total_score / $subject_count) : 0;
    
    // Get existing remark
    $rem_q = $conn->query("SELECT teacher_remark FROM remarks WHERE student_id='".$s['id']."' AND term='$term' AND session='$session'");
    $existing_remark = ($rem_q->num_rows > 0) ? $rem_q->fetch_assoc()['teacher_remark'] : "";

    $student_data[] = [
        'id' => $s['id'],
        'name' => $s['full_name'],
        'average' => $average,
        'count' => $subject_count,
        'remark' => $existing_remark
    ];
}

// 3. Sort by Average (Descending) to determine Position
usort($student_data, function($a, $b) {
    return $b['average'] <=> $a['average'];
});

?>

<!DOCTYPE html>
<html>
<head>
    <title>Class Remarks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <div class="main-content">
            <a href="dashboard.php">Back to Dashboard</a>
            <h2>Class Broad Sheet: <?php echo $class_info['class_name']; ?></h2>
            <p><strong>Note:</strong> Once you save a remark, subject teachers cannot edit scores for that student.</p>
            
            <form method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Student Name</th>
                            <th>Subj Taken</th>
                            <th>Average</th>
                            <th>Class Teacher's Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach($student_data as $s): ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td>
                                <?php echo $s['name']; ?>
                                <input type="hidden" name="student_id[]" value="<?php echo $s['id']; ?>">
                            </td>
                            <td><?php echo $s['count']; ?></td>
                            <td><?php echo number_format($s['average'], 2); ?>%</td>
                            <td>
                                <input type="text" name="remark[]" value="<?php echo $s['remark']; ?>" placeholder="Enter Remark" style="width:100%;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
                <button type="submit" name="save_remarks" class="btn-primary">Sign & Save Remarks</button>
            </form>
        </div>
    </div>
</body>
</html>