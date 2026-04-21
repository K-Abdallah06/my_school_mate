<?php
// student/view_result.php
session_start();
include '../config/db_connect.php';
include '../config/functions.php';

// 1. CHECK PERMISSIONS
if(!isset($_SESSION['role'])) { header("Location: ../login.php"); exit(); }
$my_role = $_SESSION['role'];

// 2. GET STUDENT ID & TERM ID
if($my_role == 'admin' || $my_role == 'teacher') {
    $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : die("Error: ID missing");
    $term_id = isset($_GET['term_id']) ? intval($_GET['term_id']) : 1;
} else {
    $user_id = $_SESSION['user_id'];
    $s_q = $conn->query("SELECT id FROM students WHERE user_id='$user_id'");
    $s_data = $s_q->fetch_assoc();
    $student_id = $s_data['id'] ?? die("Student record not found.");
    $term_id = isset($_GET['term_id']) ? intval($_GET['term_id']) : 1;
}

// Convert Term ID to Name
$term_names = [1 => "1st Term", 2 => "2nd Term", 3 => "3rd Term"];
$display_term_name = $term_names[$term_id] ?? $term_id . " Term";

// 3. FETCH SCHOOL SETTINGS
$sett_q = $conn->query("SELECT * FROM school_settings LIMIT 1");
$school = $sett_q->fetch_assoc();
$school_name = $school['school_name'] ?? "SALLDANS ROYAL ACADEMY";
$school_addr = $school['school_address'] ?? "Address Not Set";
$school_motto = $school['school_motto'] ?? "Excellence";
$school_phone = $school['school_phone'] ?? "";
$current_session = $school['current_session'] ?? "2023/2024";
$next_term_begins = $school['next_term_begins'] ?? "To be Announced";
$principal_sig_file = $school['principal_signature'] ?? ""; 

