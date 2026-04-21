<?php
// config/functions.php

// Sanitize inputs
function clean_input($data) {
    global $conn;
    return htmlspecialchars(stripslashes(trim($conn->real_escape_string($data))));
}

// Check Login
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

// Grading Logic for SALLDANS ROYAL ACADEMY
function calculate_grade($score) {
    if ($score >= 70) return 'A';
    if ($score >= 60) return 'B';
    if ($score >= 50) return 'C';
    if ($score >= 45) return 'D';
    return 'F';
}

function get_remark($score) {
    if ($score >= 70) return 'EXCELLENT';
    if ($score >= 60) return 'GOOD';
    if ($score >= 50) return 'CREDIT';
    if ($score >= 45) return 'PASS';
    return 'FAIL';
}
?>