<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

function clean(string $s): string {
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$first_name   = clean($_POST['first_name'] ?? '');
$last_name    = clean($_POST['last_name'] ?? '');
$municipality = clean($_POST['municipality'] ?? '');
$barangay     = clean($_POST['barangay'] ?? '');
$gender       = clean($_POST['gender'] ?? '');
$contact      = clean($_POST['contact'] ?? '');
$dob          = $_POST['dob'] ?? '';
$email        = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
$password     = $_POST['password'] ?? '';
$confirm      = $_POST['confirm_password'] ?? '';


if (!$first_name || !$last_name || !$municipality || !$gender || !$contact || !$dob || !$barangay) {
    exit('All required fields must be filled.');
}
if (!$email) {
    exit('Invalid email format.');
}
if ($password !== $confirm) {
    exit('Passwords do not match!');
}
if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/', $password)) {
    exit('Weak password. Must contain upper, lower, number, special char, and 8+ chars.');
}
if (!preg_match('/^09\d{9}$/', $contact)) {
    exit('Invalid Philippine contact number format.');
}


$check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->bind_param('s', $email);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $check->close();
    exit('Email already exists!');
}
$check->close();


$hashed_password = password_hash($password, PASSWORD_DEFAULT);


$stmt = $conn->prepare('INSERT INTO users 
    (password, email, first_name, last_name, municipality, barangay, gender, contact, dob, role, is_verified)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "youth", 0)'
);

$stmt->bind_param(
    'sssssssss',
    $hashed_password, $email, $first_name, $last_name,
    $municipality, $barangay, $gender, $contact, $dob
);

if ($stmt->execute()) {
  
    $verify_code = bin2hex(random_bytes(16));
    $user_id = $stmt->insert_id;

    $conn->query("UPDATE users SET verify_code='$verify_code' WHERE id=$user_id");

    
    $verify_link = 'http://' . $_SERVER['HTTP_HOST'] . '/youthdatasystem/auth/verify.php?code=' . $verify_code;

   
    mail($email, 'Youth Account Verification', "Please verify your Youth account: $verify_link");

    header('Location: /login.html?registered=1');
    exit;
} else {
    http_response_code(500);
    echo 'Error: ' . $stmt->error;
}

$stmt->close();
$conn->close();
