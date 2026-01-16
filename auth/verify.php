<?php

session_start();
require_once __DIR__ . '/../config/db.php';

$code = $_GET['code'] ?? '';

if (!$code) {
    exit('Invalid verification link.');
}

$stmt = $conn->prepare("SELECT id FROM users WHERE verify_code = ? AND is_verified = 0 LIMIT 1");
$stmt->bind_param('s', $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    exit('Invalid or expired verification link.');
}

$user = $result->fetch_assoc();
$user_id = $user['id'];


$update = $conn->prepare("UPDATE users SET is_verified = 1, verify_code = NULL WHERE id = ?");
$update->bind_param('i', $user_id);
$update->execute();

if ($update->affected_rows === 1) {
    echo "Your email has been verified. You can now <a href='../Login.html'>login</a>.";
} else {
    echo "Something went wrong. Please try again.";
}

$stmt->close();
$update->close();
$conn->close();
