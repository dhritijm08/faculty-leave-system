<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dean') {
    die("Access denied.");
}

if (isset($_POST['leave_id'])) {
    $leave_id = $_POST['leave_id'];
    $status = $_POST['status']; // 'approved' or 'rejected'

    if ($status === 'approved') {
        $query = "UPDATE leave_requests SET status = 'approved', forwarded_to = 'principal' WHERE id = ?";
    } else {
        $query = "UPDATE leave_requests SET status = 'rejected', forwarded_to = 'none' WHERE id = ?";
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $leave_id);

    if ($stmt->execute()) {
        echo "✅ Leave request updated successfully.";
    } else {
        echo "❌ Error updating leave request: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
