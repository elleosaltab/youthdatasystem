<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

function clean(string $s): string {
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

$first_name = clean($_POST['first_name'] ?? '');
$last_name  = clean($_POST['last_name'] ?? '');
$middle_name = clean($_POST['middle_name'] ?? ''); 
$dob        = $_POST['dob'] ?? null;
$barangay   = clean($_POST['barangay'] ?? '');
$municipality = clean($_POST['municipality'] ?? '');
$position   = clean($_POST['position'] ?? '');
$email      = strtolower(trim($_POST['email'] ?? ''));
$confirm_email = strtolower(trim($_POST['confirm_email'] ?? ''));
$password   = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$contact    = clean($_POST['contact'] ?? '');

$authFilePath = null;
if (!empty($_FILES['auth_file']['name'])) {
    $uploadDir = __DIR__ . '/../uploads/auth_files/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = time() . '_' . basename($_FILES['auth_file']['name']);
    $targetFile = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $fileSize = $_FILES['auth_file']['size'];

    $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed: PDF, JPG, JPEG, PNG.']);
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'File too large. Max 5MB allowed.']);
        exit;
    }

    if (!move_uploaded_file($_FILES['auth_file']['tmp_name'], $targetFile)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload file.']);
        exit;
    }

    $authFilePath = 'uploads/auth_files/' . $fileName;
}

$required = [$first_name, $last_name, $email, $confirm_email, $password, $confirm_password, $barangay, $municipality, $dob, $contact];
foreach ($required as $v) {
    if (empty($v)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
        exit;
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

if ($email !== $confirm_email) {
    echo json_encode(['status' => 'error', 'message' => 'Emails do not match.']);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/', $password)) {
    echo json_encode(['status' => 'error', 'message' => 'Weak password. Use upper, lower, number, special character, min 8 chars.']);
    exit;
}

if (!preg_match('/^09\d{9}$/', $contact)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid contact format. Must start with 09 and have 11 digits.']);
    exit;
}


$stmt = $conn->prepare("SELECT id FROM sk_officials WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'SK email already submitted.']);
    exit;
}
$stmt->close();


$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Email already registered as user.']);
    exit;
}
$stmt->close();

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $ins = $conn->prepare("
        INSERT INTO sk_officials 
        (first_name, last_name, middle_name, barangay, municipality, email, password, position, dob, contact, auth_file) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param(
        'sssssssssss',
        $first_name,
        $last_name,
        $middle_name, 
        $barangay,
        $municipality,
        $email,
        $hash,
        $position,
        $dob,
        $contact,
        $authFilePath
    );
    $ins->execute();
    $ins->close();

    echo json_encode(['status' => 'success', 'message' => 'SK registration successful! Wait for Approval from admin then Login.']);
} catch (mysqli_sql_exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error occurred. Please try again later.']);
}
?>
