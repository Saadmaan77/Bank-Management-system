<?php
session_start();
require_once 'config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $initial_deposit = floatval($_POST['initial_deposit']);

    if (!empty($name) && !empty($email) && !empty($password) && $initial_deposit >= 0) {
        $hashed_pwd = password_hash($password, PASSWORD_BCRYPT);
        $account_number = 'AC' . mt_rand(10000000, 99999999);

        try {
            $pdo->beginTransaction();

            // Insert User
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_pwd]);
            $user_id = $pdo->lastInsertId();

            // Create Associated Account
            $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $account_number, $initial_deposit]);
            $account_id = $pdo->lastInsertId();

            // Record Initial Deposit Transaction if > 0
            if ($initial_deposit > 0) {
                $stmt = $pdo->prepare("INSERT INTO transactions (account_id, type, amount) VALUES (?, 'deposit', ?)");
                $stmt->execute([$account_id, $initial_deposit]);
            }

            $pdo->commit();
            header("Location: login.php?registered=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Registration failed: " . $e->getMessage();
        }
    } else {
        $message = "Please fill in all fields with valid values.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-card">
        <h2>Open Bank Account</h2>
        <?php if ($message): ?><p class="error"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="number" step="0.01" name="initial_deposit" placeholder="Initial Deposit Amount" min="0" required>
            <button type="submit" class="btn">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Log In</a></p>
    </div>
</body>
</html>