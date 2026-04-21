 <?php
// teacher/enter_scores.php
session_start();
include '../config/db_connect.php';
include '../config/functions.php';

// 1. Check Login
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php"); exit();
}

// 2. Validate URL Parameters
if(!isset($_GET['class_id']) || !isset($_GET['subject_id'])) {
    die("<h3 style='color:red; text-align:center;'>Error: No Class or Subject selected.<br><a href='dashboard.php'>Go Back</a></h3>");
}

$class_id = $_GET['class_id'];
$subject_id = $_GET['subject_id'];
$msg = "";

// --- NEW FIX: GET CURRENT TERM FROM SETTINGS ---
$settings_q = $conn->query("SELECT * FROM system_settings WHERE id=1");
$settings = $settings_q->fetch_assoc();
$current_term_text = $settings['current_term']; // e.g. "2nd Term"

// Convert text to Number (The database needs 1, 2, or 3)
$term_id = 1; 
if(stripos($current_term_text, "2nd") !== false) { $term_id = 2; }
if(stripos($current_term_text, "3rd") !== false) { $term_id = 3; }
// -----------------------------------------------

// 3. GET INFO
$c_q = $conn->query("SELECT class_name FROM classes WHERE id='$class_id'");
$class_data = $c_q->fetch_assoc();
$class_name = $class_data['class_name'];

$s_q = $conn->query("SELECT subject_name FROM subjects WHERE id='$subject_id'");
$subject_data = $s_q->fetch_assoc();
$subject_name = $subject_data['subject_name'];

