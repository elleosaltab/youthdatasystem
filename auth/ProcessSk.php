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
    sendResponse('error', 'Invalid request. Please use the correct action.');
}

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    sendResponse('error', 'Access denied. Admin only can perform this action.');
}


$sk_id  = intval($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');

if ($sk_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    sendResponse('error', 'Invalid or missing data. Please refresh and try again.');
}

try {
    if ($action === 'approve') {
        $conn->begin_transaction();

    
        $get = $conn->prepare("
            SELECT * FROM sk_officials 
            WHERE id = ? AND status = 'pending' 
            LIMIT 1 FOR UPDATE
        ");
        $get->bind_param('i', $sk_id);
        $get->execute();
        $res = $get->get_result();

        if ($res->num_rows !== 1) {
            $get->close();
            safeRollback($conn);
            sendResponse('error', 'SK record not found or already processed.');
        }

        $sk = $res->fetch_assoc();
        $get->close();

        $upd = $conn->prepare("UPDATE sk_officials SET status = 'approved' WHERE id = ?");
        $upd->bind_param('i', $sk_id);
        $upd->execute();
        $upd->close();

        $ins = $conn->prepare("
            INSERT INTO users (email, password, first_name, last_name, barangay, municipality, role, is_approved, is_verified)
            VALUES (?, ?, ?, ?, ?, ?, 'sk', 1, 1)
        ");
        $ins->bind_param(
            'ssssss',
            $sk['email'],
            $sk['password'],
            $sk['first_name'],
            $sk['last_name'],
            $sk['barangay'],
            $sk['municipality']
        );
        $ins->execute();
        $ins->close();

        $conn->commit();
        sendResponse('success', 'SK official approved successfully!');

    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("
            UPDATE sk_officials 
            SET status='rejected' 
            WHERE id = ? AND status='pending'
        ");
        $stmt->bind_param('i', $sk_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            sendResponse('error', 'SK record not found or already processed.');
        }

        $stmt->close();
        sendResponse('success', 'SK official rejected successfully.');
    }

} catch (mysqli_sql_exception $e) {
    safeRollback($conn);
    sendResponse('error', 'A server error occurred. Please try again later.');
}
?>
