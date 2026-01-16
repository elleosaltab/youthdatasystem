<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity_id = (int)($_POST['activity_id'] ?? 0);
    $rating      = (int)($_POST['rating'] ?? 0);
    $feedback    = trim($_POST['feedback'] ?? '');

    if ($activity_id <= 0 || $rating < 1 || $rating > 5) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
        exit;
    }

    $stmtCheck = $conn->prepare("SELECT 1 FROM event_ratings WHERE event_id=? AND kk_member_id=? LIMIT 1");
    $stmtCheck->bind_param("ii", $activity_id, $user_id);
    $stmtCheck->execute();
    $alreadyRated = $stmtCheck->get_result()->num_rows > 0;
    $stmtCheck->close();

    if ($alreadyRated) {
        echo json_encode(['status' => 'error', 'message' => 'You already rated this event.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO event_ratings (event_id, kk_member_id, rating, feedback, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiis", $activity_id, $user_id, $rating, $feedback);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Rating submitted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit rating.']);
    }
    $stmt->close();
    exit;
}

$type = $_GET['type'] ?? '';