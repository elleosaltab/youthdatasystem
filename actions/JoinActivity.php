<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role'], $_SESSION['user_id']) || $_SESSION['role'] !== 'youth') {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as youth.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$activity_id = isset($_POST['activity_id']) ? (int)$_POST['activity_id'] : 0;

if ($activity_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid activity ID.']);
    exit;
}

$stmtCheck = $conn->prepare("SELECT 1 FROM event_participants WHERE event_id=? AND kk_member_id=? LIMIT 1");
$stmtCheck->bind_param("ii", $activity_id, $user_id);
$stmtCheck->execute();
$isRegistered = $stmtCheck->get_result()->num_rows > 0;
$stmtCheck->close();

if ($isRegistered) {
    echo json_encode(['status' => 'error', 'message' => 'You are already registered for this activity.']);
    exit;
}

$stmtMax = $conn->prepare("SELECT max_participants FROM events WHERE id=? LIMIT 1");
$stmtMax->bind_param("i", $activity_id);
$stmtMax->execute();
$resultMax = $stmtMax->get_result()->fetch_assoc();
$stmtMax->close();

$maxParticipants = $resultMax['max_participants'] ?? 0;

$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM event_participants WHERE event_id=?");
$stmtCount->bind_param("i", $activity_id);
$stmtCount->execute();
$countResult = $stmtCount->get_result()->fetch_assoc();
$currentParticipants = $countResult['total'] ?? 0;
$stmtCount->close();

if ($maxParticipants > 0 && $currentParticipants >= $maxParticipants) {
    echo json_encode(['status' => 'error', 'message' => 'This activity has reached maximum participants.']);
    exit;
}

$stmtInsert = $conn->prepare("INSERT INTO event_participants (event_id, kk_member_id, status, registered_at) VALUES (?, ?, 'registered', NOW())");
$stmtInsert->bind_param("ii", $activity_id, $user_id);

if ($stmtInsert->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Successfully joined the activity.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to join activity. Please try again.']);
}

$stmtInsert->close();
$conn->close();