// 4. FETCH STUDENT INFO
$student_q = $conn->query("SELECT s.*, c.class_name, c.class_teacher_id 
                           FROM students s 
                           LEFT JOIN classes c ON s.class_id = c.id 
                           WHERE s.id='$student_id'");
$student = $student_q->fetch_assoc();
if(!$student) die("Student Not Found.");
$class_id = $student['class_id'];

// ==========================================================
//   LOGIC STEP 1: FETCH SCORES
// ==========================================================
$query = "SELECT sc.*, su.subject_name 
          FROM scores sc 
          JOIN subjects su ON sc.subject_id = su.id 
          WHERE sc.student_id = '$student_id' AND sc.term_id = '$term_id'";
$results = $conn->query($query);

$grand_total = 0;
$subject_count = 0;
$result_rows = []; 

while($r = $results->fetch_assoc()) {
    $grand_total += $r['total_score'];
    $subject_count++;
    $result_rows[] = $r; 
}
$average = $subject_count > 0 ? round($grand_total / $subject_count, 2) : 0;

// ==========================================================
//   LOGIC STEP 2: CHECK IF EMPTY
// ==========================================================
if(count($result_rows) == 0) {
    echo "<!DOCTYPE html><html><head><title>No Result</title><meta name='viewport' content='width=device-width, initial-scale=1.0'><style>body{font-family:sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;} .msg-box{background:white; padding:40px; border-radius:10px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.1); max-width:400px;} h2{color:#555;} .btn{display:inline-block; margin-top:20px; padding:10px 20px; background:#003366; color:white; text-decoration:none; border-radius:5px;}</style></head><body><div class='msg-box'><img src='../assets/images/logo.png' style='width:60px; margin-bottom:15px;'><h2>No Result Found</h2><p>No result records found for <strong>$display_term_name</strong> yet.</p><a href='dashboard.php' class='btn'>Back to Dashboard</a></div></body></html>";
    exit();
}

// ==========================================================
//   LOGIC STEP 3: CHECK APPROVAL
// ==========================================================
$approval_q = $conn->query("SELECT is_approved FROM term_remarks 
                            WHERE student_id='$student_id' AND term_id='$term_id'");
$approval_data = $approval_q->fetch_assoc();
$is_approved = ($approval_data && $approval_data['is_approved'] == 1) ? true : false;

if(!$is_approved && $my_role != 'admin') {
    echo "<!DOCTYPE html><html><head><title>Result Not Approved</title><meta name='viewport' content='width=device-width, initial-scale=1.0'><style>body{font-family:sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;} .msg-box{background:white; padding:40px; border-radius:10px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.1); max-width:400px;} h2{color:#d9534f;} .btn{display:inline-block; margin-top:20px; padding:10px 20px; background:#003366; color:white; text-decoration:none; border-radius:5px;}</style></head><body><div class='msg-box'><img src='../assets/images/logo.png' style='width:60px; margin-bottom:15px;'><h2>Result Not Approved</h2><p>The result for <strong>$display_term_name</strong> is ready but awaiting final approval.</p><a href='dashboard.php' class='btn'>Back to Dashboard</a></div></body></html>";
    exit();
}

// ==========================================================
//   LOGIC STEP 4: FETCH POSITION & REMARKS
// ==========================================================
$count_q = $conn->query("SELECT COUNT(*) as total FROM students WHERE class_id='$class_id'");
$class_count_row = $count_q->fetch_assoc();
$total_students_in_class = $class_count_row['total'];

$pos_query = $conn->query("SELECT student_id, SUM(total_score) as class_total 
                           FROM scores 
                           WHERE class_id='$class_id' AND term_id='$term_id' 
                           GROUP BY student_id 
                           ORDER BY class_total DESC");
$rank = 0; $count = 0;
while($p = $pos_query->fetch_assoc()) {
    $count++;
    if($p['student_id'] == $student_id) { $rank = $count; break; }
}
$suffix = "th";
if(!in_array($rank % 100, [11,12,13])){ switch ($rank % 10) { case 1: $suffix = "st"; break; case 2: $suffix = "nd"; break; case 3: $suffix = "rd"; break; } }
$position_string = ($rank > 0) ? $rank . $suffix : "N/A";

$rem_q = $conn->query("SELECT teacher_remark, principal_remark 
                       FROM term_remarks 
                       WHERE student_id='$student_id' AND term_id='$term_id'");
$rem_data = $rem_q->fetch_assoc();
$teacher_remark = $rem_data['teacher_remark'] ?? "No remark yet.";
$principal_remark = $rem_data['principal_remark'] ?? "No remark yet.";

$ct_name = "Not Assigned";
if($student['class_teacher_id']) {
    $t_q = $conn->query("SELECT full_name FROM users WHERE id='".$student['class_teacher_id']."'");
    if($t_r = $t_q->fetch_assoc()) $ct_name = $t_r['full_name'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Result Sheet - <?php echo $student['full_name']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f4f4; margin: 0; padding: 10px; }
        .result-box { max-width: 850px; margin: 0 auto; background: white; padding: 40px; border: 1px solid #ddd; position: relative; }
        
        .header-container { display: flex; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .logo-box { width: 120px; flex-shrink: 0; text-align: center; }
        .logo-box img { width: 100px; height: auto; }
        .text-box { flex-grow: 1; text-align: center; }
        .text-box h1 { color: #003366; margin: 0; font-size: 26px; font-weight: 800; text-transform: uppercase; }
        
        .student-info { display: flex; justify-content: space-between; background: #f9f9f9; padding: 15px; border: 1px solid #eee; font-size: 14px; }
        .summary-box { display: flex; justify-content: space-between; background: #e3f2fd; padding: 10px; margin-top: 10px; border: 1px solid #bbdefb; font-weight: bold; font-size: 14px; }
        
        .table-responsive { width: 100%; overflow-x: auto; }
        .table-result { width: 100%; border-collapse: collapse; margin-top: 20px; min-width: 600px; }
        .table-result th, .table-result td { border: 1px solid #333; padding: 8px; text-align: center; font-size: 14px; }
        .table-result th { background: #003366; color: white; }
        
        /* Updated Remarks Section */
        .remarks-section { margin-top: 20px; padding: 10px; border: 1px solid #ccc; font-size: 14px; }
        
        /* Individual Remark Blocks */
        .remark-block { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
        .remark-block:last-child { border-bottom: none; margin-bottom: 0; }
        
        /* Signatures Section - Centered */
        .signatures { 
            margin-top: 40px; 
            display: flex; 
            justify-content: center; /* CENTERED */
            align-items: center;
        }
        .sig-box { text-align: center; width: 60%; }
        
        .next-term-box { text-align: center; margin-top: 30px; padding: 10px; border-top: 2px double #ccc; font-weight: bold; color: #003366; }
        
        @media only screen and (max-width: 600px) {
            .result-box { padding: 15px; margin: 0; width: 100%; box-sizing: border-box; }
            .header-container { flex-direction: column; text-align: center; }
            .logo-box { margin-bottom: 10px; }
            .text-box h1 { font-size: 20px; }
            .student-info { flex-direction: column; gap: 10px; }
            .student-info div { text-align: left !important; }
            .summary-box { flex-direction: column; gap: 5px; text-align: center; }
            .sig-box { width: 100%; }
            .print-btn { width: 100% !important; }
        }
        @media print { 
            .print-btn, .back-link { display: none; } 
            body { background: white; margin: 0; padding: 0; } 
            .result-box { border: none; box-shadow: none; margin: 0; padding: 0; width: 100%; max-width: 100%; } 
            .table-responsive { overflow: visible; }
        }
    </style>
</head>
<body>
    <div class="result-box">
        <div class="back-link" style="margin-bottom: 15px;">
            <a href="dashboard.php" style="color: blue; text-decoration: none;">&larr; Back</a>
        </div>

        <?php if(!$is_approved && $my_role == 'admin'): ?>
            <div style="background:#fff3cd; color:#856404; padding:10px; border:1px solid #ffeeba; text-align:center; margin-bottom:15px; font-weight:bold;">
                <i class="fas fa-eye"></i> PREVIEW MODE: Not approved yet.
            </div>
        <?php endif; ?>

        <div class="header-container">
            <div class="logo-box"><img src="../assets/images/logo.png" alt="Logo"></div>
            <div class="text-box">
                <h1><?php echo $school_name; ?></h1>
                <p style="margin:5px; font-size:13px;"><?php echo $school_addr; ?></p>
                <p style="font-weight:bold; color:#d9534f; margin:5px; font-size:13px;">"Motto: <?php echo $school_motto; ?>"</p>
                <?php if($school_phone): ?><p style="font-size: 12px; margin: 2px;">Tel: <?php echo $school_phone; ?></p><?php endif; ?>
                <h3 style="margin-top: 10px; text-decoration: underline; font-size:18px;">STUDENT REPORT SHEET</h3>
            </div>
        </div>

        <div class="student-info">
            <div style="text-align: left;">
                <p><strong>Name:</strong> <?php echo strtoupper($student['full_name']); ?></p>
                <p><strong>Admission No:</strong> <?php echo strtoupper($student['admission_no']); ?></p>
                <p><strong>Class:</strong> <?php echo strtoupper($student['class_name'] ?? 'N/A'); ?></p>
            </div>
            <div style="text-align: right;">
                <p><strong>Term:</strong> <?php echo $display_term_name; ?></p>
                <p><strong>Session:</strong> <?php echo $current_session; ?></p>
                <p><strong>No. of students in Class:</strong> <?php echo $total_students_in_class; ?></p>
            </div>
        </div>

        <div class="summary-box">
            <span>Subjects: <?php echo $subject_count; ?></span>
            <span>Total: <?php echo $grand_total; ?></span>
            <span>Average: <?php echo $average; ?></span>
            <span style="color: #003366;">Position: <?php echo $position_string; ?></span>
        </div>

        <div class="table-responsive">
            <table class="table-result">
                <thead>
                    <tr>
                        <th style="text-align: left;">Subject</th>
                        <th>1st CA (20)</th>
                        <th>2nd CA (20)</th>
                        <th>Exam (60)</th>
                        <th>Total (100)</th>
                        <th>Grade</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($result_rows as $row): 
                        $ca1 = intval($row['ca1_score']); $ca2 = intval($row['ca2_score']); $exam = intval($row['exam_score']); $total = intval($row['total_score']);
                        $g = "F"; $rem = "Fail";
                        if($total >= 70) { $g = "A"; $rem = "Excellent"; } elseif($total >= 60) { $g = "B"; $rem = "V. Good"; } elseif($total >= 50) { $g = "C"; $rem = "Good"; } elseif($total >= 45) { $g = "D"; $rem = "Pass"; } elseif($total >= 40) { $g = "E"; $rem = "Fair"; }
                    ?>
                    <tr>
                        <td style="text-align: left; font-weight: bold;"><?php echo $row['subject_name']; ?></td>
                        <td><?php echo $ca1; ?></td> <td><?php echo $ca2; ?></td> <td><?php echo $exam; ?></td>
                        <td style="font-weight: bold; background:#f0f0f0;"><?php echo $total; ?></td>
                        <td style="color:<?php echo ($g=='F')?'red':'green'; ?>; font-weight:bold;"><?php echo $g; ?></td>
                        <td style="font-size:12px;"><?php echo $rem; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="remarks-section">
            <div class="remark-block">
                <p><strong>Class Teacher's Name:</strong>    <?php echo $ct_name; ?> </p>
                <p><strong>Class Teacher's Remark:</strong> <?php echo $teacher_remark; ?></p>
                   <p><strong>Principal's Remark:</strong> <?php echo $principal_remark; ?></p>
                <p style="margin-top: 5px; color: #333; font-weight: bold; text-align: left;">
                   </div> 
            </div>
         

        <div class="signatures">
            <div class="sig-box">
                <?php if(!empty($principal_sig_file) && file_exists("../assets/uploads/".$principal_sig_file)): ?>
                    <img src="../assets/uploads/<?php echo $principal_sig_file; ?>" style="width: 100px; height: auto; display:block; margin:0 auto;">
                <?php else: ?>
                    <div style="height: 10px;"></div> 
                <?php endif; ?>
                
                <b>  Principal's Signature:</b>
                
            </div>
        </div>

        

        <button onclick="window.print()" class="print-btn" style="margin-top:20px; padding:12px; background:#333; color:white; width:100%; border:none; border-radius:5px; font-size:16px; cursor:pointer;">
            Print Result
        </button>

    </div>
</body>
</html>