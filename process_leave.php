<?php
session_start();
include 'db_connect.php';

// Ensure the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Capture and validate form fields
    $faculty_id = isset($_POST['faculty_id']) ? trim($_POST['faculty_id']) : '';
    $leave_type = isset($_POST['leave_type']) ? trim($_POST['leave_type']) : '';
    $date_from = isset($_POST['date_from']) ? trim($_POST['date_from']) : '';
    $date_to = isset($_POST['date_to']) ? trim($_POST['date_to']) : '';
    $days = isset($_POST['days']) ? intval($_POST['days']) : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    // Check if all required fields are filled
    if (empty($faculty_id) || empty($leave_type) || empty($date_from) || empty($date_to) || empty($days) || empty($reason)) {
        die("❌ All fields are required.");
    }

    // Check database connection
    if (!$conn) {
        die("❌ Database connection failed: " . mysqli_connect_error());
    }

    // Verify faculty_id exists in the users table
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        die("❌ Invalid Faculty ID.");
    }
    $stmt->close();

    // Insert the leave request into the database
    $stmt = $conn->prepare("INSERT INTO leave_requests (faculty_id, leave_type, date_from, date_to, days, reason, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    if (!$stmt) {
        die("❌ Error preparing query: " . $conn->error);
    }

    $stmt->bind_param("isssis", $faculty_id, $leave_type, $date_from, $date_to, $days, $reason);

    // Execute the query
    if ($stmt->execute()) {
        echo "✅ Leave request submitted successfully!";
    } else {
        echo "❌ Error submitting leave: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
