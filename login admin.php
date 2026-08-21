// Replace the redirect section in login.php with this block:
if ($user['role'] === 'admin') {
    header("Location: admin_dashboard.php");
} else {
    header("Location: dashboard.php");
}
exit;