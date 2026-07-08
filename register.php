<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company = trim($_POST['company_name'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$company || !$name || !$email || strlen($password) < 6) {
        $error = 'Please fill all fields. Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (company_name, name, email, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$company, $name, $email, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: dashboard.php');
            exit;
        }
    }
}
$pageTitle = 'Sign Up';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Create your account</h1>
        <p class="sub">Set up your logistics workspace</p>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="field">
                <label>Company Name</label>
                <input type="text" name="company_name" required autofocus value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Your Name</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <button class="btn btn-primary" type="submit">Create Account</button>
        </form>
        <div class="auth-foot">Already have an account? <a href="index.php">Log in</a></div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
