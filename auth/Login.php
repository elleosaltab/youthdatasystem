<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    exit;
}

function logAction(mysqli $conn, string $user_name, string $role, string $action, string $description): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_name, role, action, description, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssss', $user_name, $role, $action, $description, $ip);
    $stmt->execute();
    $stmt->close();
}

try {
    $stmt = $conn->prepare("SELECT id, email, password, role, is_approved, municipality 
        FROM users 
        WHERE LOWER(email) = LOWER(?) 
        LIMIT 1
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows !== 1) {
        logAction($conn, $email, 'unknown', 'Login Failed', 'Email not found');
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit;
    }

    $user = $res->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password'])) {
        logAction($conn, $email, $user['role'], 'Login Failed', 'Incorrect password');
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit;
    }

    switch ($user['role']) {
       case 'superadmin':
            if ((int)$user['is_approved'] !== 1) {
                echo json_encode(['status' => 'error', 'message' => 'Superadmin account not active.']);
                exit;
            }

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['role'] = 'superadmin';
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'] ?? 'Super Admin';
            $_SESSION['is_master_admin'] = true;

            logAction($conn, $user['email'], 'superadmin', 'Login', 'Superadmin logged in successfully');
            echo json_encode(['status' => 'success', 'redirect' => '../pages/SuperAdminDashboard.php']);
            exit;


        case 'admin':
            if ((int)$user['is_approved'] !== 1) {
                echo json_encode(['status' => 'error', 'message' => 'Admin account not active.']);
                exit;
            }

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['email'] = $user['email'];
            $_SESSION['municipality'] = $user['municipality']; 

            logAction($conn, $user['email'], 'admin', 'Login', 'Admin logged in successfully');
            echo json_encode(['status' => 'success', 'redirect' => '../pages/AdminDashboard.php']);
            exit;

        case 'sk':
            if ((int)$user['is_approved'] !== 1) {
                echo json_encode(['status' => 'error', 'message' => 'Your SK account is not approved yet.']);
                exit;
            }

            $stmt = $conn->prepare("
                SELECT id, first_name, last_name, barangay, municipality, position, email
                FROM sk_officials 
                WHERE email = ? 
                LIMIT 1
            ");
            $stmt->bind_param('s', $user['email']);
            $stmt->execute();
            $sk = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$sk) {
                echo json_encode(['status' => 'error', 'message' => 'SK profile not found.']);
                exit;
            }

            $_SESSION['user_id'] = (int)$sk['id'];
            $_SESSION['role'] = 'sk';
            $_SESSION['email'] = $sk['email'];
            $_SESSION['barangay'] = $sk['barangay'];
            $_SESSION['municipality'] = $sk['municipality'];
            $_SESSION['full_name'] = $sk['first_name'] . ' ' . $sk['last_name'];
            $_SESSION['position'] = $sk['position'];

            logAction($conn, $_SESSION['full_name'], 'sk', 'Login', 'SK Official logged in successfully');
            echo json_encode(['status' => 'success', 'redirect' => '../pages/SkDashboard.php']);
            exit;

        case 'youth':
            if ((int)$user['is_approved'] !== 1) {
                echo json_encode(['status' => 'error', 'message' => 'Your youth account is not approved yet.']);
                exit;
            }

            $stmt = $conn->prepare("
                SELECT id, first_name, middle_name, last_name, email, barangay, municipality, birth_date, phone, gender 
                FROM kk_members 
                WHERE email = ? 
                LIMIT 1
            ");
            $stmt->bind_param('s', $user['email']);
            $stmt->execute();
            $youth = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$youth) {
                echo json_encode(['status' => 'error', 'message' => 'Youth profile not found.']);
                exit;
            }

            $_SESSION['user_id'] = (int)$youth['id'];
            $_SESSION['kk_members_id'] = (int)$youth['id'];
            $_SESSION['role'] = 'youth';
            $_SESSION['email'] = $youth['email'];
            $_SESSION['barangay'] = $youth['barangay'];
            $_SESSION['municipality'] = $youth['municipality'];
            $_SESSION['full_name'] = $youth['first_name'] . ' ' . $youth['last_name'];
            $_SESSION['birth_date'] = $youth['birth_date'];
            $_SESSION['phone'] = $youth['phone'];
            $_SESSION['gender'] = $youth['gender'];

            logAction($conn, $_SESSION['full_name'], 'youth', 'Login', 'Youth logged in successfully');
            echo json_encode(['status' => 'success', 'redirect' => '../pages/YouthDashboard.php']);
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Role not recognized.']);
            exit;
    }

        } catch (mysqli_sql_exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Server error occurred.', 'details' => $e->getMessage()]);
            exit;
        }
?>
