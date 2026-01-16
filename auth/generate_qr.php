<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../phpqrcode/qrlib.php'; 

$event_id = (int)($_GET['event_id'] ?? 0);
if ($event_id <= 0) die("Invalid event ID.");


$stmt = $conn->prepare("SELECT id, title FROM events WHERE id=? LIMIT 1");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$event) die("Event not found.");


$res = $conn->prepare("SELECT id, first_name, last_name FROM kk_members ORDER BY last_name ASC");
$res->execute();
$participants = $res->get_result()->fetch_all(MYSQLI_ASSOC);
$res->close();


echo "<h2>QR Codes for Event: " . htmlspecialchars($event['title']) . "</h2>";
echo "<div style='display:flex; flex-wrap:wrap; gap:20px;'>";

foreach ($participants as $p) {
    $member_id = $p['id'];
    $name = htmlspecialchars($p['first_name'] . ' ' . $p['last_name']);


    $qr_url = "http://localhost/youthdatasys/actions/scan.php?kk_member_id={$member_id}&event_id={$event_id}";

    echo "<div style='text-align:center; width:150px;'>";
    echo "<p>$name</p>";
    QRcode::png($qr_url, false, QR_ECLEVEL_L, 3);
    echo "</div>";
}

echo "</div>";
?>