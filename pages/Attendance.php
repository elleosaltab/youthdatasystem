<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','sk'])) {
    header("Location: Login.html");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$user_barangay = $_SESSION['barangay'] ?? '';
$user_municipality = $_SESSION['municipality'] ?? '';
$event_id = (int)($_GET['event_id'] ?? 0);


$events = [];
$event = null;
$participants = [];
$attendance = [];
$summary = [
    'total_registered' => 0,
    'total_attended' => 0
];
$event_start_time = null;


$res = $conn->query("
    SELECT id, title, level, barangay, municipality, sk_id
    FROM events
    WHERE
        (level = 'sk' AND sk_id = $user_id)
        OR (level = 'barangay' AND barangay = '$user_barangay')
        OR (level = 'municipal' AND municipality = '$user_municipality')
        OR (level = 'admin' AND admin_id = $user_id)
    ORDER BY start_date DESC
");

while ($row = $res->fetch_assoc()) {
    $events[] = $row;
}


if ($event_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($event) {
      
        if (($event['level'] === 'sk' && $event['sk_id'] != $user_id) ||
            ($event['level'] === 'barangay' && $event['barangay'] != $user_barangay) ||
            ($event['level'] === 'municipal' && $event['municipality'] != $user_municipality) ||
            ($event['level'] === 'admin' && $event['admin_id'] != $user_id)) {
            $event = null;
        } else {
         
            $event_start_time = strtotime($event['start_date'] . ' ' . $event['start_time']);

            $query = "SELECT km.*, ep.registered_at, ep.status, ep.time_in, ep.time_out 
                      FROM event_participants ep 
                      JOIN kk_members km ON ep.kk_member_id = km.id
                      WHERE ep.event_id=?";
            $params = [$event_id];
            $types = "i";

            if ($role === 'sk') {
                if ($event['level'] === 'municipal') {
                    $query .= " AND km.municipality = ?";
                    $types .= "s"; $params[] = $user_municipality;
                } elseif (in_array($event['level'], ['barangay', 'sk'])) {
                    $query .= " AND km.barangay = ?";
                    $types .= "s"; $params[] = $user_barangay;
                }
            }
            $query .= " ORDER BY ep.registered_at ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $summary['total_registered'] = count($participants);
            $summary['total_attended'] = count(array_filter($participants, function($p){
                return $p['status'] === 'attended';
            }));
            $stmt = $conn->prepare("SELECT km.first_name, km.last_name, ep.time_in, ep.time_out, ep.status 
                                    FROM event_participants ep 
                                    JOIN kk_members km ON ep.kk_member_id = km.id
                                    WHERE ep.event_id=?");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $feedbacks = [];
            $stmt = $conn->prepare("
                SELECT 
                    f.id,
                    f.kk_member_id,
                    f.rating,
                    f.feedback,
                    f.created_at
                FROM event_ratings f
                WHERE f.event_id=?
                ORDER BY f.created_at DESC
            ");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance & Participants</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}

.layout-wrapper {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 220px;
    background: #1e1e2f;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 1000;
    transition: transform 0.3s ease;
}

.main-content {
    margin-left: 220px;
    width: calc(100% - 220px);
    min-height: 100vh;
    background: #f4f6f9;
    padding: 1rem;
    transition: margin-left 0.3s ease;
}

@media (max-width: 768px) {

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
        width: 100%;
    }

    .mobile-toggle {
        display: block;
    }
}

.mobile-toggle {
    display: none;
}


</style>
<?php if ($role === 'admin'): ?>
<link rel="stylesheet" href="../assets/css/admin_sidebar.css">
<?php elseif ($role === 'sk'): ?>
<link rel="stylesheet" href="../assets/css/sk_sidebar.css">
<?php endif; ?>
</head>
<body>
<div class="layout-wrapper">

    <div class="sidebar">
        <?php if ($role === 'sk'): ?>
            <?php include __DIR__ . '/../includes/SkSidebar.php'; ?>
        <?php else: ?>
            <?php include __DIR__ . '/../includes/AdminSidebar.php'; ?>
        <?php endif; ?>
    </div>

    <button class="btn btn-dark mobile-toggle mb-3" id="toggleSidebar">
    ☰ Menu
</button>

    <div class="main-content p-4">

        <h2 class="mb-4">Attendance & Participants</h2>

<form method="get" class="mb-3">
    <label for="event_id" class="form-label">Select Activity:</label>
    <select name="event_id" id="event_id" class="form-select" onchange="this.form.submit()">
        <option value="">Choose Event</option>
        <?php foreach ($events as $e): ?>
            <option value="<?= $e['id'] ?>" <?= ($event_id == $e['id'] ? 'selected' : '') ?>>
                <?= htmlspecialchars($e['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($event): ?>

<h5 class="mt-4">QR Attendance Scanner</h5>
<select id="cameraList" class="form-select w-50 mb-2"></select>
<div id="reader" style="width:400px; max-width:100%;"></div>
<p class="mt-2">Scanned Result: <span id="scan-result" class="fw-bold text-success"></span></p>
<script src="../node_modules/html5-qrcode/html5-qrcode.min.js"></script>
<script>
let scanner = new Html5Qrcode("reader");
Html5Qrcode.getCameras().then(devices => {
    if (devices && devices.length) {
        let select = document.getElementById("cameraList");
        devices.forEach((device,i)=>{
            let option = document.createElement("option");
            option.value=device.id;
            option.text=device.label||`Camera ${i+1}`;
            select.appendChild(option);
        });
        startScanner(devices[0].id);
        select.addEventListener("change", e=>{
            scanner.stop().then(()=>startScanner(e.target.value));
        });
    }
}).catch(err=>console.error("Camera error:",err));
function startScanner(cameraId){
    scanner.start({deviceId:{exact:cameraId}}, {fps:10, qrbox:250}, onScanSuccess).catch(err=>console.error("Start error:",err));
}
function onScanSuccess(decodedText){
    document.getElementById("scan-result").innerText = decodedText;

    if(decodedText.includes("scan.php")) {
      
        fetch(decodedText)
        .then(res => res.text()) 
        .then(data => {
            Swal.fire({
                title: 'Attendance Update',
                html: data,
                icon: 'success',
                confirmButtonText: 'OK'
            });
            
            refreshAttendanceTable();
            refreshParticipantsTable();
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                title: 'Error',
                text: 'Failed to mark attendance. Try again.',
                icon: 'error'
            });
        });
    }
}

</script>

<div class="card mb-4">
<div class="card-body">
<h4><?= htmlspecialchars($event['title']) ?></h4>
<p><?= htmlspecialchars($event['start_date'].' '.$event['start_time'].' - '.$event['end_date'].' '.$event['end_time'].' | '.$event['venue']) ?></p>
<div class="summary-box">
    <div><strong>Total Registered</strong><br><?= $summary['total_registered'] ?? 0 ?></div>
    <div><strong>Total Attended</strong><br><?= $summary['total_attended'] ?? 0 ?></div>
</div>
</div>
</div>
<div class="table-container">
<h4>Registered Participants</h4>
<?php if (!empty($participants)): ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">

<thead class="table-light">
<tr><th>#</th><th>Name</th><th>Email</th><th>Barangay</th><th>Municipality</th><th>Joined At</th><th>Status</th></tr>
</thead>
<tbody>
<?php $i=1; foreach($participants as $p): ?>
<tr class="<?= ($p['status']==='attended'&&!$p['time_out'])?'not-checked-out':'' ?>">
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></td>
<td><?= htmlspecialchars($p['email']) ?></td>
<td><?= htmlspecialchars($p['barangay']) ?></td>
<td><?= htmlspecialchars($p['municipality']) ?></td>
<td><?= htmlspecialchars($p['registered_at']) ?></td>
<td><?= htmlspecialchars($p['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>No participants found.</p>
<?php endif; ?>
</div>

<div class="table-container">
<h4 class="mt-5">Attendance Sheet</h4>
<?php if (!empty($attendance)): ?>
    <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">

<thead class="table-light"><tr><th>#</th><th>Name</th><th>Time In</th><th>Time Out</th><th>Status</th></tr></thead>
<tbody>
<?php $i=1; foreach($attendance as $a):
$time_in_val = $a['time_in']?strtotime($a['time_in']):null;
$late_class = ($time_in_val && $time_in_val>$event_start_time)?'late':'';
$not_out_class = ($a['time_in']&&!$a['time_out'])?'not-checked-out':'';
?>
<tr>
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></td>
<td><?= $a['time_in']?:'-' ?></td>
<td><?= $a['time_out']?:'-' ?></td>
<td><?= htmlspecialchars($a['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>No attendance records found.</p>
<?php endif; ?>
</div>

<?php else: ?>
<p>Please select an event to view participants and attendance.</p>
<?php endif; ?>

<div class="table-container mt-5">
    <h4>Event Ratings & Feedback</h4>

    <?php if (!empty($feedbacks)): ?>
        <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">

                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($feedbacks as $f): ?>
                        <tr>
                            <td><?= $i++ ?></td>

                            <td>
                                <?php
                                    $rating = (int)$f['rating'];
                                    echo $rating > 0 
                                        ? str_repeat('⭐', $rating)
                                        : '<span class="text-muted">No rating</span>';
                                ?>
                            </td>

                            <td><?= htmlspecialchars($f['feedback'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($f['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No feedback submitted yet.</p>
    <?php endif; ?>
</div>

<script>
    function onScanSuccess(decodedText){
    document.getElementById("scan-result").innerText = decodedText;

    if(decodedText.includes("scan.php")) {
        fetch(decodedText)
        .then(res => res.json())
        .then(data => {
            Swal.fire({
                title: 'Attendance Update',
                text: data.message,
                icon: data.status,
                confirmButtonText: 'OK'
            });
            refreshAttendanceTable();
            refreshParticipantsTable();
        })
        .catch(err => console.error(err));
    }
}



function refreshAttendanceTable(){
    const event_id = document.getElementById('event_id').value;
    fetch(`AttendanceTablePartial.php?event_id=${event_id}`)
    .then(res => res.text())
    .then(html => {
        document.getElementById('attendance-table-container').innerHTML = html;
    });
}

function refreshParticipantsTable(){
    const event_id = document.getElementById('event_id').value;
    fetch(`ParticipantsTablePartial.php?event_id=${event_id}`)
    .then(res => res.text())
    .then(html => {
        document.getElementById('participants-table-container').innerHTML = html;
    });
}

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


document.getElementById("toggleSidebar").addEventListener("click", function () {
    document.querySelector(".sidebar").classList.toggle("active");
});


</script>
</body>
</html>