<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = isLoggedIn() ? currentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Lumidexx Logistics</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if (isLoggedIn()): ?>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-mark">L</span>
            <span class="brand-name">Logistics</span>
        </div>
        <nav class="nav">
            <a href="dashboard.php" class="<?= $currentPage=='dashboard.php'?'active':'' ?>">Dashboard</a>
            <a href="shipments.php" class="<?= $currentPage=='shipments.php'?'active':'' ?>">Shipments</a>
            <a href="clients.php" class="<?= $currentPage=='clients.php'?'active':'' ?>">Clients</a>
            <a href="invoices.php" class="<?= $currentPage=='invoices.php'?'active':'' ?>">Invoices</a>
            <a href="track.php" class="<?= $currentPage=='track.php'?'active':'' ?>">Public Tracking</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($user['company_name'] ?? '') ?></div>
                    <a href="logout.php" class="logout-link">Log out</a>
                </div>
            </div>
        </div>
    </aside>
    <main class="main-content">
        <?php
        $successMsg = flash('success');
        $errorMsg = flash('error');
        ?>
        <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>
<?php endif; ?>
