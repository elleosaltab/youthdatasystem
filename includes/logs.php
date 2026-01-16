<?php
require_once __DIR__ . '/../config/db.php';

function logActivity($conn, $user_id, $user_name, $role, $action, $description) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_name, role, action, description, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $user_id, $user_name, $role, $action, $description, $ip);
    $stmt->execute();
    $stmt->close();
}
?>
