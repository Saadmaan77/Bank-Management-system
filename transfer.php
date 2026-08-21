<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';
$sender_account_id = $_SESSION['account_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_acc_no = trim($_POST['recipient_acc']);
    $amount = floatval($_POST['amount']);

    if ($recipient_acc_no === $_SESSION['account_number']) {
        $error = "You cannot transfer funds to your own account.";
    } elseif ($amount <= 0) {
        $error = "Transfer amount must be greater than zero.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Check sender balance with lock
            $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? FOR UPDATE");
            $stmt->execute([$sender_account_id]);
            $sender = $stmt->fetch();

            if (!$sender || $sender['balance'] < $amount) {
                throw new Exception("Insufficient account balance.");
            }

            // 2. Validate recipient account
            $stmt = $pdo->prepare("SELECT id FROM accounts WHERE account_number = ? FOR UPDATE");
            $stmt->execute([$recipient_acc_no]);
            $recipient = $stmt->fetch();

            if (!$recipient) {
                throw new Exception("Recipient account number does not exist.");
            }

            // 3. Deduct from Sender
            $deduct = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $deduct->execute([$amount, $sender_account_id]);

            // 4. Credit to Recipient
            $credit = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
            $credit->execute([$amount, $recipient['id']]);

            // 5. Record transactions
            $log_sender = $pdo->prepare("INSERT INTO transactions (account_id, type, amount, recipient_account) VALUES (?, 'transfer', ?, ?)");
            $log_sender->execute([$sender_account_id, $amount, $recipient_acc_no]);

            $log_recipient = $pdo->prepare("INSERT INTO transactions (account_id, type, amount, recipient_account) VALUES (?, 'deposit', ?, ?)");
            $log_recipient->execute([$recipient['id'], $amount, $_SESSION['account_number']]);

            $pdo->commit();
            $message = "Transfer of $" . number_format($amount, 2) . " to A/C " . htmlspecialchars($recipient_acc_no) . " was successful.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fund Transfer</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-card">
        <h2>Transfer Funds</h2>
        <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="recipient_acc" placeholder="Recipient Account Number (e.g. AC12345678)" required>
            <input type="number" step="0.01" min="1" name="amount" placeholder="Amount to Transfer" required>
            <button type="submit" class="btn">Complete Transfer</button>
        </form>
        <p><a href="dashboard.php">&larr; Back to Dashboard</a></p>
    </div>
</body>
</html>