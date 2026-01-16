<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../pages/Login.html");
    exit();
}


$sidebarProfile = [
    'first_name' => $_SESSION['first_name'] ?? 'User',
    'last_name'  => $_SESSION['last_name'] ?? '',
    'email'      => $_SESSION['email'] ?? ''
];


$avatarLetter = strtoupper(substr($sidebarProfile['first_name'], 0, 1));
?>
<aside class="sidebar">
    <div class="profile-box">
        <div class="avatar"><?= $avatarLetter ?></div>

        <strong>
            <?= htmlspecialchars(trim($sidebarProfile['first_name'] . ' ' . $sidebarProfile['last_name'])) ?>
        </strong>

        <small><?= htmlspecialchars($sidebarProfile['email']) ?></small>
    </div>

    <nav>
        <div style="margin-bottom:8px">
            <a href="SuperAdminDashboard.php" class="nav-link">Dashboard</a>
        </div>
        
        <div style="margin-bottom:8px">
            <a href="createadmin.php" class="nav-link">Create Admin</a>
        </div>

        <div style="margin-bottom:8px">
            <a href="consolidate.php" class="nav-link">Consolidate Reports</a>
        </div>

        <div style="margin-bottom:8px">
            <a href="Auditlogs.php" class="nav-link">Audit Logs</a>
        </div>



        <div style="margin-bottom:8px">
            <a href="#" id="logoutBtn" class="nav-link">Logout</a>
        </div>
    </nav>
</aside>
