<?php
session_start();
include 'db_connect.php';

// Ensure HOD access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'hod') {
    echo "Unauthorized access";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_id = intval($_POST['leave_id']);
    $action = ($_POST['action'] === 'approve') ? 'approved' : 'rejected';

    // Update leave status in database
    $stmt = $conn->prepare("UPDATE leave_requests SET hod_status = ? WHERE id = ?");
    $stmt->bind_param("si", $action, $leave_id);

    if ($stmt->execute()) {
        echo "Leave request has been $action successfully.";
    } else {
        echo "Failed to update leave status.";
    }
    $stmt->close();
    $conn->close();
}
?>
