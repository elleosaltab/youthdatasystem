<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token=? LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $reset = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($reset && strtotime($reset['expires_at']) > time()) {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $reset['user_id']);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token=?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->close();

            $message = "Password has been reset. <a href='Login.html'>Login now</a>";
        } else {
            $message = "Invalid or expired reset link.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="../assets/css/log.css">
</head>
<body>
  <div class="login-box">
    <h1>Reset Password</h1>
    <?php if ($message): ?>
      <p style="color: red;"><?= $message ?></p>
    <?php endif; ?>
    <?php if ($token && !$message): ?>
      <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="input-box">
          <input type="password" name="password" placeholder="New Password" required>
        </div>
        <button type="submit" class="login-btn">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
