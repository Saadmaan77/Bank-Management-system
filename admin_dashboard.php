<?php
session_start();
require_once 'config/db.php';

// Strict Role-Based Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Handle Admin Actions (Authorize, Delete, Deposit, Withdraw)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Authorize / Toggle Account Status
    if ($action === 'toggle_status') {
        $account_id = intval($_POST['account_id']);
        $new_status = $_POST['new_status'] === 'suspended' ? 'suspended' : 'active';
        try {
            $stmt = $pdo->prepare("UPDATE accounts SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $account_id]);
            $message = "Account status updated to " . ucfirst($new_status) . ".";
        } catch (Exception $e) {
            $error = "Failed to update status: " . $e->getMessage();
        }
    }

    // 2. Delete Customer Account
    elseif ($action === 'delete_user') {
        $user_id = intval($_POST['user_id']);
        try {
            // ON DELETE CASCADE removes associated account and transactions automatically
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
            $stmt->execute([$user_id]);
            $message = "Customer account and all associated records deleted successfully.";
        } catch (Exception $e) {
            $error = "Failed to delete user: " . $e->getMessage();
        }
    }

    // 3. Admin Deposit
    elseif ($action === 'admin_deposit') {
        $account_id = intval($_POST['account_id']);
        $amount = floatval($_POST['amount']);

        if ($amount <= 0) {
            $error = "Deposit amount must be greater than zero.";
        } else {
            try {
                $pdo->beginTransaction();
                
                $update = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
                $update->execute([$amount, $account_id]);

                $log = $pdo->prepare("INSERT INTO transactions (account_id, type, amount, recipient_account) VALUES (?, 'deposit', ?, 'Admin Deposit')");
                $log->execute([$account_id, $amount]);

                $pdo->commit();
                $message = "Deposited $" . number_format($amount, 2) . " successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Deposit failed: " . $e->getMessage();
            }
        }
    }

    // 4. Admin Withdrawal / Debit
    elseif ($action === 'admin_withdraw') {
        $account_id = intval($_POST['account_id']);
        $amount = floatval($_POST['amount']);

        if ($amount <= 0) {
            $error = "Withdrawal amount must be greater than zero.";
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? FOR UPDATE");
                $stmt->execute([$account_id]);
                $acc = $stmt->fetch();

                if (!$acc || $acc['balance'] < $amount) {
                    throw new Exception("Insufficient customer balance for withdrawal.");
                }

                $update = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
                $update->execute([$amount, $account_id]);

                $log = $pdo->prepare("INSERT INTO transactions (account_id, type, amount, recipient_account) VALUES (?, 'withdrawal', ?, 'Admin Debit')");
                $log->execute([$account_id, $amount]);

                $pdo->commit();
                $message = "Debited $" . number_format($amount, 2) . " successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Withdrawal failed: " . $e->getMessage();
            }
        }
    }
}

// Fetch Global Metrics
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$total_accounts = $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
$total_balance = $pdo->query("SELECT SUM(balance) FROM accounts")->fetchColumn() ?: 0.00;
$total_transactions = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();

// Fetch Customer & Account Records
$query = "
    SELECT 
        u.id AS user_id,
        u.name,
        u.email,
        u.created_at AS registered_at,
        a.id AS account_id,
        a.account_number,
        a.balance,
        a.status
    FROM users u
    LEFT JOIN accounts a ON u.id = a.user_id
    WHERE u.role = 'customer'
    ORDER BY u.created_at DESC
";
$accounts = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Prime Trust Bank</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header class="navbar navbar-admin">
        <div class="brand">Prime Trust Bank — Admin Console</div>
        <div class="user-meta">
            Logged in as <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> (Admin) | 
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container admin-container">
        <?php if ($message): ?><div class="success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Banking Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Vault Liquidity</div>
                <div class="stat-value">$<?= number_format($total_balance, 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Customers</div>
                <div class="stat-value"><?= number_format($total_users) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Registered Accounts</div>
                <div class="stat-value"><?= number_format($total_accounts) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Processed Transactions</div>
                <div class="stat-value"><?= number_format($total_transactions) ?></div>
            </div>
        </div>

        <!-- Customer & Account Management Table -->
        <div class="card" style="margin-top: 28px;">
            <h3>Manage Customer Accounts</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Account No</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Quick Deposit / Withdraw</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr><td colspan="6">No customer accounts available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $acc): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($acc['name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($acc['email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($acc['account_number'] ?? 'N/A') ?></td>
                                <td><strong>$<?= number_format($acc['balance'] ?? 0, 2) ?></strong></td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($acc['status'] ?? 'active') ?>">
                                        <?= ucfirst(htmlspecialchars($acc['status'] ?? 'active')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($acc['account_id']): ?>
                                        <!-- Deposit / Withdraw Forms -->
                                        <form method="POST" style="display:inline-flex; gap: 4px; align-items: center;">
                                            <input type="hidden" name="account_id" value="<?= $acc['account_id'] ?>">
                                            <input type="number" step="0.01" min="1" name="amount" placeholder="$ Amount" style="width: 90px; margin: 0; padding: 4px 8px;" required>
                                            <button type="submit" name="action" value="admin_deposit" class="btn-action-small btn-activate">+ Dep</button>
                                            <button type="submit" name="action" value="admin_withdraw" class="btn-action-small btn-suspend">- With</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">No Account</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <?php if ($acc['account_id']): ?>
                                            <!-- Toggle Authorize / Suspend -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="account_id" value="<?= $acc['account_id'] ?>">
                                                <?php if ($acc['status'] === 'active'): ?>
                                                    <input type="hidden" name="new_status" value="suspended">
                                                    <button type="submit" class="btn-action-small btn-suspend" onclick="return confirm('Suspend this account?')">Suspend</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit" class="btn-action-small btn-activate">Authorize</button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Delete Customer -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $acc['user_id'] ?>">
                                            <button type="submit" class="btn-action-small" style="background:#475569; color:#fff;" onclick="return confirm('Permanently delete this user and all associated accounts/transactions?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>