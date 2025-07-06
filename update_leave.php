<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit();
}

if (!isset($_POST['leave_id']) || !isset($_POST['action'])) {
    echo json_encode(["success" => false, "message" => "Missing leave ID or action"]);
    exit();
}

include 'db_connect.php';

$leave_id = intval($_POST['leave_id']);
$action = $_POST['action'];

$status = '';
$column = '';
$forward_to = null;

// Decide what to update based on the action
switch ($action) {
    case 'hod_approved':
        $status = 'approved';
        $column = 'hod_status';
        $forward_to = 'dean';
        break;

    case 'hod_rejected':
        $status = 'rejected';
        $column = 'hod_status';
        break;

    case 'dean_approved':
        $status = 'approved';
        $column = 'dean_status';
        $forward_to = 'principal';
        break;

    case 'dean_rejected':
        $status = 'rejected';
        $column = 'dean_status';
        break;

    case 'principal_approved':
        $status = 'approved';
        $column = 'status'; // Final decision
        break;

    case 'principal_rejected':
        $status = 'rejected';
        $column = 'status'; // Final decision
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        exit();
}

if ($forward_to) {
    $stmt = $conn->prepare("UPDATE leave_requests SET $column = ?, forwarded_to = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $forward_to, $leave_id);
} else {
    $stmt = $conn->prepare("UPDATE leave_requests SET $column = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $leave_id);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
