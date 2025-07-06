<?php
session_start();
include 'db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    die("Error: User not logged in. Please log in again.");
}

$username = $_SESSION['username'];
$role = $_SESSION['role']; // 'faculty', 'hod', 'dean', or 'principal'

// Fetch user ID and department
$stmt = $conn->prepare("SELECT id, department FROM users WHERE username = ?");
if (!$stmt) {
    die("Error in query: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($faculty_id, $department);
$stmt->fetch();
$stmt->close();

// Check if required fields are set
if (!isset($_POST['leave_type'], $_POST['date_from'], $_POST['date_to'], $_POST['reason'])) {
    die("Error: Missing required fields.");
}

$leave_type = $_POST['leave_type'];
$date_from = $_POST['date_from'];
$date_to = $_POST['date_to'];
$reason = $_POST['reason'];

// Calculate number of leave days
$days = (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24) + 1;

// Determine whom to forward the leave request to and the default status
$forwarded_to = '';
$status = 'pending';

if ($role === 'faculty') {
    $forwarded_to = 'hod,dean,principal';
} elseif ($role === 'hod') {
    $forwarded_to = 'dean,principal';
} elseif ($role === 'dean') {
    $forwarded_to = 'principal';
} elseif ($role === 'principal') {
    // Auto-approve for principal
    $forwarded_to = '';
    $status = 'approved';
} else {
    die("Error: Invalid role.");
}

// Insert leave request
$query = "INSERT INTO leave_requests (faculty_id, role, leave_type, date_from, date_to, days, reason, forwarded_to, status, department) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("issssissss", $faculty_id, $role, $leave_type, $date_from, $date_to, $days, $reason, $forwarded_to, $status, $department);

if ($stmt->execute()) {
    echo "Leave request submitted successfully.";
} else {
    echo "Error submitting leave request: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