// 4. SAVE SCORES LOGIC
if(isset($_POST['save_scores'])) {
    $student_ids = $_POST['student_id'];
    $ca1_scores = $_POST['ca1'];
    $ca2_scores = $_POST['ca2'];
    $exam_scores = $_POST['exam'];

    // Loop through every student
    for($i = 0; $i < count($student_ids); $i++) {
        $s_id = $student_ids[$i];
        
        // Clean inputs (defaults to 0 if empty)
        $ca1 = !empty($ca1_scores[$i]) ? clean_input($ca1_scores[$i]) : 0;
        $ca2 = !empty($ca2_scores[$i]) ? clean_input($ca2_scores[$i]) : 0;
        $exam = !empty($exam_scores[$i]) ? clean_input($exam_scores[$i]) : 0;
        
        // Calculate Total (Server side calculation for safety)
        $total = floatval($ca1) + floatval($ca2) + floatval($exam);

        // Check if score exists FOR THIS SPECIFIC TERM
        $check = $conn->query("SELECT id FROM scores 
                               WHERE student_id='$s_id' 
                               AND subject_id='$subject_id' 
                               AND class_id='$class_id'
                               AND term_id='$term_id'"); // <--- ADDED THIS CHECK

        if($check->num_rows > 0) {
            // UPDATE
            $conn->query("UPDATE scores SET ca1_score='$ca1', ca2_score='$ca2', exam_score='$exam', total_score='$total' 
                          WHERE student_id='$s_id' AND subject_id='$subject_id' AND class_id='$class_id' AND term_id='$term_id'");
        } else {
            // INSERT
            $conn->query("INSERT INTO scores (student_id, class_id, subject_id, term_id, ca1_score, ca2_score, exam_score, total_score) 
                          VALUES ('$s_id', '$class_id', '$subject_id', '$term_id', '$ca1', '$ca2', '$exam', '$total')");
        }
    }
    $msg = "<div class='alert-success'>Results Saved Successfully for Term $term_id!</div>";
}

// 5. FETCH STUDENTS AND EXISTING SCORES FOR CURRENT TERM
// We select ca1, ca2, exam, and total
$query = "SELECT students.*, scores.ca1_score, scores.ca2_score, scores.exam_score, scores.total_score 
          FROM students 
          LEFT JOIN scores ON students.id = scores.student_id 
          AND scores.subject_id = '$subject_id' 
          AND scores.class_id = '$class_id'
          AND scores.term_id = '$term_id'  -- <--- CRITICAL FIX: Only fetch scores for THIS term
          WHERE students.class_id = '$class_id'
          ORDER BY students.full_name ASC";

$students = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enter Scores - <?php echo $subject_name; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .input-score { width: 50px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
        .input-total { width: 50px; padding: 5px; text-align: center; border: 1px solid #ccc; background-color: #f0f0f0; color: #333; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; margin-bottom: 15px; text-align: center; }
        th { background: #444; color: white; }
    </style>
</head>
<body>

    <div class="wrapper" style="max-width: 1000px; margin: 30px auto;">
        
        <div style="margin-bottom: 20px;">
            <a href="dashboard.php" style="text-decoration: none; color: #333;">&larr; Back to Dashboard</a>
            <h2>Enter Scores</h2>
            <h4 style="color: #666;">
                Class: <span style="color:#007bff"><?php echo $class_name; ?></span> | 
                Subject: <span style="color:#007bff"><?php echo $subject_name; ?></span> |
                Term: <span style="color:#007bff"><?php echo $current_term_text; ?></span>
            </h4>
        </div>

        <?php echo $msg; ?>

        <form method="POST">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 10px; text-align: left;">S/N</th>
                            <th style="padding: 10px; text-align: left;">Name</th>
                            <th style="padding: 10px; text-align: left;">Adm No</th>
                            <th style="padding: 10px; text-align: center;">1st CA (20)</th>
                            <th style="padding: 10px; text-align: center;">2nd CA (20)</th>
                            <th style="padding: 10px; text-align: center;">Exam (60)</th>
                            <th style="padding: 10px; text-align: center;">Total (100)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($students->num_rows > 0): 
                            $count = 1;
                            while($row = $students->fetch_assoc()): 
                                // ID for javascript targeting
                                $rid = $row['id']; 
                        ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?php echo $count++; ?></td>
                                <td style="padding: 10px; font-weight: bold;"><?php echo $row['full_name']; ?></td>
                                <td style="padding: 10px; color: #666;"><?php echo $row['admission_no']; ?></td>
                                
                                <input type="hidden" name="student_id[]" value="<?php echo $row['id']; ?>">
                                
                                <td style="text-align: center;">
                                    <input type="number" name="ca1[]" id="ca1_<?php echo $rid; ?>" class="input-score" 
                                           value="<?php echo $row['ca1_score']; ?>" 
                                           min="0" max="20" step="0.1" 
                                           oninput="calculateTotal(<?php echo $rid; ?>)">
                                </td>

                                <td style="text-align: center;">
                                    <input type="number" name="ca2[]" id="ca2_<?php echo $rid; ?>" class="input-score" 
                                           value="<?php echo $row['ca2_score']; ?>" 
                                           min="0" max="20" step="0.1" 
                                           oninput="calculateTotal(<?php echo $rid; ?>)">
                                </td>

                                <td style="text-align: center;">
                                    <input type="number" name="exam[]" id="exam_<?php echo $rid; ?>" class="input-score" 
                                           value="<?php echo $row['exam_score']; ?>" 
                                           min="0" max="60" step="0.1" 
                                           oninput="calculateTotal(<?php echo $rid; ?>)">
                                </td>

                                <td style="text-align: center;">
                                    <input type="number" name="total_display[]" id="total_<?php echo $rid; ?>" class="input-total" 
                                           value="<?php echo $row['total_score']; ?>" readonly>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                            <tr><td colspan="7" style="text-align: center; padding: 20px; color: red;">No students found in this class.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if($students->num_rows > 0): ?>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" name="save_scores" class="btn-primary" style="padding: 12px 30px;">Save Results</button>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
        function calculateTotal(studentId) {
            // 1. Get values from the inputs
            var ca1 = document.getElementById('ca1_' + studentId).value;
            var ca2 = document.getElementById('ca2_' + studentId).value;
            var exam = document.getElementById('exam_' + studentId).value;

            // 2. Convert to numbers (default to 0 if empty)
            var val1 = ca1 === "" ? 0 : parseFloat(ca1);
            var val2 = ca2 === "" ? 0 : parseFloat(ca2);
            var val3 = exam === "" ? 0 : parseFloat(exam);

            // 3. Add them up
            var total = val1 + val2 + val3;

            // 4. Show in the Total box (fixed to 1 decimal place if needed)
            document.getElementById('total_' + studentId).value = total;
        }
    </script>

</body>
</html>