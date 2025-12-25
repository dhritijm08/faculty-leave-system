<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch principal details
$stmt = $conn->prepare("SELECT id, name, username, role, department, photo, email, phone FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($id, $name, $username, $role, $department, $photo, $email, $phone);
$stmt->fetch();
$stmt->close();

// Default to leave-requests
$tab = $_GET['tab'] ?? 'leave-requests';
$active_sub_tab = $_GET['sub_tab'] ?? 'faculty-requests';

// Handle approvals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = (int) $_POST['leave_id'];
    $action = $_POST['action'];
    $current_sub_tab = $_POST['active_sub_tab'] ?? 'faculty-requests';
    $remarks = $_POST['remarks'] ?? '';

    if ($action === 'approve') {
        $status = 'approved';
        $stmt = $conn->prepare("UPDATE leave_requests SET principal_status = ?, principal_remarks = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $remarks, $status, $leave_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $stmt = $conn->prepare("UPDATE leave_requests SET principal_status = ?, principal_remarks = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $remarks, $status, $leave_id);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect back to same tab
    header("Location: principal_home.php?tab=leave-requests&sub_tab=" . $current_sub_tab);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Principal Dashboard</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body {
      display: flex;
      min-height: 100vh;
      background-color: #eaeded;
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      flex-direction: column;
    }

    .sidebar {
      background-color: #232f3e;
      color: white;
      width: 240px;
      padding: 20px 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: fixed;
      height: 100%;
      overflow-y: auto;
      z-index: 1000;
      transition: transform 0.3s ease;
    }

    .sidebar-toggle {
      display: none;
      position: fixed;
      top: 15px;
      left: 15px;
      background: #232f3e;
      color: white;
      border: none;
      font-size: 24px;
      z-index: 1100;
      padding: 5px 10px;
      border-radius: 4px;
    }

    .sidebar img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin-bottom: 10px;
      border: 2px solid #febd69;
    }

    .sidebar ul {
      width: 100%;
      padding: 0;
    }

    .sidebar ul li {
      padding: 12px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 15px;
      border: 1px solid transparent;
      border-radius: 4px;
      margin-bottom: 4px;
    }

    .sidebar ul li a {
      color: white;
      text-decoration: none;
    }

    .sidebar ul li a i {
      margin-right: 8px;
    }

    .sidebar ul li i {
      min-width: 20px;
    }

    .sidebar ul li:hover {
      border: 1px solid white;
    }

    .sidebar ul li:hover a {
      color: white;
    }

    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      margin-left: 240px;
      transition: margin-left 0.3s ease;
    }

    .topbar {
      background-color: #131921;
      padding: 15px 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      color: white;
      position: sticky;
      top: 0;
      z-index: 900;
    }

    .topbar h1 {
      font-size: 20px;
      margin: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .topbar img {
      border-radius: 50%;
    }

    .content {
      flex: 1;
      padding: 20px 20px 20px 40px;
      display: flex;
      gap: 20px;
      background-color: #eaeded;
    }

    .main-panel {
      flex: 1;
      background-color: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      overflow-y: auto;
    }

    .section {
      display: none;
    }

    .section.active {
      display: block;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      text-align: left;
      padding: 10px;
      border-bottom: 1px solid #ddd;
      vertical-align: top;
    }

    th {
      background-color: #232f3e;
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f2f2f2;
    }

    tr:hover {
      background-color: #e6e6e6;
    }

    .form-header {
      background-color: #232f3e; 
      padding: 15px;
      margin-bottom: 20px;
      color: white;
      border-radius: 6px 6px 0 0;
    }
    
    .form-header h2 {
      margin: 0; 
      color: white;
    }
    
    .form-container {
      background: white; 
      padding: 20px; 
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border-radius: 0 0 6px 6px;
      border: 1px solid #ddd;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
      color: #131921;
    }
    
    .form-control {
      width: 98.5%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 6px;
      background-color: #eaeded;
    }
    
    textarea.form-control {
      min-height: 100px;
    }
    
    .submit-btn {
      background-color: #FFD700;
      color: #131921;
      padding: 10px 25px;
      font-weight: bold;
      border: none;
      border-radius: 5px;
      cursor: pointer;     
      box-shadow: 0 1px 0 rgba(255,255,255,.4) inset;
    }

    .submit-btn:hover {
      background-color: #FFC107;
      box-shadow: 0 1px 3px rgba(0,0,0,.15);
    }

    .profile-photo {
      text-align: center;
      margin-bottom: 15px; 
    }

    .profile-photo img {
      width: 110px; 
      height: 110px; 
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #febd69; 
    }

    .profile-row {
      text-align: center;
    }

    .profile-field {
      background-color: white;
      padding: 15px; 
      border-radius: 6px;
      box-shadow: 0 0px 8px rgba(0, 0, 0, 0.08), 
            0 -1px 8px rgba(0, 0, 0, 0.08);
      margin-bottom: 10px; 
    }

    .profile-field label {
      display: block;
      padding: 8px; 
      background-color: #eaeded;
      border-radius: 4px;
      text-align: center;
      font-weight: normal;
      color: #131921;
      font-size: 14px; 
      margin-bottom: 5px;
    }

    .profile-field label strong {
      color: #007185;
      font-weight: bold;
      font-size: 13px; 
    }

    /* Request Tabs */
    .request-tabs {
      display: flex;
      margin-bottom: 20px;
      border-bottom: 1px solid #ddd;
    }
    
    .request-tab {
      padding: 10px 20px;
      cursor: pointer;
      background: #f1f1f1;
      margin-right: 5px;
      border-radius: 5px 5px 0 0;
    }
    
    .request-tab.active {
      background: #232f3e;
      color: white;
    }
    
    .request-content {
      display: none;
    }
    
    .request-content.active {
      display: block;
    }

    /* Inline remarks textbox */
    .remarks-textbox {
        width: 80%;
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 13px;
        resize: vertical;
        min-height: 60px;
        max-height: 100px;
    }

    .remarks-textbox:focus {
        outline: none;
        border-color: #000;
        border-width: 1.75px;
    }

    .compact-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .compact-btn {
        padding: 6px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        font-weight: bold;
        width: 100%;
        text-align: center;
    }

    .compact-approve {
        background-color: #28a745;
        color: white;
    }

    .compact-reject {
        background-color: #dc3545;
        color: white;
    }

    .compact-btn:hover {
        opacity: 0.9;
    }

    /* Already processed status */
    .processed-status {
        color: #6c757d;
        font-weight: normal;
        font-style: italic;
    }

    /* Status column styling */
    .status-column {
        line-height: 1.6;
    }

    .approval-status {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 11px;
      font-weight: bold;
      margin-right: 5px;
    }

    .approval-status.pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .approval-status.approved {
      background-color: #d4edda;
      color: #155724;
    }

    .approval-status.rejected {
      background-color: #f8d7da;
      color: #721c24;
    }

    /* Approval Hierarchy Styling */
    .approval-hierarchy {
      font-size: 12px;
      line-height: 1.6;
    }

    .approval-item {
      margin-bottom: 8px;
      padding: 6px;
      border-radius: 4px;
      background-color: #f8f9fa;
    }

    .approval-item:last-child {
      margin-bottom: 0;
    }

    .approval-authority {
      font-weight: bold;
      color: #131921;
      display: inline;
      margin-bottom: 0;
      margin-right: 5px;
    }

    .approval-header {
      display: flex;
      align-items: center;
      gap: 5px;
      margin-bottom: 3px;
    }

    .approval-remarks {
      font-style: italic;
      color: #666;
      margin-top: 3px;
      font-size: 11px;
      padding-left: 0;
      border-left: none;
    }

    .approval-remarks strong {
      color: #131921;
      font-weight: bold;
      font-size: 11px;
      margin-right: 3px;
    }

    /* Mobile Responsiveness */
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
      }

      .sidebar-toggle {
        display: block;
      }

      .topbar {
        padding: 15px;
      }

      .topbar h1 {
        font-size: 16px;
        margin-left: 40px;
      }

      .content {
        padding: 15px;
      }

      .main-panel {
        padding: 15px;
      }

      table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
      }

      .profile-field label {
        font-size: 13px;
      }
      
      th, td {
        padding: 8px 5px;
        font-size: 14px;
      }
    }

    @media (max-width: 768px) {
      .topbar h1 {
        display: none;
      }

      .topbar > div:first-child {
        flex: 1;
      }

      .form-control {
        width: 97.5%;
      }

      .request-tab { 
        padding: 8px 12px; 
        font-size: 14px;
      }

      .compact-actions {
        flex-direction: row;
      }
      .compact-btn {
        padding: 6px 8px;
        font-size: 12px;
      }
    }

    @media (max-width: 576px) {
      .form-header h2 {
        font-size: 18px;
      }

      .profile-photo img {
        width: 90px;
        height: 90px;
      }
    }
  </style>
