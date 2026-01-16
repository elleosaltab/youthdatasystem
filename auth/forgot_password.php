<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
   
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
     
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

   
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)
                                    ON DUPLICATE KEY UPDATE token=?, expires_at=?");
            $stmt->bind_param("issss", $user['id'], $token, $expiry, $token, $expiry);
            $stmt->execute();
            $stmt->close();

     
            $resetLink = "http://yourdomain.com/auth/reset_password.php?token=" . $token;

   
            $message = "A reset link has been sent to your email.";
        } else {
            $message = "No account found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="../assets/css/log.css">
</head>
<body>
  <div class="login-box">
    <h1>Forgot Password</h1>
    <?php if ($message): ?>
      <p style="color: red;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST" action="">
      <div class="input-box">
        <input type="email" name="email" placeholder="Enter your email" required>
      </div>
      <button type="submit" class="login-btn">Send Reset Link</button>
    </form>
    <p><a href="../pages/Login.html">Back to Login</a></p>
  </div>
</body>
</html>
