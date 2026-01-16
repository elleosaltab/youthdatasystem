<?php 
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: Login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>System Settings | Super Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/superadmin_sidebar.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* Enhance content spacing */
.main-content {
  margin-left: 250px;
  padding: 30px;
  min-height: 100vh;
  background: #f8fafc;
}
.settings-card {
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  max-width: 650px;
}
</style>
</head>

<body>
<?php include __DIR__ . '/../includes/SuperAdminSidebar.php'; ?>

<main class="main-content">
  <div class="container-fluid">
    <h3 class="fw-bold mb-3">⚙️ System Settings</h3>
    <p class="text-muted mb-4">Configure and manage system-wide preferences for <strong>YouthDataSys</strong>.</p>

    <div class="settings-card">
      <form>
        <div class="mb-3">
          <label class="form-label fw-semibold">System Name</label>
          <input type="text" class="form-control" value="Youth Data System">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Maintenance Mode</label>
          <select class="form-select">
            <option selected>Disabled</option>
            <option>Enabled</option>
          </select>
          <small class="text-muted">When enabled, users will temporarily lose access while maintenance is ongoing.</small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Email Notifications</label>
          <select class="form-select">
            <option selected>Enabled</option>
            <option>Disabled</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Data Auto-Backup</label>
          <select class="form-select">
            <option selected>Daily</option>
            <option>Weekly</option>
            <option>Monthly</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary px-4">💾 Save Changes</button>
      </form>
    </div>
  </div>
</main>

<script>
document.getElementById('logoutBtn').addEventListener('click', function() {
  Swal.fire({
    title: 'Logout?',
    text: 'Are you sure you want to log out?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Logout',
  }).then(result => {
    if (result.isConfirmed) window.location.href = '../auth/Logout.php';
  });
});
</script>
</body>
</html>
