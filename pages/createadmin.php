<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: Login.html");
    exit();
}

$fullname = $_SESSION['full_name'] ?? 'Superadmin';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $municipality = trim($_POST['municipality'] ?? '');

    if ($first_name && $last_name && $email && $password && $municipality) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            $stmt->close();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $is_verified = 0;
            $role = 'admin';

            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, is_verified, role, municipality) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiss", $first_name, $last_name, $email, $hashed_password, $is_verified, $role, $municipality);

            if ($stmt->execute()) {
                $success = "Admin account created successfully! Pending approval.";
            } else {
                $error = "Failed to create admin. Try again!";
            }
            $stmt->close();
        }
    } else {
        $error = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Create Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin_sidebar.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    font-family: 'Inter', sans-serif;
    background: #f8f9fa;
    display: flex;
    min-height: 100vh;
}
.main-content {
    margin-left: 250px;
    width: calc(100% - 250px);
    padding: 30px;
}
.card-form {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>
</head>

<body>
<?php include __DIR__ . '/../includes/SuperAdminSidebar.php'; ?>

<main class="main-content">
    <h2>Create Admin Account</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card-form mt-4">
        <form method="POST">
            <div class="mb-3">
                <label>First Name</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Municipality</label>
                <input type="text" name="municipality" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Create Admin</button>
        </form>
    </div>
</main>

<script>

const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: "Log Out?",
            text: "Are you sure you want to log out?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Logout",
            cancelButtonText: "Cancel"
        }).then((res) => {
            if (res.isConfirmed) window.location.href = "../auth/Logout.php";
        });
    });
}
</script>

</body>
</html>
