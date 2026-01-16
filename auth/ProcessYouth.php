<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function sendResponse(string $status, string $message): void {
    echo json_encode([
        'status' => $status,
        'message' => $message
    ]);
    exit;
}

function safeRollback(mysqli $conn): void {
    if ($conn->errno) {
        $conn->rollback();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Invalid request. Please use POST method.');
}

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin', 'sk'], true)) {
    sendResponse('error', 'Access denied. Admin or SK privileges required.');
}

$youth_id = intval($_POST['id'] ?? 0);
$action   = trim($_POST['action'] ?? '');

if ($youth_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    sendResponse('error', 'Invalid or missing data.');
}

try {
    if ($action === 'approve') {
        $conn->begin_transaction();

        $get = $conn->prepare("SELECT * FROM kk_members WHERE id = ? AND approval_status = 'pending' LIMIT 1 FOR UPDATE");
        $get->bind_param('i', $youth_id);
        $get->execute();
        $res = $get->get_result();

        if ($res->num_rows !== 1) {
            $get->close();
            safeRollback($conn);
            sendResponse('error', 'Youth not found or already processed.');
        }

        $youth = $res->fetch_assoc();
        $get->close();

        
        $upd = $conn->prepare("UPDATE kk_members 
            SET approval_status = 'approved', approved_at = NOW(), approved_by = ? 
            WHERE id = ?");
        $approved_by = $_SESSION['user_id'] ?? null;
        $upd->bind_param('ii', $approved_by, $youth_id);
        $upd->execute();
        $upd->close();

        $ins = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, barangay, municipality, role, is_approved, is_verified)
            VALUES (?, ?, ?, ?, ?, ?, 'youth', 1, 1)");
        $ins->bind_param(
            'ssssss',
            $youth['email'],
            $youth['password'],
            $youth['first_name'],
            $youth['last_name'],
            $youth['barangay'],
            $youth['municipality']
        );
        $ins->execute();
        $ins->close();

        $conn->commit();
        sendResponse('success', 'Youth approved successfully!');

    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE kk_members 
            SET approval_status='rejected', rejected_at = NOW() 
            WHERE id = ? AND approval_status='pending'");
        $stmt->bind_param('i', $youth_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            sendResponse('error', 'Youth not found or already processed.');
        }

        $stmt->close();
        sendResponse('success', 'Youth rejected successfully.');
    }

} catch (mysqli_sql_exception $e) {
    safeRollback($conn);
    sendResponse('error', 'A server error occurred: ' . $e->getMessage());
}
?>
