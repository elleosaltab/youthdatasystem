<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'youth') {
    header("Location: ../pages/Login.html");
    exit();
}


$sidebarProfile = [
    'first_name' => $_SESSION['full_name'] ?? '',
    'last_name'  => '',
    'email'      => $_SESSION['email'] ?? ''
];
?>
<aside class="sidebar">
    <div class="profile-box">
        <div class="avatar">
            <?= strtoupper(substr($sidebarProfile['first_name'] ?? 'U', 0, 1)) ?>
        </div>
        <strong><?= htmlspecialchars(trim(($sidebarProfile['first_name'] ?? '') . ' ' . ($sidebarProfile['last_name'] ?? ''))) ?></strong>
        <small><?= htmlspecialchars($sidebarProfile['email'] ?? '') ?></small>
    </div>
    <nav>
        <div style="margin-bottom:8px">
            <a href="YouthDashboard.php" style="color:#cbd5e1; text-decoration:none">Activities & Programs</a>
        </div>
        <div style="margin-bottom:8px">
            <a href="YouthParticipation.php" style="color:#cbd5e1; text-decoration:none">My Participation</a>
        </div>
        <div style="margin-bottom:8px">
            <a href="YouthProfile.php" style="color:#cbd5e1; text-decoration:none">Profile</a>
        </div>
        <div style="margin-bottom:8px">
            <a href="#" id="logoutBtn" class="nav-link">Logout</a>
        </div>
    </nav>
</aside>
