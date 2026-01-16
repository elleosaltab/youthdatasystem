<?php  
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.html");
    exit();
}

$municipality = $_SESSION['municipality'] ?? 'Unknown';
$reportFolder = __DIR__ . '/generated/municipal_reports/';
$reports = [];
function getAllCsvReports(string $dir, string $basePath = ''): array {
    $result = [];
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
        $relativePath = ltrim($basePath . '/' . $file, '/');

        if (is_dir($fullPath)) {
            $result = array_merge($result, getAllCsvReports($fullPath, $relativePath));
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
            $result[] = $relativePath;
        }
    }
    return $result;
}

if (is_dir($reportFolder)) {
    $reports = getAllCsvReports($reportFolder);
}

 
$totalMembers = $totalEvents = $totalBarangays = 0;

$memberQuery = $conn->prepare("SELECT COUNT(*) AS total FROM kk_members WHERE municipality = ?");
$memberQuery->bind_param("s", $municipality);
$memberQuery->execute();
$totalMembers = $memberQuery->get_result()->fetch_assoc()['total'] ?? 0;

$eventQuery = $conn->prepare("SELECT COUNT(*) AS total FROM events WHERE municipality = ?");
$eventQuery->bind_param("s", $municipality);
$eventQuery->execute();
$totalEvents = $eventQuery->get_result()->fetch_assoc()['total'] ?? 0;

$barangayQuery = $conn->prepare("SELECT COUNT(DISTINCT barangay) AS total FROM kk_members WHERE municipality = ?");
$barangayQuery->bind_param("s", $municipality);
$barangayQuery->execute();
$totalBarangays = $barangayQuery->get_result()->fetch_assoc()['total'] ?? 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_report'])) {

    $summary = json_encode([
        'municipality' => $municipality,
        'total_barangays' => $totalBarangays,
        'total_members' => $totalMembers,
        'total_events' => $totalEvents,
        'sent_at' => date('Y-m-d H:i:s')
    ]);

    if (!is_dir($reportFolder)) mkdir($reportFolder, 0777, true);
    $fileName = 'municipal_report_' . strtolower(str_replace(' ', '_', $municipality)) . '_' . time() . '.csv';
    $filePath = $reportFolder . $fileName;

    $csv = fopen($filePath, 'w');
    fputcsv($csv, ['Municipality', 'Total Barangays', 'Total Members', 'Total Events', 'Sent At']);
    fputcsv($csv, [$municipality, $totalBarangays, $totalMembers, $totalEvents, date('Y-m-d H:i:s')]);
    fclose($csv);

    $stmt = $conn->prepare("
        INSERT INTO municipality_reports (municipality, report_data, created_at, status, file_path)
        VALUES (?, ?, NOW(), 'pending', ?)
    ");
    $stmt->bind_param("sss", $municipality, $summary, $fileName);
    $stmt->execute();

    echo "<script>
        window.onload = () => Swal.fire({
            icon: 'success',
            title: 'Report Sent!',
            text: 'Municipal report successfully sent to Super Admin.',
            confirmButtonColor: '#3085d6'
        });
    </script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Reports - <?= htmlspecialchars($municipality) ?> Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="../assets/css/admin_sidebar.css">
<style>
body { background-color: #f9fafb; font-family: 'Inter', sans-serif; }
.main-content { margin-left: 250px; padding: 40px 30px; }
.card { border: none; border-radius: 16px; box-shadow: 0 6px 15px rgba(0,0,0,0.05); transition: 0.2s; }
.card:hover { transform: scale(1.01); }
.section-header { border-bottom: 3px solid #3b82f6; padding-bottom: 8px; margin-bottom: 20px; }
.table thead { background-color: #1e293b; color: #fff; }
.btn-success { background-color: #16a34a; border: none; }
.btn-success:hover { background-color: #15803d; }
</style>
</head>
<body>

<?php include __DIR__ . '/../includes/AdminSidebar.php'; ?>

<div class="main-content">

  <div class="card mb-5 p-4">
      <div class="section-header">
          <h2 class="text-primary fw-bold">🏛️ <?= htmlspecialchars($municipality) ?> Municipal Summary</h2>
      </div>
      <p class="text-muted mb-4">This report summarizes all barangay data and registered KK members for your municipality.</p>

      <div class="row text-center mb-4">
          <div class="col-md-4">
              <div class="p-3 bg-light rounded">
                  <h4 class="fw-bold text-dark"><?= $totalBarangays ?></h4>
                  <p class="text-muted mb-0">Total Barangays</p>
              </div>
          </div>
          <div class="col-md-4">
              <div class="p-3 bg-light rounded">
                  <h4 class="fw-bold text-dark"><?= $totalMembers ?></h4>
                  <p class="text-muted mb-0">Registered Members</p>
              </div>
          </div>
          <div class="col-md-4">
              <div class="p-3 bg-light rounded">
                  <h4 class="fw-bold text-dark"><?= $totalEvents ?></h4>
                  <p class="text-muted mb-0">Total Events</p>
              </div>
          </div>
      </div>

      <form method="POST" class="text-end">
          <button type="submit" name="send_report" class="btn btn-success px-4 py-2">
              📤 Send Municipal Report to Super Admin
          </button>
      </form>
  </div>

  <div class="card p-4">
      <div class="section-header">
          <h2 class="text-primary fw-bold">📊 Barangay Reports</h2>
      </div>
      <p class="text-muted mb-4">Below is the list of all generated barangay reports within your municipality.</p>

      <?php if (!empty($reports)): ?>
      <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle">
              <thead>
                  <tr>
                      <th>#</th>
                      <th>Barangay</th>
                      <th>Report File</th>
                      <th>Action</th>
                  </tr>
              </thead>
              <tbody>
              <?php 
              $i = 1;
              foreach ($reports as $file): 
                  $parts = explode('/', $file);
                  $barangay = count($parts) >= 2 ? ucfirst($parts[count($parts) - 2]) : 'Unknown';
              ?>
                  <tr>
                      <td><?= $i++ ?></td>
                      <td><?= htmlspecialchars($barangay) ?></td>
                      <td><?= htmlspecialchars(basename($file)) ?></td>
                      <td>
                          <a href="generated/municipal_reports/<?= htmlspecialchars($file) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                              📥 View / Download
                          </a>
                      </td>
                  </tr>
              <?php endforeach; ?>
              </tbody>
          </table>
      </div>
      <?php else: ?>
          <div class="alert alert-warning">No barangay reports found yet.</div>
      <?php endif; ?>
  </div>
</div>

</body>
</html>
