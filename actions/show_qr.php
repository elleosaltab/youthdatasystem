<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../phpqrcode/qrlib.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'youth') {
    header("Location: ../auth/login.php");
    exit();
}

$kk_member_id = $_SESSION['kk_members_id'] ?? 0;
if (!$kk_member_id) {
    die("No valid youth session. Please re-login.");
}

if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    die("Invalid or missing event ID.");
}
$event_id = (int)$_GET['event_id'];
$event_name = "Youth Event";
$stmt = $conn->prepare("SELECT title FROM events WHERE id=? LIMIT 1");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $event_name = $row['title'];
}
$stmt->close();
$attendance_url = "http://localhost/youthdatasys/actions/scan.php?kk_members_id={$kk_member_id}&event_id={$event_id}";
header('Content-Type: image/png');
QRcode::png($attendance_url, false, QR_ECLEVEL_H, 8, 2);
exit;
