<?php
include 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$query = "SELECT u.username, lr.leave_type, lr.date_from, lr.date_to, lr.reason, lr.status 
          FROM leave_requests lr
          JOIN users u ON lr.faculty_id = u.id
          WHERE lr.status IN ('approved', 'rejected')
          ORDER BY lr.date_from DESC";

$result = $conn->query($query);
?>

<?php if ($result->num_rows > 0): ?>
    <table>
        <tr><th>Name</th><th>Type</th><th>From</th><th>To</th><th>Reason</th><th>Status</th></tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php 
                $statusColor = ($row['status'] === 'approved') ? 'green' : (($row['status'] === 'rejected') ? 'red' : 'black');
            ?>
            <tr>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['leave_type']) ?></td>
                <td><?= htmlspecialchars($row['date_from']) ?></td>
                <td><?= htmlspecialchars($row['date_to']) ?></td>
                <td><?= htmlspecialchars($row['reason']) ?></td>
                <td style="color: <?= $statusColor ?>; font-weight: bold;">
                    <?= ucfirst($row['status']) ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>No approved or rejected leave history available.</p>
<?php endif; ?>
