<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sk') {
    header("Location: ../Login.html");
    exit();
}

$sk_name   = $_SESSION['fullname'] ?? '';
$sk_email  = $_SESSION['email'] ?? '';
$sk_initial = strtoupper(substr($sk_name, 0, 1));
?>


<aside class="sidebar">
  <div class="profile-box">
    <div class="avatar"><?= $sk_initial ?></div>
    <div class="info text-center mt-2">
      <strong><?= htmlspecialchars($sk_email) ?></strong>
    </div>
  </div>

  <nav class="mt-3">
    <a href="SkDashboard.php" class="nav-link">Youth Management</a> 
    <a href="MainSkDashboard.php" class="nav-link">Main Dashboard</a>
    <a href="CreateActivity.php" class="nav-link">Activities & Programs</a>
    <a href="Attendance.php" class="nav-link">Attendance List</a>
    <a href= "GenerateReportSK.php" class="nav-link">Report</a> 
    <a href="../auth/Logout.php" class="nav-link">Logout</a>
  </nav>
</aside>
