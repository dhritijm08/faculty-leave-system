<?php
include 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

if ($role === 'faculty') {
    $user_id = $_SESSION['id'];

    $query = "SELECT lr.id, u.username, lr.leave_type, lr.date_from, lr.date_to, lr.reason, lr.status 
              FROM leave_requests lr
              JOIN users u ON lr.faculty_id = u.id
              WHERE lr.faculty_id = ? AND lr.status IN ('approved', 'rejected')";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
} 
elseif ($role === 'hod') {
    $user_id = $_SESSION['id'];

    $query = "SELECT lr.id, u.username, lr.leave_type, lr.date_from, lr.date_to, lr.reason, lr.hod_status 
              FROM leave_requests lr
              JOIN users u ON lr.faculty_id = u.id
              WHERE lr.hod_id = ? AND lr.hod_status IN ('approved', 'rejected')";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
} 
elseif ($role === 'dean') {
    $query = "SELECT lr.id, u.username, lr.leave_type, lr.date_from, lr.date_to, lr.reason, lr.dean_status 
              FROM leave_requests lr
              JOIN users u ON lr.faculty_id = u.id
              WHERE lr.forwarded_to = 'principal' AND lr.dean_status IN ('approved', 'rejected')";

    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!-- LEAVE HISTORY UI -->
<div class="tab-content" id="leaveHistoryTab">
    <h3>Leave History</h3>

    <?php if ($result->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Faculty Name</th>
                    <th>Leave Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
                        if ($role === 'dean') {
                            $status = $row['dean_status'];
                        } elseif ($role === 'hod') {
                            $status = $row['hod_status'];
                        } else {
                            $status = $row['status'];
                        }

                        // ✅ Fixing the color logic
                        $statusColor = ($status === 'approved') ? 'green' : (($status === 'rejected') ? 'red' : 'black');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['leave_type']) ?></td>
                        <td><?= htmlspecialchars($row['date_from']) ?></td>
                        <td><?= ($row['date_to'] === '0000-00-00' || empty($row['date_to'])) ? 'N/A' : htmlspecialchars($row['date_to']) ?></td>
                        <td><?= htmlspecialchars($row['reason']) ?></td>
                        <td style="color: <?= $statusColor ?>; font-weight: bold;">
                            <?= ucfirst($status) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No approved or rejected leave history.</p>
    <?php endif; ?>
</div>

<?php
$stmt->close();
$conn->close();
?>
