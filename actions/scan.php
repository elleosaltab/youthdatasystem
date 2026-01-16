<?php 
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';


if (!isset($_SESSION['role'])) {
    die("You must be logged in to scan this QR.");
}

$user_role       = $_SESSION['role'];
$user_id         = $_SESSION['user_id'] ?? 0;
$user_barangay   = $_SESSION['barangay'] ?? '';
$user_municipality = $_SESSION['municipality'] ?? '';


if ($user_role === 'youth') {
    $kk_member_id = $_SESSION['kk_members_id'] ?? 0;
    if (!$kk_member_id) die("Youth session invalid. Please re-login.");
} elseif (in_array($user_role, ['admin', 'sk'])) {
    if (!isset($_GET['kk_members_id']) || !is_numeric($_GET['kk_members_id'])) {
        die("Invalid KK member ID from QR scan.");
    }
    $kk_member_id = (int)$_GET['kk_members_id'];
} else {
    die("Unauthorized role.");
}


if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    die("No valid event selected.");
}
$event_id = (int)$_GET['event_id'];


$stmt = $conn->prepare("SELECT id, title, level, barangay, municipality, sk_id 
                        FROM events 
                        WHERE id=? LIMIT 1");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) die("Event not found.");


if ($user_role === 'admin' && $event['level'] === 'barangay') {
    die("Admin cannot scan attendance for barangay-level events.");
}

if ($user_role === 'sk') {
    if ($event['level'] === 'municipal' && $event['municipality'] !== $user_municipality) {
        die("You cannot scan participants outside your municipality.");
    }
    if ($event['level'] === 'barangay' && $event['barangay'] !== $user_barangay) {
        die("You cannot scan participants outside your barangay.");
    }
    if ($event['level'] === 'sk' && (int)$event['sk_id'] !== $user_id) {
        die("You cannot scan participants of events created by other SK users.");
    }
}


$stmt = $conn->prepare("SELECT id, barangay, municipality 
                        FROM kk_members 
                        WHERE id=? LIMIT 1");
$stmt->bind_param("i", $kk_member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$member) die("Invalid KK member ID.");


if ($user_role === 'sk') {
    if ($event['level'] === 'municipal' && $member['municipality'] !== $user_municipality) {
        die("Cannot mark attendance for participant outside your municipality.");
    }
    if (in_array($event['level'], ['barangay','sk']) && $member['barangay'] !== $user_barangay) {
        die("Cannot mark attendance for participant outside your barangay.");
    }
}

$stmt = $conn->prepare("SELECT id, status, time_in, time_out 
                        FROM event_participants 
                        WHERE event_id=? AND kk_member_id=? LIMIT 1");
$stmt->bind_param("ii", $event_id, $kk_member_id);
$stmt->execute();
$participant = $stmt->get_result()->fetch_assoc();
$stmt->close();


if (!$participant) {

    $stmt = $conn->prepare("INSERT INTO event_participants 
                            (event_id, kk_member_id, registered_at, status, time_in) 
                            VALUES (?, ?, NOW(), 'attended', NOW())");
    $stmt->bind_param("ii", $event_id, $kk_member_id);
    $stmt->execute();
    $stmt->close();
    $message = "You have successfully checked in for '{$event['title']}'.";
} else {
    if (empty($participant['time_in'])) {
  
        $stmt = $conn->prepare("UPDATE event_participants 
                                SET time_in=NOW(), status='attended' 
                                WHERE id=?");
        $stmt->bind_param("i", $participant['id']);
        $stmt->execute();
        $stmt->close();
        $message = "You have successfully checked in for '{$event['title']}'.";
    } elseif (empty($participant['time_out'])) {
        
        $stmt = $conn->prepare("UPDATE event_participants 
                                SET time_out=NOW() 
                                WHERE id=?");
        $stmt->bind_param("i", $participant['id']);
        $stmt->execute();
        $stmt->close();
        $message = "You have successfully checked out from '{$event['title']}'.";
    } else {
       
        $message = "You have already checked in and out for '{$event['title']}'.";
    }
}
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => $message
]);
exit;

