<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: Login.html");
    exit();
}

$filter_municipality = $_GET['municipality'] ?? '';
$filter_status = $_GET['status'] ?? '';
$sql = "SELECT id, municipality, report_data, created_at, status, file_path FROM municipality_reports WHERE 1=1";
$params = [];
$types = '';

if ($filter_municipality) {
    $sql .= " AND municipality = ?";
    $params[] = $filter_municipality;
    $types .= 's';
}

if ($filter_status) {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if (isset($_GET['download']) && $_GET['download'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ssa_consolidated.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID','Municipality','Report Data','Status','File Path','Date Submitted']);

    $stmt->execute();
    $download_result = $stmt->get_result();
    while($row = $download_result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['municipality'],
            $row['report_data'],
            $row['status'],
            $row['file_path'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit();
}

$muni_result = $conn->query("SELECT DISTINCT municipality FROM municipality_reports ORDER BY municipality ASC");
$status_result = $conn->query("SELECT DISTINCT status FROM municipality_reports ORDER BY status ASC");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Consolidated Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_sidebar.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { font-family: 'Inter', sans-serif; background: #f8f9fa; display: flex; min-height: 100vh; }
.main-content { margin-left: 250px; width: calc(100% - 250px); padding: 30px; }
.table-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
</style>
</head>

<body>
<?php include __DIR__ . '/../includes/SuperAdminSidebar.php'; ?>

<main class="main-content">
    <h2>Consolidated Report</h2>

    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-4">
            <select name="municipality" class="form-select">
                <option value="">-- All Municipalities --</option>
                <?php while($m = $muni_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($m['municipality']) ?>" <?= ($filter_municipality == $m['municipality']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['municipality']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select">
                <option value="">-- All Status --</option>
                <?php while($s = $status_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($s['status']) ?>" <?= ($filter_status == $s['status']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['status']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="?download=csv<?= ($filter_municipality ? "&municipality=$filter_municipality" : '') . ($filter_status ? "&status=$filter_status" : '') ?>" class="btn btn-success">Download CSV</a>
        </div>
    </form>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Municipality</th>
                        <th>Report Data</th>
                        <th>Status</th>
                        <th>File</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['municipality']) ?></td>
                                <td><?= htmlspecialchars($row['report_data']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td>
                                    <?php if ($row['file_path']): ?>
                                        <a href="generated/municipal_reports/<?= htmlspecialchars($row['file_path']) ?>" target="_blank">View File</a>
                                    <?php else: ?>
                                     -
                                    <?php endif; ?>
                                </td>
                                <td><?= date("F d, Y h:i A", strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">No reports available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
<script>
document.getElementById('logoutBtn').addEventListener('click', function(e) {
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
    if (result.isConfirmed) window.location.href = "../auth/Logout.php";
  });
});
</script>
</html>
