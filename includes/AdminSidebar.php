<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.html");
    exit();
}

$fullname = $_SESSION['fullname'] ?? '';
$firstLetter = strtoupper(substr($fullname, 0, 1));
$email = $_SESSION['email'] ?? '';
?>


<aside class="sidebar">
  <div class="profile-box">

    <div class="avatar"><?= $firstLetter ?></div>
    <div class="info">
      <strong><?= htmlspecialchars($email) ?></strong>  
    </div>
  </div>

  <nav>
    <a href="../public/datamining.php" class="nav-link">Dashboard</a>
    <a href="AdminDashboard.php" class="nav-link">User Management</a>
    <a href="CreateActivity.php" class="nav-link">Activities & Programs</a>
    <a href="Attendance.php" class="nav-link">Attendance List</a>
    <a href="ViewReport.php" class="nav-link">View Report</a> 
    <a href="AuditLogs.php" class="nav-link">Audit Logs</a> 
    <a href="#" id="logoutBtn" class="nav-link">Logout</a>
  </nav>
</aside>
