<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dean') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Define schools and their departments
$schools = [
    'Natural and Applied Science' => [
        'Biochemistry', 'Biotechnology', 'Botany', 'Chemistry', 'Computer Science',
        'Electronics', 'Environmental Science', 'Fashion & Apparel Design',
        'Food Science & Nutrition', 'Interior Design & Management', 'Human Development', 
        'Home Science', 'Life Science', 'Mathematics', 'Microbiology', 'Nano Science & Technology', 'Physics',
        'Statistics & Analytics', 'Zoology'
    ],
    'Humanities and Social Sciences' => [
        'Communication Studies', 'Economics', 'English', 'French', 'German',
        'Hindi', 'History', 'Journalism', 'Kannada', 'Physical Education', 'Political Science', 'Psychology',
        'Sanskrit', 'Sociology', 'Travel & Tourism'
    ],
    'Commerce' => ['Commerce'],
    'Management' => ['Business Administration'],
    'Education' => ['Education']
];

// Fetch dean details
$stmt = $conn->prepare("SELECT id, name, username, role, department, photo, email, phone FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($id, $name, $username, $role, $department, $photo, $email, $phone);
$stmt->fetch();
$stmt->close();

// Default to leave-requests
$tab = $_GET['tab'] ?? 'leave-requests';
$active_sub_tab = $_GET['sub_tab'] ?? 'faculty-requests';

// If leave-requests tab is active but no sub_tab specified, force faculty-requests
if ($tab === 'leave-requests' && !isset($_GET['sub_tab'])) {
    $active_sub_tab = 'faculty-requests';
}

// Handle approvals with dropdown action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = (int) $_POST['leave_id'];
    $action = $_POST['action'];
    $current_sub_tab = $_POST['active_sub_tab'] ?? 'faculty-requests';
    $remarks = $_POST['remarks'] ?? '';

    if ($action === 'approve') {
        $status = 'approved';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    }

    if (isset($status)) {
        // Update dean_status and dean_remarks
        $stmt = $conn->prepare("UPDATE leave_requests SET dean_status = ?, dean_remarks = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $remarks, $status, $leave_id);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect back to same tab with filter
    $redirect_url = "dean_home.php?tab=leave-requests&sub_tab=" . urlencode($current_sub_tab);
    if (!empty($_POST['date_from_filter'])) {
        $redirect_url .= "&from=" . urlencode($_POST['date_from_filter']);
    }

    header("Location: " . $redirect_url);
    exit();
}

// From-date filter 
$date_from_filter = $_GET['from'] ?? '';

// Leave history filters
$filterLeaveType = $_GET['leave_type'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';

// Department matching system 
$dean_school = null;
$dean_departments = [];

foreach ($schools as $school => $depts) {
    if ($department === $school || in_array($department, $depts)) {
        $dean_school = $school;
        $dean_departments = $depts;
        break;
    }
}

// If still no match, assume department is the school
if ($dean_school === null) {
    $dean_school = $department;
    $dean_departments = [$department];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Dean Dashboard</title>

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

    /* Filter Controls */
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

    /* Leave Requests Specific Styles */
    .filter-section {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border: 1px solid #dee2e6;
    }

    .filter-row {
      display: flex;
      align-items: flex-end;
      gap: 15px;
      flex-wrap: wrap;
    }

    .filter-group {
      flex: 1;
      min-width: 200px;
    }

    .filter-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #495057;
    }
    
    .filter-control {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      background-color: white;
      max-width: 650px;
    }
    
    .filter-actions {
      display: flex;
      align-items: flex-end;
      gap: 10px;
      margin-top: 0;
    }

    .apply-filter {
      background-color: #FFD700;
      color: #131921;
      padding: 6px 18px;
      font-size: 14px;
    }

    .apply-filter i {
      color: #131921;
    }

    .apply-filter:hover {
      background-color: #FFC107;
    }

    .reset-filter {
      background-color: #6c757d;
      color: white;
      padding: 6px 18px;
      font-size: 14px;
    }

    .reset-filter i {
      color: white;
    }

    .reset-filter:hover {
      background-color: #5a6268;
    }

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

    /* Final Status Badge */
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

      .filter-row {
        flex-direction: column;
        align-items: flex-start;
      }

      .filter-group { 
        min-width: 100%; 
      }

      .filter-controls {
        flex-direction: column;
        align-items: stretch;
      }

      .filter-select { 
        width: 100%; 
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
  </style>
</head>

<body>
  <button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
  </button>

  <div class="sidebar" id="sidebar">
    <h2 style="color: #febd69;">Dean Dashboard</h2>
    <img src="<?= htmlspecialchars($photo); ?>" alt="Dean Photo" />
    <ul>
      <li data-section="profile"><i class="fas fa-user"></i> Profile</li>
      <li data-section="apply-leave"><i class="fas fa-pen"></i> Apply Leave</li>
      <li data-section="leave-requests"><i class="fas fa-inbox"></i> Leave Requests</li>
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
            <h2>DEAN PROFILE</h2>
          </div>

          <div class="form-container">
            <div class="profile-photo">
              <img src="<?= htmlspecialchars($photo); ?>" alt="Dean Photo">
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

          <form id="leaveForm" class="form-container" action="submit_leave.php" method="POST">
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

        <!-- Leave Requests Section -->
        <section id="leave-requests" class="section <?= $tab == 'leave-requests' ? 'active' : '' ?>">
          <div class="form-header"><h2>LEAVE REQUESTS</h2></div>

          <!-- Request Tabs -->
          <div class="request-tabs">
            <div class="request-tab <?= $active_sub_tab == 'faculty-requests' ? 'active' : '' ?>" data-target="faculty-requests">Faculty Requests</div>
            <div class="request-tab <?= $active_sub_tab == 'hod-requests' ? 'active' : '' ?>" data-target="hod-requests">HOD Requests</div>
          </div>

          <!-- From-Date filter -->
          <div class="filter-section">
            <form method="GET" id="filterForm">
              <input type="hidden" name="tab" value="leave-requests">
              <input type="hidden" name="sub_tab" value="<?= htmlspecialchars($active_sub_tab) ?>">

              <div class="filter-row">
                <div class="filter-group">
                  <label for="date_from_filter">Select Date</label>
                  <input
                    type="date"
                    name="from"
                    id="date_from_filter"
                    class="filter-control"
                    value="<?= htmlspecialchars($date_from_filter) ?>"
                  >
                </div>

                <div class="filter-actions">
                  <button type="submit" class="filter-btn apply-filter">
                    <i class="fas fa-filter"></i> Filter
                  </button>
                  <button type="button" class="filter-btn reset-filter" onclick="resetFilters()">
                    <i class="fas fa-redo"></i> Reset
                  </button>
                </div>
              </div>
            </form>
          </div>

          <!-- Faculty Requests -->
          <div id="faculty-requests" class="request-content <?= $active_sub_tab == 'faculty-requests' ? 'active' : '' ?>">
            <?php
            // Only show requests from faculty in the dean's school departments
            $placeholders = implode(',', array_fill(0, count($dean_departments), '?'));
            $types = str_repeat('s', count($dean_departments));
            
            $faculty_query = "SELECT lr.id, u.name, u.department, lr.leave_type, lr.date_from, lr.date_to, lr.reason, 
                           lr.status, lr.hod_status, lr.dean_status, lr.principal_status, 
                           lr.hod_remarks, lr.dean_remarks, lr.principal_remarks 
                           FROM leave_requests lr 
                           JOIN users u ON lr.faculty_id = u.id 
                           WHERE lr.dean_status = 'pending' 
                           AND u.role = 'faculty' 
                           AND u.department IN ($placeholders)";

            $param_types = $types;
            $param_values = $dean_departments;
            
            if (!empty($date_from_filter)) {
                $faculty_query .= " AND lr.date_from >= ?";
                $param_types .= 's';
                $param_values[] = $date_from_filter;
            }

            $stmt = $conn->prepare($faculty_query);
            if (!empty($param_types)) {
                $stmt->bind_param($param_types, ...$param_values);
            }
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
                            <span class="approval-status pending">Pending</span>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if ($row['dean_status'] === 'pending'): ?>
                        <div class="compact-actions">
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('approve', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="faculty-requests">
                            <input type="hidden" name="date_from_filter" value="<?= htmlspecialchars($date_from_filter) ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="compact-btn compact-approve">Approve</button>
                          </form>
                          
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('reject', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="faculty-requests">
                            <input type="hidden" name="date_from_filter" value="<?= htmlspecialchars($date_from_filter) ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="compact-btn compact-reject">Reject</button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span class="processed-status">
                          <?= ucfirst($row['dean_status']) ?> by Dean
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['dean_status'] === 'pending'): ?>
                        <textarea 
                          name="remarks_<?= $row['id']; ?>" 
                          class="remarks-textbox" 
                          placeholder="Remarks"
                          data-leave-id="<?= $row['id']; ?>"></textarea>
                      <?php else: ?>
                        <?php if (!empty($row['dean_remarks']) && $row['dean_status'] === 'rejected'): ?>
                          <div style="font-size: 13px; color: #000;">
                            <strong>Your Remarks:</strong><br>
                            <?= htmlspecialchars($row['dean_remarks']) ?>
                          </div>
                        <?php elseif (!empty($row['dean_remarks']) && $row['dean_status'] === 'approved'): ?>
                          <div style="font-size: 13px; color: #000;">
                            <strong>Your Remarks:</strong><br>
                            <?= htmlspecialchars($row['dean_remarks']) ?>
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
                <p>No pending faculty requests in your school departments (<?= htmlspecialchars($dean_school) ?>).</p>
                <?php if (!empty($date_from_filter)): ?>
                  <p style="font-size: 12px; color: #666; margin-top: 10px;">
                    Filtered from date: <?= htmlspecialchars($date_from_filter) ?>
                  </p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php $stmt->close(); ?>
          </div>

          <!-- HOD Requests -->
          <div id="hod-requests" class="request-content <?= $active_sub_tab == 'hod-requests' ? 'active' : '' ?>">
            <?php
            // Only show requests from HODs in the dean's school departments
            $placeholders = implode(',', array_fill(0, count($dean_departments), '?'));
            $types = str_repeat('s', count($dean_departments));
            
            $hod_query = "SELECT lr.id, u.name, u.department, lr.leave_type, lr.date_from, lr.date_to, lr.reason, 
                           lr.status, lr.hod_status, lr.dean_status, lr.principal_status, 
                           lr.hod_remarks, lr.dean_remarks, lr.principal_remarks 
                           FROM leave_requests lr 
                           JOIN users u ON lr.faculty_id = u.id 
                           WHERE lr.dean_status = 'pending' 
                           AND u.role = 'hod' 
                           AND u.department IN ($placeholders)";

            $param_types = $types;
            $param_values = $dean_departments;
            
            if (!empty($date_from_filter)) {
                $hod_query .= " AND lr.date_from >= ?";
                $param_types .= 's';
                $param_values[] = $date_from_filter;
            }

            $stmt = $conn->prepare($hod_query);
            if (!empty($param_types)) {
                $stmt->bind_param($param_types, ...$param_values);
            }
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
                      <?php if ($row['dean_status'] === 'pending'): ?>
                        <div class="compact-actions">
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('approve', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="hod-requests">
                            <input type="hidden" name="date_from_filter" value="<?= htmlspecialchars($date_from_filter) ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="compact-btn compact-approve">Approve</button>
                          </form>
                          
                          <form method="POST" style="margin: 0;" onsubmit="return submitWithRemarks('reject', this)">
                            <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="active_sub_tab" value="hod-requests">
                            <input type="hidden" name="date_from_filter" value="<?= htmlspecialchars($date_from_filter) ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="compact-btn compact-reject">Reject</button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span class="processed-status">
                          <?= ucfirst($row['dean_status']) ?> by Dean
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['dean_status'] === 'pending'): ?>
                        <textarea 
                          name="remarks_<?= $row['id']; ?>" 
                          class="remarks-textbox" 
                          placeholder="Remarks"
                          data-leave-id="<?= $row['id']; ?>"></textarea>
                      <?php else: ?>
                        <?php if (!empty($row['dean_remarks']) && $row['dean_status'] === 'rejected'): ?>
                          <div style="font-size: 13px; color: #000;">
                            <strong>Your Remarks:</strong><br>
                            <?= htmlspecialchars($row['dean_remarks']) ?>
                          </div>
                        <?php elseif (!empty($row['dean_remarks']) && $row['dean_status'] === 'approved'): ?>
                          <div style="font-size: 13px; color: #000;">
                            <strong>Your Remarks:</strong><br>
                            <?= htmlspecialchars($row['dean_remarks']) ?>
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
                <p>No pending HOD requests in your school departments (<?= htmlspecialchars($dean_school) ?>).</p>
                <?php if (!empty($date_from_filter)): ?>
                  <p style="font-size: 12px; color: #666; margin-top: 10px;">
                    Filtered from date: <?= htmlspecialchars($date_from_filter) ?>
                  </p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php $stmt->close(); ?>
          </div>
        </section>

        <!-- Leave History Section -->
        <section id="leave-history" class="section <?= $tab == 'leave-history' ? 'active' : '' ?>">
          <div class="form-header"><h2>LEAVE HISTORY</h2></div>
          <div class="form-container">
            <div class="filter-controls">
              <select id="filterLeaveType" class="filter-select">
                <option value="all">All Types</option>
                <option value="Casual Leave" <?= ($filterLeaveType == 'Casual Leave') ? 'selected' : '' ?>>Casual Leave</option>
                <option value="Medical Leave" <?= ($filterLeaveType == 'Medical Leave') ? 'selected' : '' ?>>Medical Leave</option>
                <option value="Earned Leave" <?= ($filterLeaveType == 'Earned Leave') ? 'selected' : '' ?>>Earned Leave</option>
              </select>
              <select id="filterStatus" class="filter-select">
                <option value="all">All Status</option>
                <option value="pending" <?= ($filterStatus == 'pending') ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= ($filterStatus == 'approved') ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= ($filterStatus == 'rejected') ? 'selected' : '' ?>>Rejected</option>
              </select>
              <button id="applyFilter" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
              </button>
              <button id="resetFiltersHistory" class="reset-btn">
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
                  $query = "SELECT leave_type, date_from, date_to, reason, 
                           principal_status, principal_remarks
                           FROM leave_requests WHERE faculty_id = ?";

                  $params = [$id];
                  $types = "i";

                  if ($filterLeaveType !== 'all') {
                    $query .= " AND leave_type = ?";
                    $params[] = $filterLeaveType;
                    $types .= "s";
                  }
                  
                  if ($filterStatus !== 'all') {
                    $query .= " AND principal_status = ?";
                    $params[] = $filterStatus;
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
                      $finalStatus = $row['principal_status'] ?: 'pending';
                      
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
                      
                      //Principal Status
                      echo '<div class="approval-item">';
                      echo '<div class="approval-header">';
                      echo '<span class="approval-authority">Principal:</span>';
                      echo '<span class="approval-status ' . $row['principal_status'] . '">' . ucfirst($row['principal_status'] ?: 'pending') . '</span>';
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
      document.querySelectorAll('.sidebar ul li').forEach(item => {
        item.classList.remove('active');
      });
      const activeSidebarItem = document.querySelector(`.sidebar ul li[data-section="${currentTab}"]`);
      if (activeSidebarItem) {
        activeSidebarItem.classList.add('active');
      }

      document.querySelectorAll('.request-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.request-content').forEach(c => c.classList.remove('active'));

      // ALWAYS show faculty-requests if leave-requests tab is active and no sub_tab is specified
      if (currentTab === 'leave-requests' && !activeSubTabFromUrl) {
        const facultyTab = document.querySelector('.request-tab[data-target="faculty-requests"]');
        const facultyContent = document.getElementById('faculty-requests');
        if (facultyTab) facultyTab.classList.add('active');
        if (facultyContent) facultyContent.classList.add('active');
        
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('tab', 'leave-requests');
        newUrl.searchParams.set('sub_tab', 'faculty-requests');
        window.history.replaceState({path: newUrl.href}, '', newUrl.href);
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

          const dateFrom = document.getElementById('date_from_filter').value;
          if (dateFrom) currentUrl.searchParams.set('from', dateFrom);
          else currentUrl.searchParams.delete('from');

          window.history.pushState({path: currentUrl.href}, '', currentUrl.href);
        });
      });

      // Leave history filter logic
      const filterLeaveType = document.getElementById('filterLeaveType');
      const filterStatus = document.getElementById('filterStatus');
      const applyFilter = document.getElementById('applyFilter');
      const resetFiltersHistory = document.getElementById('resetFiltersHistory');

      applyFilter.addEventListener('click', function() {
        const leaveType = filterLeaveType.value;
        const status = filterStatus.value;
        let url = '?tab=leave-history';
        if (leaveType !== 'all') url += '&leave_type=' + encodeURIComponent(leaveType);
        if (status !== 'all') url += '&status=' + encodeURIComponent(status);
        window.location.href = url;
      });

      resetFiltersHistory.addEventListener('click', function() {
        window.location.href = '?tab=leave-history';
      });

      // Clear old localStorage remarks
      Object.keys(localStorage).forEach(key => {
        if (key.startsWith('remarks_')) {
          localStorage.removeItem(key);
        }
      });
      
      // Initialize remarks textboxes
      document.querySelectorAll('.remarks-textbox').forEach(textbox => {
        textbox.value = '';
        
        textbox.addEventListener('input', function() {
          const leaveId = this.getAttribute('data-leave-id');
          if (leaveId) {
            localStorage.setItem(`remarks_${leaveId}`, this.value);
          }
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
            
            localStorage.removeItem(`remarks_${leaveId}`);
        }
        
        return true;
    }

    function resetFilters() {
      window.location.href = `?tab=leave-requests&sub_tab=<?= htmlspecialchars($active_sub_tab) ?>`;
    }
  </script>
</body>
</html>