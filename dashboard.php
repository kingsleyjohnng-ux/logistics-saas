<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$uid = currentUserId();

$totalShipments = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE user_id = ?");
$totalShipments->execute([$uid]);
$totalShipments = $totalShipments->fetchColumn();

$inTransit = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE user_id = ? AND status IN ('Picked Up','In Transit','Out for Delivery')");
$inTransit->execute([$uid]);
$inTransit = $inTransit->fetchColumn();

$delivered = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE user_id = ? AND status = 'Delivered'");
$delivered->execute([$uid]);
$delivered = $delivered->fetchColumn();

$unpaid = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE user_id = ? AND status IN ('Unpaid','Overdue')");
$unpaid->execute([$uid]);
$unpaid = $unpaid->fetchColumn();

$recent = $pdo->prepare("
    SELECT s.*, c.name AS client_name FROM shipments s
    JOIN clients c ON c.id = s.client_id
    WHERE s.user_id = ? ORDER BY s.created_at DESC LIMIT 8
");
$recent->execute([$uid]);
$recent = $recent->fetchAll();

function badgeClass($status) {
    return 'badge-' . strtolower(str_replace(' ', '-', $status));
}

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>Overview of your logistics operations</p>
    </div>
    <a href="shipments.php?new=1" class="btn btn-primary">+ New Shipment</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Total Shipments</div>
        <div class="value"><?= $totalShipments ?></div>
    </div>
    <div class="stat-card">
        <div class="label">In Transit</div>
        <div class="value accent"><?= $inTransit ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Delivered</div>
        <div class="value"><?= $delivered ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Outstanding Invoices</div>
        <div class="value">$<?= number_format($unpaid, 2) ?></div>
    </div>
</div>

<div class="card">
    <h2 style="font-size:16px; margin-bottom:16px;">Recent Shipments</h2>
    <?php if (empty($recent)): ?>
        <div class="empty-state">No shipments yet. Create your first one to get started.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tracking #</th><th>Client</th><th>Route</th><th>Status</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $s): ?>
                <tr>
                    <td><a href="shipments.php?view=<?= $s['id'] ?>"><?= htmlspecialchars($s['tracking_number']) ?></a></td>
                    <td><?= htmlspecialchars($s['client_name']) ?></td>
                    <td><?= htmlspecialchars($s['origin']) ?> → <?= htmlspecialchars($s['destination']) ?></td>
                    <td><span class="badge <?= badgeClass($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                    <td><?= date('M j, Y', strtotime($s['updated_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
