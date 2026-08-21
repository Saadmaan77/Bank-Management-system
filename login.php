<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.password, u.role, a.id AS account_id, a.account_number, a.balance 
        FROM users u 
        LEFT JOIN accounts a ON u.id = a.user_id 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['account_id'] = $user['account_id'];
        $_SESSION['account_number'] = $user['account_number'];
        
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-card">
        <h2>Account Login</h2>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn">Sign In</button>
        </form>
        <p>Don't have an account? <a href="register.php">Create one</a></p>
    </div>
</body>
</html>