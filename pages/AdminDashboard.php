<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: Login.html");
    exit();
}

$fullname     = $_SESSION['full_name'] ?? 'Admin';
$firstLetter  = strtoupper(substr($fullname, 0, 1));
$municipality = trim($_SESSION['municipality'] ?? '');
$view         = $_GET['view'] ?? 'sk';
$youthCountStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM kk_members
    WHERE municipality = ?
");
$youthCountStmt->bind_param("s", $municipality);
$youthCountStmt->execute();
$totalYouth = $youthCountStmt->get_result()->fetch_assoc()['total'] ?? 0;
$skCountStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM sk_officials
    WHERE municipality = ?
");
$skCountStmt->bind_param("s", $municipality);
$skCountStmt->execute();
$totalSK = $skCountStmt->get_result()->fetch_assoc()['total'] ?? 0;
$youthStmt = $conn->prepare("
    SELECT first_name, last_name, email, barangay, municipality, gender
    FROM kk_members
    WHERE municipality = ?
    ORDER BY barangay, last_name
");
$youthStmt->bind_param("s", $municipality);
$youthStmt->execute();
$youthList = $youthStmt->get_result();

$skStmt = $conn->prepare("
    SELECT id, first_name, last_name, email, barangay, municipality, position, status, auth_file
    FROM sk_officials
    WHERE municipality = ?
    ORDER BY barangay, last_name
");
$skStmt->bind_param("s", $municipality);
$skStmt->execute();
$skList = $skStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_sidebar.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
  background:#f8f9fa;
  font-family: Inter, sans-serif;
  display:flex;
  min-height:100vh;
}
.main-content {
  margin-left:250px;
  padding:30px;
  width:calc(100% - 250px);
}
.card-stats {
  border-radius:12px;
  cursor:pointer;
  transition:.2s;
}
.card-stats:hover {
  transform:scale(1.03);
  box-shadow:0 4px 12px rgba(0,0,0,.15);
}
</style>
</head>

<body>

<?php include __DIR__ . '/../includes/AdminSidebar.php'; ?>

<main class="main-content">

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card p-3 card-stats shadow-sm" onclick="location='?view=youth'">
      <h6>Total Youth</h6>
      <h2><?= $totalYouth ?></h2>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card p-3 card-stats shadow-sm" onclick="location='?view=sk'">
      <h6>Total SK Officials</h6>
      <h2><?= $totalSK ?></h2>
    </div>
  </div>
</div>

<hr>

<?php if ($view === 'youth'): ?>

<div class="d-flex justify-content-between mb-2">
  <h4>Youth List (<?= htmlspecialchars($municipality) ?>)</h4>
  <a href="export_youth_admin.php" class="btn btn-success btn-sm">Export Excel</a>
</div>

<div class="table-responsive">
<table class="table table-striped">
<thead>
<tr>
  <th>Name</th>
  <th>Email</th>
  <th>Barangay</th>
  <th>Municipality</th>
  <th>Gender</th>
</tr>
</thead>
<tbody>

<?php if ($youthList->num_rows > 0): ?>
<?php while ($y = $youthList->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($y['first_name'].' '.$y['last_name']) ?></td>
  <td><?= htmlspecialchars($y['email']) ?></td>
  <td><?= htmlspecialchars($y['barangay']) ?></td>
  <td><?= htmlspecialchars($y['municipality']) ?></td>
  <td><?= htmlspecialchars($y['gender']) ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
  <td colspan="5" class="text-center text-muted">No youth records found.</td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>

<?php else: ?>

<h4>SK Officials List (<?= htmlspecialchars($municipality) ?>)</h4>

<div class="table-responsive">
<table class="table table-striped">
<thead>
<tr>
  <th>Name</th>
  <th>Email</th>
  <th>Barangay</th>
  <th>Position</th>
  <th>Status</th>
  <th>File</th>
  <th>Action</th>
</tr>
</thead>
<tbody>

<?php if ($skList->num_rows > 0): ?>
<?php while ($sk = $skList->fetch_assoc()): 
  $badge = $sk['status']==='approved'?'success':($sk['status']==='rejected'?'danger':'secondary');
?>
<tr>
  <td><?= htmlspecialchars($sk['first_name'].' '.$sk['last_name']) ?></td>
  <td><?= htmlspecialchars($sk['email']) ?></td>
  <td><?= htmlspecialchars($sk['barangay']) ?></td>
  <td><?= htmlspecialchars($sk['position']) ?></td>
  <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($sk['status']) ?></span></td>
  <td>
    <?php if ($sk['auth_file']): ?>
      <a href="../<?= ltrim($sk['auth_file'],'/') ?>" target="_blank" class="btn btn-sm btn-primary">View</a>
    <?php else: ?>
      <span class="text-muted">None</span>
    <?php endif; ?>
  </td>
  <td>
    <?php if ($sk['status']==='pending'): ?>
      <button class="btn btn-success btn-sm" onclick="confirmProcessSK(<?= $sk['id'] ?>,'approve')">Approve</button>
      <button class="btn btn-danger btn-sm" onclick="confirmProcessSK(<?= $sk['id'] ?>,'reject')">Reject</button>
    <?php else: ?> —
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="7" class="text-center text-muted">No SK officials found.</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>

<?php endif; ?>

</main>

<script>
async function processSK(id, action) {
  const fd = new FormData();
  fd.append('id', id);
  fd.append('action', action);

  const res = await fetch('../auth/ProcessSk.php',{method:'POST',body:fd});
  const r = await res.json();

  if (r.status==='success') {
    Swal.fire({icon:'success',title:'Success',text:r.message,timer:1500,showConfirmButton:false})
      .then(()=>location.reload());
  } else {
    Swal.fire('Error',r.message,'error');
  }
}
document.getElementById('logoutBtn').addEventListener('click', function(e) { e.preventDefault(); Swal.fire({ title: "Log Out?", text: "Are you sure you want to log out of your account?", icon: "warning", showCancelButton: true, confirmButtonColor: "#d33", cancelButtonColor: "#3085d6", confirmButtonText: "Log out", cancelButtonText: "Cancel" }).then((result) => { if (result.isConfirmed) window.location.href = "../auth/Logout.php"; }); });
function confirmProcessSK(id,action){
  Swal.fire({
    title:`${action.toUpperCase()} SK Official?`,
    icon:'question',
    showCancelButton:true,
    confirmButtonText:'Yes'
  }).then(r=>{
    if(r.isConfirmed) processSK(id,action);
  });
}
</script>

</body>
</html>
