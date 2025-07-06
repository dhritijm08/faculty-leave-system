<?php
$servername = "localhost";  // Change if using a remote database
$username = "root";         // Your database username
$password = "";             // Your database password (leave blank for XAMPP)
$dbname = "staff_leave_management";  // Database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
