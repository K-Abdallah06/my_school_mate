<?php
// admin/manage_settings.php
session_start();
include '../config/db_connect.php';
include '../config/functions.php';

// Check Admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit();
}

$msg = "";
$sig_msg = ""; 

// 1. SAVE GENERAL SETTINGS
if(isset($_POST['save_settings'])) {
    $name = clean_input($_POST['school_name']);
    $addr = clean_input($_POST['school_address']);
    $motto = clean_input($_POST['school_motto']);
    $phone = clean_input($_POST['school_phone']);
    $next_term = clean_input($_POST['next_term_begins']);

    $up_q = "UPDATE school_settings SET 
             school_name='$name', 
             school_address='$addr', 
             school_motto='$motto', 
             school_phone='$phone', 
             next_term_begins='$next_term' 
             WHERE id=1";
             
    if($conn->query($up_q)) {
        $msg = "<div class='alert-success'>Settings Updated Successfully!</div>";
    } else {
        $msg = "<div class='alert-danger'>Error: " . $conn->error . "</div>";
    }
}

// 2. HANDLE SIGNATURE UPLOAD
if(isset($_POST['update_signature'])) {
    $target_dir = "../assets/uploads/";
    
    // Create folder if not exists
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_name = time() . "_" . basename($_FILES["signature_image"]["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate file type
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        $sig_msg = "<div class='alert-danger'>Sorry, only JPG, JPEG & PNG files are allowed.</div>";
    } else {
        // Upload file
        if(move_uploaded_file($_FILES["signature_image"]["tmp_name"], $target_file)) {
            // Save filename to DB
            $conn->query("UPDATE school_settings SET principal_signature='$file_name' WHERE id=1");
            $sig_msg = "<div class='alert-success'>Signature uploaded successfully!</div>";
        } else {
            $sig_msg = "<div class='alert-danger'>Failed to upload image.</div>";
        }
    }
}

// 3. FETCH DATA
$q = $conn->query("SELECT * FROM school_settings WHERE id=1");
if($q->num_rows == 0) {
    $conn->query("INSERT INTO school_settings (id) VALUES (1)");
    $q = $conn->query("SELECT * FROM school_settings WHERE id=1");
}
$s = $q->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Settings</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Force body background to simple grey */
        body { background: #f4f4f4; margin: 0; padding: 20px; }

        /* Use a custom container class to avoid conflicts with .wrapper */
        .settings-container {
            max-width: 600px;
            margin: 0 auto;       /* Centers the container */
            display: block;       /* Forces block layout (prevents side-by-side) */
        }

        /* Styling for the white cards */
        .section-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #ddd;
            width: 100%;          /* Forces full width */
            box-sizing: border-box; /* Ensures padding doesn't break width */
            margin-bottom: 30px;  /* Adds space between the two boxes */
            display: block;       /* Ensures it sits on its own line */
        }

        /* Form Styles */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        
        .btn-primary { width: 100%; padding: 12px; font-size: 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-upload { width: 100%; padding: 12px; font-size: 16px; background: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary:hover { background: #218838; }
        .btn-upload:hover { background: #218838; }

        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        h3 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 0; color: #003366; }
        
        .back-link { display: block; margin-bottom: 20px; color: #003366; font-weight: bold; text-decoration: none; }
   
        body { 
            background: #faf5dd; 
            margin: 0; 
            padding: 20px; 
            font-family: sans-serif;
        }
   </style>
</head>
<body>

    <div class="settings-container">
        <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
        <h2>System Settings</h2>
        
        <div class="section-card">
            <h3>General School Info</h3>
            <?php echo $msg; ?>

            <form method="POST">
                <div class="form-group">
                    <label>School Name</label>
                    <input type="text" name="school_name" value="<?php echo $s['school_name']; ?>" required>
                </div>

                <div class="form-group">
                    <label>School Address</label>
                    <input type="text" name="school_address" value="<?php echo $s['school_address']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Motto</label>
                    <input type="text" name="school_motto" value="<?php echo $s['school_motto']; ?>">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="school_phone" value="<?php echo $s['school_phone']; ?>">
                </div>

                <div class="form-group">
                    <label>Next Term Begins</label>
                    <input type="text" name="next_term_begins" value="<?php echo $s['next_term_begins']; ?>" placeholder="e.g. 20th January, 2025">
                </div>

                <button type="submit" name="save_settings" class="btn-primary">Update Info</button>
            </form>
        </div>

        <div class="section-card">
            <h3>Upload Principal's Signature</h3>
            <?php echo $sig_msg; ?>
            
            <form method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Current Signature:</label>
                    <?php if(!empty($s['principal_signature']) && file_exists("../assets/uploads/" . $s['principal_signature'])): ?>
                        <div style="background: #f9f9f9; padding: 10px; border: 1px dashed #ccc; text-align: center;">
                            <img src="../assets/uploads/<?php echo $s['principal_signature']; ?>" style="max-height: 80px; display: inline-block;">
                        </div>
                    <?php else: ?>
                        <div style="background: #f9f9f9; padding: 15px; border: 1px dashed #ccc; text-align: center; color: #999;">
                            No signature uploaded yet.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Select New Image (PNG/JPG):</label>
                    <input type="file" name="signature_image" required accept="image/png, image/jpeg">
                    <small style="display:block; margin-top:5px; color:#666;">Recommended: White or Transparent background.</small>
                </div>

                <button type="submit" name="update_signature" class="btn-upload">Upload Signature</button>
            </form>
        </div>

    </div>

</body>
</html>