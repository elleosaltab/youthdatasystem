<?php 
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/events_helper.php';

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'sk') {
    header('Location: ../pages/Login.html');
    exit();
}

$sk_id = $_SESSION['user_id'];
$barangay = $_SESSION['barangay'];
$email = $_SESSION['email'] ?? 'SK User';
$sk_initial = strtoupper(substr($email, 0, 1));
$message = "";

$today = date('Y-m-d');
$searchRaw = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$search = '%' . $searchRaw . '%';


$activities = [];
if (!empty($barangay)) {

    $searchTerm = empty($searchRaw) ? '%' : '%' . $searchRaw . '%';

    $sql = "SELECT id, title, description, details, start_date, start_time, end_date, end_time,
                   venue, category, image, municipality, barangay, level
            FROM events
            WHERE 
            (
                (level = 'admin' AND barangay = ?)
                OR (level = 'municipal' AND municipality = ?)
            )
            AND start_date >= ?
            AND (title LIKE ? OR description LIKE ?)
            ORDER BY start_date ASC, start_time ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
      
        $stmt->bind_param("sssss", $barangay, $_SESSION['municipality'], $today, $searchTerm, $searchTerm);
        $stmt->execute();
        $res = $stmt->get_result();
        $activities = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SK Main Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="../assets/css/sk_sidebar.css">
<style>
<?= file_get_contents(__DIR__ . '/../assets/css/dashboard.css') ?>
.card {
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  margin-bottom: 20px;
}
.card-img {
  max-height: 220px;
  object-fit: cover;
  width: 100%;
  display: block;
}
.card-body h3 {
  font-size: 1.3rem;
  font-weight: 700;
}
.card-body p {
  margin-bottom: 8px;
}
.badge {
  font-size: 0.9rem;
}
</style>
</head>
<body>
   <?php include __DIR__ . '/../includes/SkSidebar.php'; ?>
<div class="main">
  <div class="topbar">
    <form method="GET" class="search-bar">
      <input type="text" name="search" placeholder="Search barangay activities..." 
            value="<?= htmlspecialchars($searchRaw) ?>">
      <button type="submit" class="btn btn-outline-primary">Search</button>
    </form>
  </div>

  <?php if (!empty($activities)): ?>
    <ul class="list-group">
      <?php foreach ($activities as $activity): ?>
        <?php $status = getEventStatus($activity); ?>
        <li class="list-group-item bg-white">
          <h5><?= htmlspecialchars((string)$activity['title']) ?></h5>
          <p><?= nl2br(htmlspecialchars((string)$activity['description'])) ?></p>
          <small class="text-muted">
            📅 <?= htmlspecialchars((string)$activity['start_date']) ?> → <?= htmlspecialchars((string)$activity['end_date']) ?><br>
            🕒 <?= htmlspecialchars((string)$activity['start_time']) ?> - <?= htmlspecialchars((string)$activity['end_time']) ?><br>
            📍 <?= htmlspecialchars((string)$activity['venue']) ?> 
          </small>
          <div class="mt-2">
            <span class="badge bg-warning"><?= htmlspecialchars((string)$activity['category']) ?></span>
            <span class="badge bg-secondary"><?= $status['status'] ?></span>
          </div>
          <form method="POST" action="../actions/join_activity.php" class="mt-2">
            <input type="hidden" name="activity_id" value="<?= (int)$activity['id'] ?>">
            <button type="submit" class="btn btn-primary btn-sm" <?= !$status['can_register'] ? 'disabled' : '' ?>>
              Join
            </button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="mt-3">No barangay activities found.</p>
  <?php endif; ?>
</div>
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
