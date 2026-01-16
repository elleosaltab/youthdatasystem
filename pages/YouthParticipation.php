<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'youth') {
    header("Location: ../pages/Login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email FROM kk_members WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$sql = "
    SELECT 
        e.title AS event_name, 
        e.start_date, 
        e.end_date, 
        ep.registered_at AS participation_date, 
        ep.status, 
        ep.certificate_path
    FROM event_participants ep
    JOIN events e ON e.id = ep.event_id
    WHERE ep.kk_member_id = ?
    ORDER BY ep.registered_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$participations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Participation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
<?= file_get_contents(__DIR__ . '/../assets/css/dashboard.css') ?>

body {
    font-family: 'Inter', sans-serif;
}
.table thead {
    background: linear-gradient(90deg, #0066ff, #0099ff);
    color: white;
}
.table tbody tr {
    transition: all 0.2s ease-in-out;
}
.table tbody tr:hover {
    background-color: #f4f9ff;
    transform: scale(1.01);
}
.table th, .table td {
    vertical-align: middle !important;
}
.badge {
    font-size: 0.9rem;
    padding: 0.45em 0.7em;
}
.certificate-link {
    text-decoration: none;
    font-weight: 600;
}
.certificate-link i {
    font-size: 1.2rem;
}
.certificate-link.view {
    color: #007bff;
}
.certificate-link.download {
    color: #28a745;
}
.certificate-link:hover {
    opacity: 0.8;
}
.table-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    padding: 1.5rem;
}
.topbar h3 {
    font-weight: 700;
}
</style>
</head>
<body>

<?php include __DIR__ . '/../includes/YouthSidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h3 style="margin-left:1rem; color:#333;">My Participation History</h3>
    </div>

    <div class="container mt-4">
        <?php if (empty($participations)): ?>
            <div class="alert alert-info text-center shadow-sm rounded-3">
                You haven’t joined any events yet.
            </div>
        <?php else: ?>
        <div class="table-card">
            <div class="table-responsive">
                <table class="table align-middle table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Joined On</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participations as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['event_name']) ?></td>
                                <td><?= htmlspecialchars($p['start_date']) ?></td>
                                <td><?= htmlspecialchars($p['end_date']) ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($p['participation_date']))) ?></td>
                                <td>
                                    <?php if (strtolower($p['status']) === 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif (strtolower($p['status']) === 'attended'): ?>
                                        <span class="badge bg-primary">Attended</span>
                                    <?php elseif (strtolower($p['status']) === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($p['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
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
</script>
</body>
</html>
