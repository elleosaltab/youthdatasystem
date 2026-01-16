<?php  
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: Login.html");
    exit();
}


$role = $_SESSION['role'];
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM audit_logs WHERE 1";
if ($search) $query .= " AND (user_name LIKE '%$search%' OR action LIKE '%$search%' OR description LIKE '%$search%')";
if ($role_filter) $query .= " AND role = '$role_filter'";
if ($from && $to) $query .= " AND DATE(created_at) BETWEEN '$from' AND '$to'";

$count_query = $conn->query($query);
$total_rows = $count_query->num_rows;
$total_pages = ceil($total_rows / $limit);

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Audit Logs - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php

if ($role === 'admin') {
    echo '<link rel="stylesheet" href="../assets/css/admin_sidebar.css">';
} elseif ($role === 'sk') {
    echo '<link rel="stylesheet" href="../assets/css/sk_sidebar.css">';
}
?>

<style>
body { font-family:'Inter', sans-serif; margin:0; display:flex; min-height:100vh; background:#f8f9fa; }

.main-content { flex:1; padding:20px 30px; margin-left:250px; }
.filters select {
    flex: 1;
    min-width: 150px;
    pointer-events: auto;
    z-index: 100;      
}

.filters { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; background:#fff; padding:15px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.05);}
.filters input, .filters select, .filters button, .filters a { height:42px; border-radius:8px; }
.filters input, .filters select { flex:1; min-width:150px; }
.filters button, .filters a { white-space:nowrap; }

.table-card { background:#fff; padding:15px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
.table th, .table td { vertical-align: middle; }
.table-striped>tbody>tr:nth-of-type(odd) { background-color:#f1f5f9; }
.table-hover>tbody>tr:hover { background-color:#e0f2fe; }
.badge-role { border-radius:8px; padding:5px 12px; font-weight:500; text-transform:capitalize; }
.badge-role.youth { background:#3b82f6; color:#fff; }
.badge-role.sk { background:#16a34a; color:#fff; }
.badge-role.admin { background:#7c3aed; color:#fff; }
.badge-role.pydo { background:#f59e0b; color:#fff; }
</style>
</head>
<body>


<div class="sidebar">
  <?php
  if ($role === 'sk') {
      include __DIR__ . '/../includes/SkSidebar.php';
  } else {
      include __DIR__ . '/../includes/AdminSidebar.php';
  }
  ?>
</div>


<main class="main-content">

<h2 class="mb-4"></i>Audit Logs</h2>
<form method="GET" class="filters mb-3" id="filterForm">
  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search logs..." class="form-control" id="searchInput">
  
  <select name="role" class="form-select" id="roleSelect">
    <option value="">All Roles</option>
    <option value="youth" <?= ($role_filter=='youth')?'selected':'' ?>>Youth</option>
    <option value="sk" <?= ($role_filter=='sk')?'selected':'' ?>>SK</option>
    <option value="admin" <?= ($role_filter=='admin')?'selected':'' ?>>Admin</option>
  </select>
  
  <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control" id="fromDate">
  <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control" id="toDate">
  
  <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
  <a href="AuditLogs.php" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
</form>


<div class="table-card">
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>User</th>
          <th>Role</th>
          <th>Action</th>
          <th>Description</th>
          <th>IP</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['user_name']) ?></td>
              <td><span class="badge badge-role <?= htmlspecialchars($row['role']) ?>"><?= htmlspecialchars($row['role']) ?></span></td>
              <td><?= htmlspecialchars($row['action']) ?></td>
              <td><?= htmlspecialchars($row['description']) ?></td>
              <td><?= htmlspecialchars($row['ip_address']) ?></td>
              <td><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center">No logs found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($total_pages > 1): ?>
  <div class="mt-3 text-center">
    <?php if ($page < $total_pages): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-outline-primary">
        <i class="bi bi-arrow-down-circle"></i> Load More
      </a>
    <?php else: ?>
      <p class="text-muted">No more logs to display.</p>
    <?php endif; ?>
  </div>
<?php endif; ?>

</main>
<script>
document.getElementById('logoutBtn').addEventListener('click', function (e) {
  e.preventDefault();
  Swal.fire({
    title: "Log Out?",
    text: "Are you sure you want to log out of your account?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Log out",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = "../auth/Logout.php";
    }
  });
});
document.getElementById('roleSelect').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

document.getElementById('fromDate').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

document.getElementById('toDate').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('filterForm').submit();
    }
});
</script>

</body>
</html>
