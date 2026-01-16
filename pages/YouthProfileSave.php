<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'youth') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$email = $_SESSION['email'] ?? '';
if ($email === '') {
    echo json_encode(['success' => false, 'message' => 'No email']);
    exit;
}

/**
 * Allowed fields (security)
 */
$allowedFields = [
    'first_name', 'middle_name', 'last_name',
    'birth_date', 'age', 'gender', 'address',
    'barangay', 'municipality', 'phone',
    'status', 'school', 'course_grade', 'work'
];

$updates = [];
$params  = [];
$types   = '';

foreach ($allowedFields as $field) {
    if (isset($_POST[$field])) {
        $value = trim($_POST[$field]);

        // ❗ Skip empty values
        if ($value === '') {
            continue;
        }

        $updates[] = "{$field} = ?";
        $params[]  = $value;
        $types    .= 's';
    }
}

if (empty($updates)) {
    echo json_encode(['success' => true, 'message' => 'Nothing to update']);
    exit;
}

$sql = "UPDATE kk_members SET " . implode(', ', $updates) . " WHERE email = ?";
$params[] = $email;
$types   .= 's';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {

    // 🔄 Re-fetch updated profile
    $stmt2 = $conn->prepare("
        SELECT first_name, middle_name, last_name, email
        FROM kk_members
        WHERE email = ?
        LIMIT 1
    ");
    $stmt2->bind_param('s', $email);
    $stmt2->execute();
    $updated = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    // 🧠 Update SESSION values (for sidebar & header)
    $_SESSION['full_name'] = trim(
        ($updated['first_name'] ?? '') . ' ' .
        ($updated['middle_name'] ?? '') . ' ' .
        ($updated['last_name'] ?? '')
    );

    $_SESSION['email'] = $updated['email'];

    echo json_encode([
        'success' => true,
        'full_name' => $_SESSION['full_name']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}


$stmt->close();
