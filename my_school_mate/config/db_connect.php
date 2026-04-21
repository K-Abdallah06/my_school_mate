<?php
// config/db_connect.php

$servername = "localhost";
$username = "root";      // Default XAMPP/WAMP username
$password = "";          // Default XAMPP/WAMP password (leave empty)
$dbname = "my_school_mate_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start Session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>