<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sk') {
    header("Location: ../pages/Login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $submitted_by = (int)$_SESSION['user_id'];

    if ($report_type && $title && $description) {
        $stmt = $conn->prepare("INSERT INTO reports (report_type, submitted_by, title, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $report_type, $submitted_by, $title, $description);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to submit report.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all fields.']);
    }
}
