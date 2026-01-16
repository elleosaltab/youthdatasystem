<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ACCESS CONTROL
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../auth/Login.html");
    exit();
}

// COUNT FUNCTION
function getCount($conn, $query) {
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['t'];
    }
    return 0;
}

// TOTAL COUNTS
$totalYouth  = getCount($conn, "SELECT COUNT(*) AS t FROM users WHERE role='youth'");
$totalSK     = getCount($conn, "SELECT COUNT(*) AS t FROM sk_officials");
$totalAdmins = getCount($conn, "SELECT COUNT(*) AS t FROM users WHERE role='admin'");

// VIEW FILTER
$view = $_GET['view'] ?? '';
$dataResult = null;

// LOAD DATA ONLY IF CLICKED
if ($view === 'youth') {
    $dataResult = $conn->query("
        SELECT id, first_name, last_name, email, municipality, barangay, created_at
        FROM users WHERE role='youth' ORDER BY created_at DESC
    ");
} elseif ($view === 'sk') {
    $dataResult = $conn->query("
        SELECT id, first_name, last_name, email, municipality, barangay, 'SK Official' AS role, NOW() AS created_at
        FROM sk_officials ORDER BY id DESC
    ");
} elseif ($view === 'admin') {
    $dataResult = $conn->query("
        SELECT id, first_name, last_name, email, municipality, barangay, created_at
        FROM users WHERE role='admin' ORDER BY created_at DESC
    ");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Superadmin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f5f6fa; font-family: Arial, sans-serif; display: flex; min-height: 100vh; margin: 0; }
        .main-content { flex: 1; padding: 30px; margin-left: 250px; }
        .dashboard-card {
            border-radius: 8px;
            padding: 20px;
            background: white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: 0.15s;
            text-align: center;
        }
        .dashboard-card:hover {
            transform: scale(1.03);
            box-shadow: 0 3px 10px rgba(0,0,0,0.18);
        }
        hr { margin: 2rem 0; }
        .table-responsive { margin-top: 20px; }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/SuperAdminSidebar.php'; ?>

    <div class="main-content">
        <h2 class="mb-4">Superadmin Dashboard</h2>

        <!-- STATS CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="dashboard-card" onclick="window.location='?view=youth'">
                    <h6>Total Youth</h6>
                    <h2><?= $totalYouth ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card" onclick="window.location='?view=sk'">
                    <h6>Total SK Officials</h6>
                    <h2><?= $totalSK ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card" onclick="window.location='?view=admin'">
                    <h6>Total Admins</h6>
                    <h2><?= $totalAdmins ?></h2>
                </div>
            </div>
        </div>

        <hr>

        <!-- PROMPT IF NO CLICK -->
        <?php if ($view === ''): ?>
            <p class="text-muted">Click any statistic above to view the list.</p>
        <?php endif; ?>

        <!-- TABLE (ONLY IF CLICKED) -->
        <?php if ($dataResult && $dataResult->num_rows > 0): ?>
            <h4 class="mb-3 text-capitalize"><?= $view ?> List</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Municipality</th>
                            <th>Barangay</th>
                            <th>Date Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $dataResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['municipality'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['barangay'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
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
