<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sk') {
    header("Location: ../Login.html");
    exit();
}

$municipality = $_SESSION['municipality'] ?? 'San Andres';
$barangay = $_SESSION['barangay'] ?? 'Unknown Barangay';
$municipality_safe = strtolower(preg_replace('/[^a-z0-9]+/i', '', $municipality));
$barangay_safe = strtolower(preg_replace('/[^a-z0-9]+/i', '', $barangay));

$reportFolder = __DIR__ . "/generated/{$municipality_safe}/{$barangay_safe}/";
if (!is_dir($reportFolder)) mkdir($reportFolder, 0777, true);

$youths = [];
$events = [];
$attendance_summary = [];
$reports_data = [];

try {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, barangay, municipality
        FROM users
        WHERE role='youth' AND municipality = ? AND barangay = ?
        ORDER BY first_name");
    $stmt->bind_param("ss", $municipality, $barangay);
    $stmt->execute();
    $youths = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $stmt = $conn->prepare("SELECT id, event_name, event_date, category, description
        FROM events
        WHERE municipality = ? AND barangay = ?
        ORDER BY event_date DESC");
    $stmt->bind_param("ss", $municipality, $barangay);
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $stmt = $conn->prepare("
        SELECT e.event_name, COUNT(a.id) AS total_attended
        FROM attendance a
        INNER JOIN events e ON a.event_id = e.id
        WHERE e.municipality = ? AND e.barangay = ? AND a.status = 'attended'
        GROUP BY e.id");
    $stmt->bind_param("ss", $municipality, $barangay);
    $stmt->execute();
    $attendance_summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $stmt = $conn->prepare("
        SELECT r.id, e.event_name, r.report_reason, r.report_status, r.created_at
        FROM reports r
        LEFT JOIN events e ON r.event_id = e.id
        WHERE e.municipality = ? AND e.barangay = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("ss", $municipality, $barangay);
    $stmt->execute();
    $reports_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $filename = "report_{$barangay_safe}_" . date('Ymd_His') . ".csv";
        $filepath = $reportFolder . $filename;
        $fp = fopen($filepath, 'w');
        fputcsv($fp, ["YOUTH LIST - {$barangay}, {$municipality}"]);
        fputcsv($fp, ['ID','First Name','Last Name','Email','Barangay','Municipality']);
        if (!empty($youths)) foreach ($youths as $y) fputcsv($fp, $y);
        else fputcsv($fp, ['No youth records found']);
        fputcsv($fp, []);
        fputcsv($fp, ["EVENTS - {$barangay}, {$municipality}"]);
        fputcsv($fp, ['Event Name','Event Date','Category','Description']);
        if (!empty($events)) foreach ($events as $e) fputcsv($fp, $e);
        else fputcsv($fp, ['No event records found']);
        fputcsv($fp, []);
        fputcsv($fp, ["ATTENDANCE SUMMARY (Attended Only)"]);
        fputcsv($fp, ['Event Name','Total Attended']);
        if (!empty($attendance_summary)) foreach ($attendance_summary as $a) fputcsv($fp, $a);
        else fputcsv($fp, ['No attendance data found']);
        fputcsv($fp, []);
        fputcsv($fp, ["REPORTS DATA"]);
        fputcsv($fp, ['ID', 'Event Name', 'Reason', 'Status', 'Created At']);
        if (!empty($reports_data)) foreach ($reports_data as $r) fputcsv($fp, $r);
        else fputcsv($fp, ['No reports found']);
        fputcsv($fp, []);
        fputcsv($fp, ["SUMMARY TOTALS"]);
        fputcsv($fp, ['Total Youth', count($youths)]);
        fputcsv($fp, ['Total Events', count($events)]);
        fputcsv($fp, ['Events with Attendance', count($attendance_summary)]);
        fputcsv($fp, ['Total Reports', count($reports_data)]);
        fclose($fp);

        echo json_encode([
            'status' => 'success',
            'message' => "Report generated successfully for {$barangay}, {$municipality}",
            'file' => "{$municipality_safe}/{$barangay_safe}/{$filename}",
            'summary' => [
                'total_youth' => count($youths),
                'total_events' => count($events),
                'total_attended_events' => count($attendance_summary),
                'total_reports' => count($reports_data)
            ]
        ]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Summary Report - <?= htmlspecialchars($barangay) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="../assets/css/sk_sidebar.css">
</head>
<style>
    .main-content {
    margin-left: 250px;
    padding: 20px;
}

    </style>
<body>
<?php include __DIR__ . '/../includes/SkSidebar.php'; ?>
<!-- Wrap main content with a div that avoids sidebar overlap -->
<div class="main-content" style="margin-left: 250px; padding: 20px;">
    <div class="card shadow p-4">
        <h2 class="text-primary mb-3">📊 Summary Report - <?= htmlspecialchars($barangay) ?>, <?= htmlspecialchars($municipality) ?></h2>
        <p class="text-muted">Live overview and downloadable summary report for your barangay.</p>

        <form id="reportForm" class="mb-3">
            <button type="submit" class="btn btn-primary">Generate & Download CSV Report</button>
        </form>

        <div id="downloadLink" class="mb-4"></div>

        <!-- SUMMARY TABLE -->
        <div class="mb-4">
            <h4 class="text-success">📈 Summary Overview</h4>
            <table class="table table-bordered w-50">
                <tbody>
                    <tr><th>Total Youth</th><td><?= count($youths) ?></td></tr>
                    <tr><th>Total Events</th><td><?= count($events) ?></td></tr>
                    <tr><th>Events with Attendance</th><td><?= count($attendance_summary) ?></td></tr>
                </tbody>
            </table>
        </div>

        <!-- YOUTH LIST -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">Youth List (<?= count($youths) ?>)</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Barangay</th></tr></thead>
                    <tbody>
                    <?php if (!empty($youths)): ?>
                        <?php foreach ($youths as $i => $y): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($y['first_name'].' '.$y['last_name']) ?></td>
                            <td><?= htmlspecialchars($y['email']) ?></td>
                            <td><?= htmlspecialchars($y['barangay']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">No youth records found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- EVENTS -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">Events (<?= count($events) ?>)</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>#</th><th>Event Name</th><th>Date</th><th>Category</th><th>Description</th></tr></thead>
                    <tbody>
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $i => $e): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($e['event_name']) ?></td>
                            <td><?= htmlspecialchars($e['event_date']) ?></td>
                            <td><?= htmlspecialchars($e['category']) ?></td>
                            <td><?= htmlspecialchars($e['description']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No event records found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ATTENDANCE SUMMARY -->
        <div class="card mb-4">
            <div class="card-header bg-warning">Attendance Summary (Attended Only)</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>#</th><th>Event Name</th><th>Total Attended</th></tr></thead>
                    <tbody>
                    <?php if (!empty($attendance_summary)): ?>
                        <?php foreach ($attendance_summary as $i => $a): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($a['event_name']) ?></td>
                            <td><?= htmlspecialchars($a['total_attended']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted">No attendance records found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<script>
document.getElementById('reportForm').addEventListener('submit', function(e){
    e.preventDefault();
    Swal.fire({ title: 'Generating...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('', { method:'POST' })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            if(data.status === 'success'){
                Swal.fire('✅ Success', data.message, 'success');
                document.getElementById('downloadLink').innerHTML =
                    '<a href="generated/'+data.file+'" download class="btn btn-success mt-2">📥 Download Report</a>';
            } else {
                Swal.fire('❌ Error', data.message, 'error');
            }
        })
        .catch(() => Swal.fire('❌ Error', 'Unexpected error occurred', 'error'));
});
</script>
</body>
</html>
