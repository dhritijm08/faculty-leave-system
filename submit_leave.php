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

$leave_type = trim($_POST['leave_type']);
$date_from = trim($_POST['date_from']);
$date_to = trim($_POST['date_to']);
$reason = trim($_POST['reason']);

// Validate dates
$today = date('Y-m-d');
if ($date_from < $today) {
    echo "Error: From Date cannot be in the past.";
    exit();
}

if ($date_to < $today) {
    echo "Error: To Date cannot be in the past.";
    exit();
}

if ($date_to < $date_from) {
    echo "Error: To Date cannot be before From Date.";
    exit();
}

// Calculate number of leave days
$start = new DateTime($date_from);
$end = new DateTime($date_to);
$end->modify('+1 day'); // Include the end date
$interval = $start->diff($end);
$days = $interval->days;

// Determine hierarchical workflow based on role
$forwarded_to = '';
$hod_status = 'pending';
$dean_status = 'pending';
$principal_status = 'pending';
$status = 'pending';

switch ($role) {
    case 'faculty':
        // Faculty: HOD → Dean → Principal
        $forwarded_to = 'hod,dean,principal';
        // All statuses remain pending
        break;
        
    case 'hod':
        // HOD applying for own leave: Skip HOD approval, go to Dean → Principal
        $forwarded_to = 'dean,principal';
        $hod_status = 'approved'; // HOD auto-approves their own leave
        $dean_status = 'pending';
        $principal_status = 'pending';
        break;
        
    case 'dean':
        // Dean applying for own leave: Skip HOD and Dean approval, go to Principal only
        $forwarded_to = 'principal';
        $hod_status = 'approved'; // Auto-approved at HOD level
        $dean_status = 'approved'; // Auto-approved at Dean level
        $principal_status = 'pending';
        break;
        
    case 'principal':
        // Principal applying for own leave: All approvals auto-approved
        $forwarded_to = 'completed';
        $hod_status = 'approved';
        $dean_status = 'approved';
        $principal_status = 'approved';
        $status = 'approved'; // Overall status is approved
        break;
}

// Set remarks based on auto-approvals
$hod_remarks = ($hod_status === 'approved') ? 'Auto-approved (self-request)' : '';
$dean_remarks = ($dean_status === 'approved') ? 'Auto-approved (self-request)' : '';
$principal_remarks = ($principal_status === 'approved') ? 'Auto-approved (self-request)' : '';

// Insert leave request with hierarchical approval tracking
$query = "INSERT INTO leave_requests 
    (faculty_id, leave_type, date_from, date_to, days, reason, department, 
     forwarded_to, status, hod_status, dean_status, principal_status, 
     hod_remarks, dean_remarks, principal_remarks) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("isssissssssssss", 
    $faculty_id, 
    $leave_type, 
    $date_from, 
    $date_to, 
    $days, 
    $reason, 
    $department, 
    $forwarded_to, 
    $status, 
    $hod_status,
    $dean_status,
    $principal_status,
    $hod_remarks,
    $dean_remarks,
    $principal_remarks
);

if ($stmt->execute()) {
    echo "Leave request submitted successfully!";
} else {
    echo "Error submitting leave request: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>