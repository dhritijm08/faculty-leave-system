<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please log in again.");
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $leave_type = $_POST['leave_type'];
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    $reason = $_POST['reason'];

    if (empty($leave_type) || empty($date_from) || empty($date_to) || empty($reason)) {
        die("Error: Missing required fields.");
    }

    // Determine who the leave should be forwarded to
    if ($role == 'faculty') {
        $forward_to = 'hod'; // Faculty requests go to HOD
    } elseif ($role == 'hod') {
        $forward_to = 'dean'; // HOD requests go to Dean
    } else {
        $forward_to = 'principal'; // Dean requests go to Principal
    }

    // Insert leave request
    $stmt = $conn->prepare("INSERT INTO leave_requests (faculty_id, leave_type, date_from, date_to, reason, status, forwarded_to) 
                            VALUES (?, ?, ?, ?, ?, 'pending', ?)");
    $stmt->bind_param("isssss", $user_id, $leave_type, $date_from, $date_to, $reason, $forward_to);

    if ($stmt->execute()) {
        echo "Leave request submitted successfully!";
    } else {
        echo "Error submitting leave request: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
