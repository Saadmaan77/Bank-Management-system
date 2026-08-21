<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$account_id = $_SESSION['account_id'];

// Refresh Balance
$stmt = $pdo->prepare("SELECT balance, account_number FROM accounts WHERE id = ?");
$stmt->execute([$account_id]);
$account = $stmt->fetch();

// Fetch Transaction History
$tx_stmt = $pdo->prepare("SELECT type, amount, recipient_account, timestamp FROM transactions WHERE account_id = ? ORDER BY timestamp DESC LIMIT 10");
$tx_stmt->execute([$account_id]);
$transactions = $tx_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="navbar">
        <div class="brand">Prime Trust Bank</div>
        <div class="user-meta">
            Welcome, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> | 
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="grid-2">
            <div class="card balance-card">
                <h3>Account Summary</h3>
                <p class="acc-no">A/C: <?= htmlspecialchars($account['account_number']) ?></p>
                <div class="balance-display">$<?= number_format($account['balance'], 2) ?></div>
                <p class="status">Status: Active</p>
            </div>

            <div class="card">
                <h3>Quick Actions</h3>
                <a href="transfer.php" class="btn btn-action">Send Money / Transfer Funds</a>
            </div>
        </div>

        <div class="card" style="margin-top: 24px;">
            <h3>Recent Transaction History</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Recipient / Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="4">No transactions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?= date('M d, Y H:i', strtotime($tx['timestamp'])) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($tx['type']) ?>"><?= ucfirst($tx['type']) ?></span></td>
                                <td>$<?= number_format($tx['amount'], 2) ?></td>
                                <td><?= $tx['recipient_account'] ? htmlspecialchars($tx['recipient_account']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>