<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

$stmt = $conn->prepare("SELECT id, name, username, role, department, photo, email, phone FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($id, $name, $username, $role, $department, $photo, $email, $phone);
$stmt->fetch();
$stmt->close();

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'leave-history';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Faculty Dashboard</title>
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

    .final-status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 5px;
      font-size: 13px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .final-status-badge.approved {
      background-color: #d4edda;
      color: #155724;
      border: 2px solid #28a745;
    }

    .final-status-badge.rejected {
      background-color: #f8d7da;
      color: #721c24;
      border: 2px solid #dc3545;
    }

    .final-status-badge.pending {
      background-color: #fff3cd;
      color: #856404;
      border: 2px solid #ffc107;
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

    .filter-controls {
      display: flex;
      gap: 10px;
      margin-bottom: 15px;
      flex-wrap: wrap;
      align-items: center;
    }

    .filter-select {
      padding: 6px 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      background-color: white;
      color: #131921;
      font-size: 14px;
      min-width: 120px;
    }

    .filter-select:focus {
      outline: none;
      border-color: #FFD700;
    }

    .filter-btn {
      background-color: #FFD700;
      color: #131921;
      padding: 6px 15px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .filter-btn:hover {
      background-color: #FFC107;
    }

    .reset-btn {
      background-color: #6c757d;
      color: white;
      padding: 6px 15px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .reset-btn:hover {
      background-color: #5a6268;
    }

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

      table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
      }

      .profile-field label {
        font-size: 13px;
      }

      .filter-controls {
        flex-direction: column;
        align-items: stretch;
      }

      .filter-select {
        width: 100%;
      }
      
      th, td {
        padding: 8px 5px;
        font-size: 14px;
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
    <h2 style="color: #febd69;">Faculty Dashboard</h2>
    <img src="<?= htmlspecialchars($photo); ?>" alt="Faculty Photo" />
    <ul>
      <li data-section="profile"><i class="fas fa-user"></i> Profile</li>
      <li data-section="apply-leave"><i class="fas fa-pen"></i> Apply Leave</li>
      <li data-section="leave-history"><i class="fas fa-history"></i> Leave History</li>
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
            <h2>FACULTY PROFILE</h2>
          </div>

          <div class="form-container">
            <div class="profile-photo">
              <img src="<?= htmlspecialchars($photo); ?>" alt="Faculty Photo">
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

        <!-- Apply Leave Section -->
        <section id="apply-leave" class="section <?= $tab == 'apply-leave' ? 'active' : '' ?>">
          <div class="form-header">
            <h2>APPLY LEAVE</h2>
          </div>

          <form id="leaveForm" class="form-container">
            <div class="form-group">
              <label for="leave_type">Leave Type</label>
              <select style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; background-color: #eaeded;" name="leave_type" id="leave_type" required>
                <option value="" disabled selected>Select Leave Type</option>
                <option value="Casual Leave">Casual Leave</option>
                <option value="Medical Leave">Medical Leave</option>
                <option value="Earned Leave">Earned Leave</option>
              </select>
            </div>

            <div class="form-group">
              <label for="date_from">From Date</label>
              <input type="date" name="date_from" id="date_from" class="form-control" required min="<?= date('Y-m-d'); ?>">
              <small id="date_from_error" style="color: red; display: none;">Date cannot be in the past</small>
            </div>

            <div class="form-group">
              <label for="date_to">To Date</label>
              <input type="date" name="date_to" id="date_to" class="form-control" required min="<?= date('Y-m-d'); ?>">
              <small id="date_to_error" style="color: red; display: none;">Date cannot be before From Date</small>
            </div>

            <div class="form-group">
              <label for="reason">Reason</label>
              <textarea name="reason" id="reason" class="form-control" required></textarea>
            </div>

            <input type="hidden" name="faculty_id" value="<?= htmlspecialchars($id); ?>">
            <input type="hidden" name="department" value="<?= htmlspecialchars($department); ?>">

            <div style="text-align: center;">
              <button type="submit" class="submit-btn">SUBMIT</button>
            </div>
          </form>
        </section>

        <!-- Leave History Section -->
        <section id="leave-history" class="section <?= $tab == 'leave-history' ? 'active' : '' ?>">
          <div class="form-header">
            <h2>LEAVE HISTORY</h2>
          </div>

          <div class="form-container">
            <!-- Filter Controls -->
            <div class="filter-controls">
              <select id="filterLeaveType" class="filter-select">
                <option value="all">All Types</option>
                <option value="Casual Leave">Casual Leave</option>
                <option value="Medical Leave">Medical Leave</option>
                <option value="Earned Leave">Earned Leave</option>
              </select>
              
              <select id="filterStatus" class="filter-select">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
              
              <button id="applyFilter" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
              </button>
              
              <button id="resetFilters" class="reset-btn">
                <i class="fas fa-redo"></i> Reset
              </button>
            </div>

            <div id="leaveHistoryTable">
              <table>
                <thead>
                  <tr>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $filterLeaveType = $_GET['leave_type'] ?? 'all';
                  $filterStatus = $_GET['status'] ?? 'all';
                  
                  $query = "SELECT leave_type, date_from, date_to, reason, 
                           hod_status, hod_remarks, 
                           dean_status, dean_remarks, 
                           principal_status, principal_remarks
                           FROM leave_requests WHERE faculty_id = ?";
                  $params = [$id];
                  $types = "i";
                  
                  if ($filterLeaveType !== 'all') {
                    $query .= " AND leave_type = ?";
                    $params[] = $filterLeaveType;
                    $types .= "s";
                  }
                  
                  $query .= " ORDER BY date_from DESC";
                  
                  $stmt = $conn->prepare($query);
                  
                  if (count($params) > 1) {
                    $stmt->bind_param($types, ...$params);
                  } else {
                    $stmt->bind_param($types, $id);
                  }
                  
                  $stmt->execute();
                  $result = $stmt->get_result();
                  
                  if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                      $finalStatus = 'pending';
                      
                      $hodStatus = $row['hod_status'];
                      $deanStatus = $row['dean_status'];
                      $principalStatus = $row['principal_status'];
                      
                      if ($principalStatus === 'approved' || $principalStatus === 'rejected') {
                        $finalStatus = $principalStatus;
                      }
                      elseif ($deanStatus === 'rejected') {
                        $finalStatus = 'rejected';
                      }
                      elseif ($hodStatus === 'rejected') {
                        $finalStatus = 'rejected';
                      }
                      elseif ($hodStatus === 'approved' && $deanStatus === 'approved' && $principalStatus === 'approved') {
                        $finalStatus = 'approved';
                      }
                      else {
                        $finalStatus = 'pending';
                      }
                      
                      if (isset($filterStatus) && $filterStatus !== 'all' && $filterStatus !== $finalStatus) {
                        continue;
                      }
                      
                      echo '<tr>';
                      echo '<td>' . htmlspecialchars($row['leave_type']) . '</td>';
                      echo '<td>' . $row['date_from'] . '</td>';
                      echo '<td>' . $row['date_to'] . '</td>';
                      echo '<td>' . htmlspecialchars($row['reason']) . '</td>';
                      
                      echo '<td style="text-align: center;">';
                      echo '<span class="final-status-badge ' . $finalStatus . '">' . ucfirst($finalStatus) . '</span>';
                      echo '</td>';
                      
                      echo '<td>';
                      echo '<div class="approval-hierarchy">';
                      
                      echo '<div class="approval-item">';
                      echo '<div class="approval-header">';
                      echo '<span class="approval-authority">HOD:</span>';
                      echo '<span class="approval-status ' . $row['hod_status'] . '">' . ucfirst($row['hod_status']) . '</span>';
                      echo '</div>';
                      if (!empty($row['hod_remarks'])) {
                        echo '<div class="approval-remarks"><strong>Remarks:</strong> ' . htmlspecialchars($row['hod_remarks']) . '</div>';
                      }
                      echo '</div>';
                      
                      echo '<div class="approval-item">';
                      echo '<div class="approval-header">';
                      echo '<span class="approval-authority">Dean:</span>';
                      echo '<span class="approval-status ' . $row['dean_status'] . '">' . ucfirst($row['dean_status']) . '</span>';
                      echo '</div>';
                      if (!empty($row['dean_remarks'])) {
                        echo '<div class="approval-remarks"><strong>Remarks:</strong> ' . htmlspecialchars($row['dean_remarks']) . '</div>';
                      }
                      echo '</div>';
                      
                      echo '<div class="approval-item">';
                      echo '<div class="approval-header">';
                      echo '<span class="approval-authority">Principal:</span>';
                      echo '<span class="approval-status ' . $row['principal_status'] . '">' . ucfirst($row['principal_status']) . '</span>';
                      echo '</div>';
                      if (!empty($row['principal_remarks'])) {
                        echo '<div class="approval-remarks"><strong>Remarks:</strong> ' . htmlspecialchars($row['principal_remarks']) . '</div>';
                      }
                      echo '</div>';
                      
                      echo '</div>';
                      echo '</td>';
                      
                      echo '</tr>';
                    }
                  } else {
                    echo '<tr><td colspan="6">No leave history found.</td></tr>';
                  }
                  
                  $stmt->close();
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <script>
    const today = new Date().toISOString().split('T')[0];
    
    document.getElementById('date_from').min = today;
    document.getElementById('date_to').min = today;
    
    document.getElementById('date_from').addEventListener('change', function() {
      const fromDate = this.value;
      const toDateInput = document.getElementById('date_to');
      const errorFrom = document.getElementById('date_from_error');
      
      errorFrom.style.display = 'none';
      
      if (fromDate < today) {
        errorFrom.textContent = 'Date cannot be in the past';
        errorFrom.style.display = 'block';
        this.value = today;
      } else {
        toDateInput.min = fromDate;
        
        if (toDateInput.value && toDateInput.value < fromDate) {
          toDateInput.value = fromDate;
        }
      }
    });
    
    document.getElementById('date_to').addEventListener('change', function() {
      const fromDate = document.getElementById('date_from').value;
      const toDate = this.value;
      const errorTo = document.getElementById('date_to_error');
      
      errorTo.style.display = 'none';
      
      if (toDate < fromDate) {
        errorTo.textContent = 'Date cannot be before From Date';
        errorTo.style.display = 'block';
        this.value = fromDate;
      }
      
      if (toDate < today) {
        errorTo.textContent = 'Date cannot be in the past';
        errorTo.style.display = 'block';
        this.value = today;
      }
    });
    
    document.getElementById('leaveForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const form = this;
      const formData = new FormData(form);
      
      const fromDate = document.getElementById('date_from').value;
      const toDate = document.getElementById('date_to').value;
      const leaveType = document.getElementById('leave_type').value;
      const reason = document.getElementById('reason').value;
      
      if (!leaveType) {
        alert('Please select a leave type');
        return;
      }
      
      if (!reason.trim()) {
        alert('Please enter a reason for leave');
        return;
      }
      
      if (fromDate < today || toDate < today) {
        alert('Cannot select dates in the past');
        return;
      }
      
      if (toDate < fromDate) {
        alert('To Date cannot be before From Date');
        return;
      }
      
      const submitBtn = form.querySelector('.submit-btn');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Submitting...';
      submitBtn.disabled = true;
      
      fetch('submit_leave.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        
        alert(data);
        
        if (data.includes('successfully')) {
          form.reset();
          document.getElementById('date_from').min = today;
          document.getElementById('date_to').min = today;
          window.location.href = '?tab=leave-history';
        }
      })
      .catch(error => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        alert('Network error: ' + error.message);
      });
    });

    document.querySelectorAll('.sidebar ul li').forEach(item => {
      item.addEventListener('click', function() {
        const section = this.getAttribute('data-section');
        if (section && section !== 'logout') {
          window.location.href = `?tab=${section}`;
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
      const filterLeaveType = document.getElementById('filterLeaveType');
      const filterStatus = document.getElementById('filterStatus');
      const applyFilter = document.getElementById('applyFilter');
      const resetFilters = document.getElementById('resetFilters');
      
      const urlParams = new URLSearchParams(window.location.search);
      const currentLeaveType = urlParams.get('leave_type') || 'all';
      const currentStatus = urlParams.get('status') || 'all';
      
      filterLeaveType.value = currentLeaveType;
      filterStatus.value = currentStatus;
      
      applyFilter.addEventListener('click', function() {
        const leaveType = filterLeaveType.value;
        const status = filterStatus.value;
        
        let url = '?tab=leave-history';
        if (leaveType !== 'all') url += '&leave_type=' + encodeURIComponent(leaveType);
        if (status !== 'all') url += '&status=' + encodeURIComponent(status);
        
        window.location.href = url;
      });
      
      resetFilters.addEventListener('click', function() {
        window.location.href = '?tab=leave-history';
      });
    });
  </script>
</body>
</html>