</head>

<body>
  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
  </button>

  <div class="sidebar" id="sidebar">
    <h2 style="color: #febd69;">Principal Dashboard</h2>
    <img src="<?= htmlspecialchars($photo); ?>" alt="Principal Photo" />
    <ul>
      <li data-section="profile"><i class="fas fa-user"></i> Profile</li>
      <li data-section="leave-requests"><i class="fas fa-inbox"></i> Leave Requests</li>
      <li><a href="logout.php"><i class="fas fa-power-off"></i> Logout</a></li>
    </ul>
  </div>

  <div class="main-content">
    <div class="topbar">
      <div style="display: flex; align-items: center; gap: 15px;">
        <img src="facultyleave.png" alt="Faculty Logo" style="border-radius: 50%; width: 70px;" />
        <h1>FACULTY LEAVE SYSTEM</h1>
      </div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <span><?= htmlspecialchars($username); ?></span>
        <img src="<?= htmlspecialchars($photo); ?>" alt="User" style="width: 30px; height: 30px; border: 1px solid #febd69;" />
      </div>
    </div>

    <div class="content">
      <div class="main-panel">
        <!-- Profile Section -->
        <section id="profile" class="section <?= $tab == 'profile' ? 'active' : '' ?>">
          <div class="form-header">
            <h2>PRINCIPAL PROFILE</h2>
          </div>

          <div class="form-container">
            <div class="profile-photo">
              <img src="<?= htmlspecialchars($photo); ?>" alt="Principal Photo">
            </div>

            <div class="profile-row">
              <div class="profile-field">
                <div class="form-group" style="margin: 0;">
                  <label><strong>ID:</strong> <?= htmlspecialchars($id); ?></label>
                  <label><strong>Name:</strong> <?= htmlspecialchars($name); ?></label>
                  <label><strong>Username:</strong> <?= htmlspecialchars($username); ?></label>
                  <label><strong>Role:</strong> <?= ucfirst(htmlspecialchars($role)); ?></label>
                  <label><strong>Department:</strong> <?= htmlspecialchars($department); ?></label>
                  <label><strong>Email:</strong> <?= htmlspecialchars($email); ?></label>
                  <label><strong>Phone:</strong> <?= htmlspecialchars($phone); ?></label>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Leave Requests Section -->
        <section id="leave-requests" class="section <?= $tab == 'leave-requests' ? 'active' : '' ?>">
          <div class="form-header"><h2>LEAVE REQUESTS</h2></div>

          <!-- Request Tabs -->
          <div class="request-tabs">
            <div class="request-tab <?= $active_sub_tab == 'faculty-requests' ? 'active' : '' ?>" data-target="faculty-requests">Faculty Requests</div>
            <div class="request-tab <?= $active_sub_tab == 'hod-requests' ? 'active' : '' ?>" data-target="hod-requests">HOD Requests</div>
            <div class="request-tab <?= $active_sub_tab == 'dean-requests' ? 'active' : '' ?>" data-target="dean-requests">Dean Requests</div>
          </div>

          <!-- Faculty Requests -->
          <div id="faculty-requests" class="request-content <?= $active_sub_tab == 'faculty-requests' ? 'active' : '' ?>">
            <?php
            $stmt = $conn->prepare("SELECT lr.id, u.name, u.department, lr.leave_type, lr.date_from, lr.date_to, lr.reason, 
                           lr.status, lr.hod_status, lr.dean_status, lr.principal_status, 
                           lr.hod_remarks, lr.dean_remarks, lr.principal_remarks 
                           FROM leave_requests lr 
                           JOIN users u ON lr.faculty_id = u.id 
                           WHERE lr.principal_status = 'pending' AND u.role = 'faculty'");
            $stmt->execute();
            $result = $stmt->get_result();
            ?>

            <?php if ($result->num_rows > 0): ?>
              <table>
                <tr>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Type</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Actions</th>
                  <th>Remarks</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['leave_type']) ?></td>
                    <td><?= $row['date_from'] ?></td>
                    <td><?= $row['date_to'] ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td class="status-column">
                      <div class="approval-hierarchy">
                        <!-- HOD Status -->
                        <div class="approval-item">
                          <div class="approval-header">
                            <span class="approval-authority">HOD:</span>
                            <span class="approval-status <?= $row['hod_status'] ?>"><?= ucfirst($row['hod_status']) ?></span>
                          </div>
                          <?php if (!empty($row['hod_remarks'])): ?>
                            <div class="approval-remarks"><strong>Remarks:</strong> <?= htmlspecialchars($row['hod_remarks']) ?></div>
                          <?php endif; ?>
                        </div>
                        
                        <!-- Dean Status -->
                        <div class="approval-item">
                          <div class="approval-header">
                            <span class="approval-authority">Dean:</span>
                            <span class="approval-status <?= $row['dean_status'] ?>"><?= ucfirst($row['dean_status']) ?></span>
                          </div>
                          <?php if (!empty($row['dean_remarks'])): ?>
                            <div class="approval-remarks"><strong>Remarks:</strong> <?= htmlspecialchars($row['dean_remarks']) ?></div>
                          <?php endif; ?>
                        </div>
                        
                        <!-- Principal Status -->
                        <div class="approval-item">
                          <div class="approval-header">
                            <span class="approval-authority">Principal:</span>
                            <span class="approval-status pending">Pending</span>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if ($row['principal_status'] === 'pending'): ?>
                        <div class="compact-actions">
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('approve', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="faculty-requests">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="compact-btn compact-approve">Approve</button>
                          </form>
                          
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('reject', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="faculty-requests">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="compact-btn compact-reject">Reject</button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span class="processed-status">
                          <?= ucfirst($row['principal_status']) ?> by Principal
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['principal_status'] === 'pending'): ?>
                        <textarea 
                          name="remarks_<?= $row['id']; ?>" 
                          class="remarks-textbox" 
                          placeholder="Remarks"
                          data-leave-id="<?= $row['id']; ?>"></textarea>
                      <?php else: ?>
                        <?php if (!empty($row['principal_remarks'])): ?>
                          <div style="font-size: 13px; color: #000;">
                            <strong>Your Remarks:</strong><br>
                            <?= htmlspecialchars($row['principal_remarks']) ?>
                          </div>
                        <?php else: ?>
                          <span style="color: #999; font-style: italic;">-</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </table>
            <?php else: ?>
              <div class="form-container">
                <p>No pending faculty requests.</p>
              </div>
            <?php endif; ?>
            <?php $stmt->close(); ?>
          </div>

          <!-- HOD Requests -->
          <div id="hod-requests" class="request-content <?= $active_sub_tab == 'hod-requests' ? 'active' : '' ?>">
            <?php
            $stmt = $conn->prepare("SELECT lr.id, u.name, u.department, lr.leave_type, lr.date_from, lr.date_to, lr.reason, 
                           lr.status, lr.hod_status, lr.dean_status, lr.principal_status, 
                           lr.hod_remarks, lr.dean_remarks, lr.principal_remarks 
                           FROM leave_requests lr 
                           JOIN users u ON lr.faculty_id = u.id 
                           WHERE lr.principal_status = 'pending' AND u.role = 'hod'");
            $stmt->execute();
            $result = $stmt->get_result();
            ?>

            <?php if ($result->num_rows > 0): ?>
              <table>
                <tr>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Type</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Actions</th>
                  <th>Remarks</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['leave_type']) ?></td>
                    <td><?= $row['date_from'] ?></td>
                    <td><?= $row['date_to'] ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td class="status-column">
                      <div class="approval-hierarchy">
                         <!-- Dean Status -->
                        <div class="approval-item">
                          <div class="approval-header">
                            <span class="approval-authority">Dean:</span>
                            <span class="approval-status <?= $row['dean_status'] ?>"><?= ucfirst($row['dean_status']) ?></span>
                          </div>
                          <?php if (!empty($row['dean_remarks'])): ?>
                            <div class="approval-remarks"><strong>Remarks:</strong> <?= htmlspecialchars($row['dean_remarks']) ?></div>
                          <?php endif; ?>
                        </div>
                        
                        <!-- Principal Status -->
                        <div class="approval-item">
                          <div class="approval-header">
                            <span class="approval-authority">Principal:</span>
                            <span class="approval-status pending">Pending</span>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if ($row['principal_status'] === 'pending'): ?>
                        <div class="compact-actions">
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('approve', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="hod-requests">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="compact-btn compact-approve">Approve</button>
                          </form>
                          
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('reject', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="hod-requests">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="compact-btn compact-reject">Reject</button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span class="processed-status">
                          <?= ucfirst($row['principal_status']) ?> by Principal
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['principal_status'] === 'pending'): ?>
                        <textarea 
                          name="remarks_<?= $row['id']; ?>" 
                          class="remarks-textbox" 
                          placeholder="Remarks"
                          data-leave-id="<?= $row['id']; ?>"></textarea>
                      <?php else: ?>
                        <?php if (!empty($row['principal_remarks'])): ?>
                          <div style="font-size: 13px; color: #000;">
                            <strong>Your Remarks:</strong><br>
                            <?= htmlspecialchars($row['principal_remarks']) ?>
                          </div>
                        <?php else: ?>
                          <span style="color: #999; font-style: italic;">-</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </table>
            <?php else: ?>
              <div class="form-container">
                <p>No pending HOD requests.</p>
              </div>
            <?php endif; ?>
            <?php $stmt->close(); ?>
          </div>

          <!-- Dean Requests -->
          <div id="dean-requests" class="request-content <?= $active_sub_tab == 'dean-requests' ? 'active' : '' ?>">
            <?php
            $stmt = $conn->prepare("SELECT lr.id, u.name, u.department, lr.leave_type, lr.date_from, lr.date_to, lr.reason, 
                           lr.status, lr.hod_status, lr.dean_status, lr.principal_status, 
                           lr.hod_remarks, lr.dean_remarks, lr.principal_remarks 
                           FROM leave_requests lr 
                           JOIN users u ON lr.faculty_id = u.id 
                           WHERE lr.principal_status = 'pending' AND u.role = 'dean'");
            $stmt->execute();
            $result = $stmt->get_result();
            ?>

            <?php if ($result->num_rows > 0): ?>
              <table>
                <tr>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Type</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Actions</th>
                  <th>Remarks</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['leave_type']) ?></td>
                    <td><?= $row['date_from'] ?></td>
                    <td><?= $row['date_to'] ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td class="status-column">
                      <span class="approval-status pending">Pending</span>
                    </td>
                    <td>
                      <?php if ($row['principal_status'] === 'pending'): ?>
                        <div class="compact-actions">
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('approve', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="dean-requests">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="compact-btn compact-approve">Approve</button>
                          </form>
                          
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('reject', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="dean-requests">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="compact-btn compact-reject">Reject</button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span class="processed-status">
                          <?= ucfirst($row['principal_status']) ?> by Principal
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['principal_status'] === 'pending'): ?>
                        <textarea 
                          name="remarks_<?= $row['id']; ?>" 
                          class="remarks-textbox" 
                          placeholder="Remarks"
                          data-leave-id="<?= $row['id']; ?>"></textarea>
                      <?php else: ?>
                        <?php if (!empty($row['principal_remarks'])): ?>
                          <div style="font-size: 13px; color: #000;">
                            <strong>Your Remarks:</strong><br>
                            <?= htmlspecialchars($row['principal_remarks']) ?>
                          </div>
                        <?php else: ?>
                          <span style="color: #999; font-style: italic;">-</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </table>
            <?php else: ?>
              <div class="form-container">
                <p>No pending Dean requests.</p>
              </div>
            <?php endif; ?>
            <?php $stmt->close(); ?>
          </div>
        </section>
      </div>
    </div>
  </div>

  <script>
    // Sidebar navigation
    document.querySelectorAll('.sidebar ul li').forEach(item => {
      item.addEventListener('click', function() {
        const section = this.getAttribute('data-section');
        if (section && section !== 'logout') {
          let url = `?tab=${section}`;
          
          if (section === 'leave-requests') {
            url += '&sub_tab=faculty-requests';
          }
          
          window.location.href = url;
          
          if (window.innerWidth <= 992) {
            document.getElementById('sidebar').classList.remove('active');
          }
        }
      });
    });

    document.getElementById('sidebarToggle').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('active');
    });

    document.addEventListener('click', function(event) {
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebarToggle');
      
      if (window.innerWidth <= 992 && 
          !sidebar.contains(event.target) && 
          !sidebarToggle.contains(event.target) &&
          sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
      }
    });

    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const currentTab = urlParams.get('tab') || 'leave-requests';
      const activeSubTabFromUrl = urlParams.get('sub_tab');

      // Highlight active sidebar item
      const activeSidebarItem = document.querySelector(`.sidebar ul li[data-section="${currentTab}"]`);
      if (activeSidebarItem) {
        activeSidebarItem.classList.add('active');
      }

      document.querySelectorAll('.request-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.request-content').forEach(c => c.classList.remove('active'));

      // Show faculty-requests if leave-requests tab is active and no sub_tab is specified
      if (currentTab === 'leave-requests' && !activeSubTabFromUrl) {
        const facultyTab = document.querySelector('.request-tab[data-target="faculty-requests"]');
        const facultyContent = document.getElementById('faculty-requests');
        if (facultyTab) facultyTab.classList.add('active');
        if (facultyContent) facultyContent.classList.add('active');
      } else if (activeSubTabFromUrl) {
        const targetTab = document.querySelector(`.request-tab[data-target="${activeSubTabFromUrl}"]`);
        const targetContent = document.getElementById(activeSubTabFromUrl);
        if (targetTab) targetTab.classList.add('active');
        if (targetContent) targetContent.classList.add('active');
      }

      // Tab click handler
      document.querySelectorAll('.request-tab').forEach(tab => {
        tab.addEventListener('click', function() {
          document.querySelectorAll('.request-tab').forEach(t => t.classList.remove('active'));
          document.querySelectorAll('.request-content').forEach(c => c.classList.remove('active'));
          this.classList.add('active');

          const target = this.getAttribute('data-target');
          document.getElementById(target).classList.add('active');

          const currentUrl = new URL(window.location.href);
          currentUrl.searchParams.set('tab', 'leave-requests');
          currentUrl.searchParams.set('sub_tab', target);

          window.history.pushState({path: currentUrl.href}, '', currentUrl.href);
        });
      });
    });

    function submitWithRemarks(action, form) {
        let message = '';
        if (action === 'approve') {
            message = 'Are you sure you want to APPROVE this leave request?';
        } else if (action === 'reject') {
            message = 'Are you sure you want to REJECT this leave request?';
        }
        
        if (!confirm(message)) {
            return false;
        }
        
        const leaveId = form.querySelector('input[name="leave_id"]').value;
        const remarksTextbox = document.querySelector(`textarea[name="remarks_${leaveId}"]`);
        
        if (remarksTextbox) {
            const remarksValue = remarksTextbox.value.trim();
            const remarksInput = document.createElement('input');
            remarksInput.type = 'hidden';
            remarksInput.name = 'remarks';
            remarksInput.value = remarksValue;
            form.appendChild(remarksInput);
        }
        
        return true;
    }
  </script>
</body>
</html>