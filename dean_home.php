<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'dean') {
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

$tab = $_GET['tab'] ?? 'profile';
$active_sub_tab = $_GET['sub_tab'] ?? 'faculty-requests';

// Handle approvals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = (int) $_POST['leave_id'];
    $action = $_POST['action'];
    $current_sub_tab = $_POST['active_sub_tab'] ?? 'faculty-requests';

    if ($action === 'approve') {
        $status = 'approved';
        $stmt = $conn->prepare("UPDATE leave_requests SET dean_status = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $status, $leave_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'reject' && !empty($_POST['rejection_reason'])) {
        $status = 'rejected';
        $rejection_reason = $_POST['rejection_reason'];
        $stmt = $conn->prepare("UPDATE leave_requests SET dean_status = ?, status = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $status, $rejection_reason, $leave_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dean_home.php?tab=leave-requests&sub_tab=" . $current_sub_tab);
    exit();
}

// Simplified department matching - uses only PHP array
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
    $dean_departments = [$department]; // Treat as both school and department
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Dean Dashboard</title>
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

    .status-text-pending { color: #facc15; font-weight: bold;}
    .status-text-approved { color: #16a34a; font-weight: bold; }
    .status-text-rejected { color: #dc2626; font-weight: bold; }

    .status-pending {
      background-color: #facc15;
      color: #131921;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: bold;
    }

    .status-approved {
      background-color: #16a34a;
      color: white;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: bold;
    }

    .status-rejected {
      background-color: #dc2626;
      color: white;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: bold;
    }

    .leave-cards {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 30px;
    }

    .leave-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      width: 320px;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08), 
            0 -6px 12px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s ease;
      border: 1px solid #ddd;
    }

    .leave-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
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

    .card-effect {
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .card-effect:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }

    /* Action buttons */
    .action-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-right: 5px;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
      color: white;
    }
    
    .approve-btn {
      background-color: green;
    }
    
    .reject-btn {
      background-color: red;
    }
    
    .rejection-input {
      padding: 5px;
      border: 1px solid #ccc;
      border-radius: 4px;
      margin-right: 5px;
    }

    /* Request type tabs */
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

      .leave-cards {
        justify-content: center;
      }

      .leave-card {
        width: 100%;
        max-width: 350px;
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
    }

    @media (max-width: 576px) {
      .leave-card {
        padding: 15px;
      }

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
    <h2 style="color: #febd69;">Dean Dashboard</h2>
    <img src="<?= htmlspecialchars($photo); ?>" alt="Dean Photo" />
    <ul>
      <li data-section="profile"><i class="fas fa-user"></i> Profile</li>
      <li data-section="apply-leave"><i class="fas fa-pen"></i> Apply Leave</li>
      <li data-section="leave-requests"><i class="fas fa-inbox"></i> Leave Requests</li>
      <li data-section="leave-status"><i class="fas fa-check-circle"></i> Leave Status</li>
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

          <form action="submit_leave.php" method="POST" class="form-container">
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
              <input type="date" name="date_from" id="date_from" class="form-control" required>
            </div>

            <div class="form-group">
              <label for="date_to">To Date</label>
              <input type="date" name="date_to" id="date_to" class="form-control" required>
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
          <div class="form-header">
            <h2>LEAVE REQUESTS</h2>
          </div>

          <div class="request-tabs">
            <div class="request-tab <?= $active_sub_tab == 'faculty-requests' ? 'active' : '' ?>" data-target="faculty-requests">Faculty Requests</div>
            <div class="request-tab <?= $active_sub_tab == 'hod-requests' ? 'active' : '' ?>" data-target="hod-requests">HOD Requests</div>
          </div>

          <div id="faculty-requests" class="request-content <?= $active_sub_tab == 'faculty-requests' ? 'active' : '' ?>">
            <?php
            // Only show requests from faculty in the dean's school departments
            $placeholders = implode(',', array_fill(0, count($dean_departments), '?'));
            $types = str_repeat('s', count($dean_departments));
            
            $query = "SELECT lr.id, u.name, lr.leave_type, lr.date_from, lr.date_to, lr.reason 
                     FROM leave_requests lr 
                     JOIN users u ON lr.faculty_id = u.id 
                     WHERE lr.dean_status = 'pending' AND u.role = 'faculty' 
                     AND u.department IN ($placeholders)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$dean_departments);
            $stmt->execute();
            $result = $stmt->get_result();
            ?>
            
            <?php if ($result->num_rows > 0): ?>
              <table>
                <tr>
                  <th>Name</th>
                  <th>Type</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Reason</th>
                  <th>Actions</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['leave_type']) ?></td>
                    <td><?= $row['date_from'] ?></td>
                    <td><?= $row['date_to'] ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                        <input type="hidden" name="active_sub_tab" value="faculty-requests"> 
                        <button class="action-btn approve-btn" name="action" value="approve">Approve</button>
                      </form>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                        <input type="hidden" name="active_sub_tab" value="faculty-requests"> 
                        <input type="text" name="rejection_reason" placeholder="Rejection reason" required class="rejection-input">
                        <button class="action-btn reject-btn" name="action" value="reject">Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </table>
            <?php else: ?>
              <div class="form-container">
                <p>No pending faculty requests in your school departments.</p>
              </div>
            <?php endif; ?>
            <?php $stmt->close(); ?>
          </div>

          <div id="hod-requests" class="request-content <?= $active_sub_tab == 'hod-requests' ? 'active' : '' ?>">
            <?php
            // Only show requests from HODs in the dean's school departments
            $placeholders = implode(',', array_fill(0, count($dean_departments), '?'));
            $types = str_repeat('s', count($dean_departments));
            
            $query = "SELECT lr.id, u.name, lr.leave_type, lr.date_from, lr.date_to, lr.reason 
                     FROM leave_requests lr 
                     JOIN users u ON lr.faculty_id = u.id 
                     WHERE lr.dean_status = 'pending' AND u.role = 'hod' 
                     AND lr.forwarded_to LIKE '%dean%' 
                     AND u.department IN ($placeholders)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$dean_departments);
            $stmt->execute();
            $result = $stmt->get_result();
            ?>
            
            <?php if ($result->num_rows > 0): ?>
              <table>
                <tr>
                  <th>Name</th>
                  <th>Type</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Reason</th>
                  <th>Actions</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['leave_type']) ?></td>
                    <td><?= $row['date_from'] ?></td>
                    <td><?= $row['date_to'] ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                        <input type="hidden" name="active_sub_tab" value="hod-requests"> 
                        <button class="action-btn approve-btn" name="action" value="approve">Approve</button>
                      </form>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="leave_id" value="<?= $row['id']; ?>">
                        <input type="hidden" name="active_sub_tab" value="hod-requests"> 
                        <input type="text" name="rejection_reason" placeholder="Rejection reason" required class="rejection-input">
                        <button class="action-btn reject-btn" name="action" value="reject">Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </table>
            <?php else: ?>
              <div class="form-container">
                <p>No pending HOD requests in your school departments.</p>
              </div>
            <?php endif; ?>
            <?php $stmt->close(); ?>
          </div>
        </section>

        <!-- Leave Status Section -->
        <section id="leave-status" class="section <?= $tab == 'leave-status' ? 'active' : '' ?>">
          <div class="form-header">
            <h2>LEAVE STATUS</h2>
          </div>

          <?php
          $stmt = $conn->prepare("SELECT id, leave_type, date_from, date_to, status, hod_status, dean_status, principal_status, rejection_reason FROM leave_requests WHERE faculty_id = ? ORDER BY id DESC");
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $result = $stmt->get_result();
          $leaves = [];
          while ($row = $result->fetch_assoc()) {
              $leaves[] = $row;
          }
          $stmt->close();
          ?>

          <?php if (!empty($leaves)): ?>
              <div class="leave-cards">
                  <?php foreach ($leaves as $leave): ?>
                      <div class="leave-card card-effect">
                          <h3 style="color: #007185;"><?= htmlspecialchars($leave['leave_type']) ?></h3>
                          <p><strong>From:</strong> <?= htmlspecialchars($leave['date_from']) ?></p>
                          <p><strong>To:</strong> <?= htmlspecialchars($leave['date_to']) ?></p>
                          <p><strong>Status:</strong> 
                            <span class="status-<?= htmlspecialchars($leave['status']) ?>">
                              <?= ucfirst($leave['status']) ?>
                            </span>
                          </p>

                          <?php if (!empty($leave['rejection_reason']) && $leave['status'] === 'rejected'): ?>
                              <p><strong>Rejection Reason:</strong> <?= htmlspecialchars($leave['rejection_reason']) ?></p>
                          <?php endif; ?>
                      </div>
                  <?php endforeach; ?>
              </div>
          <?php else: ?>
              <div class="form-container">
                <p>No leave applications found.</p>
              </div>
          <?php endif; ?>
        </section>

        <!-- Leave History Section -->
        <section id="leave-history" class="section <?= $tab == 'leave-history' ? 'active' : '' ?>">
          <div class="form-header">
            <h2>LEAVE HISTORY</h2>
          </div>

          <div class="form-container">
            <table>
              <tr>
                <th>Type</th>
                <th>From</th>
                <th>To</th>
                <th>Reason</th>
                <th>Status</th>
              </tr>
              <?php
              $stmt = $conn->prepare("SELECT leave_type, date_from, date_to, reason, status FROM leave_requests WHERE faculty_id = ? ORDER BY date_from DESC");
              $stmt->bind_param("i", $id);
              $stmt->execute();
              $result = $stmt->get_result();
              
              if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      echo '<tr>';
                      echo '<td>' . htmlspecialchars($row['leave_type']) . '</td>';
                      echo '<td>' . htmlspecialchars($row['date_from']) . '</td>';
                      echo '<td>' . htmlspecialchars($row['date_to']) . '</td>';
                      echo '<td>' . htmlspecialchars($row['reason']) . '</td>';
                      echo '<td><span class="status-text-' . htmlspecialchars($row['status']) . '">' . ucfirst(htmlspecialchars($row['status'])) . '</span></td>';
                      echo '</tr>';
                  }
              } else {
                  echo '<tr><td colspan="5">No leave history found.</td></tr>';
              }
              $stmt->close();
              ?>
            </table>
          </div>
        </section>
      </div>
    </div>
  </div>

  <script>
    // Handle tab switching
    document.querySelectorAll('.sidebar ul li').forEach(item => {
      item.addEventListener('click', function() {
        const section = this.getAttribute('data-section');
        if (section && section !== 'logout') {
          window.location.href = `?tab=${section}`;
          // Close sidebar on mobile after selection
          if (window.innerWidth <= 992) {
            document.getElementById('sidebar').classList.remove('active');
          }
        }
      });
    });

    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('active');
    });

    // Close sidebar when clicking outside on mobile
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

    // Handle request type tabs
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const activeSubTabFromUrl = urlParams.get('sub_tab');

        // Remove active class from all tabs and contents initially
        document.querySelectorAll('.request-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.request-content').forEach(c => c.classList.remove('active'));

        if (activeSubTabFromUrl) {
            // Activate the tab and content based on URL parameter
            const targetTab = document.querySelector(`.request-tab[data-target="${activeSubTabFromUrl}"]`);
            const targetContent = document.getElementById(activeSubTabFromUrl);

            if (targetTab) {
                targetTab.classList.add('active');
            }
            if (targetContent) {
                targetContent.classList.add('active');
            }
        } else {
            // If no sub_tab parameter, default to 'faculty-requests' for leave-requests tab
            const currentMainTab = urlParams.get('tab');
            if (currentMainTab === 'leave-requests') {
                document.querySelector('.request-tab[data-target="faculty-requests"]').classList.add('active');
                document.getElementById('faculty-requests').classList.add('active');
            }
        }

        // Add event listeners for click events on tabs
        document.querySelectorAll('.request-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.request-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.request-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                const target = this.getAttribute('data-target');
                document.getElementById(target).classList.add('active');

                // Update URL with sub_tab parameter when a tab is clicked
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('tab', 'leave-requests'); // Ensure main tab is also correct
                currentUrl.searchParams.set('sub_tab', target);
                window.history.pushState({path: currentUrl.href}, '', currentUrl.href);
            });
        });
    });
  </script>
</body>
</html>