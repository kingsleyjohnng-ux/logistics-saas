<?php
require_once __DIR__ . '/includes/auth.php';

$shipment = null;
$history = [];
$notFound = false;

if (isset($_GET['t']) && trim($_GET['t']) !== '') {
    $tracking = trim($_GET['t']);
    $stmt = $pdo->prepare("
        SELECT s.*, c.name AS client_name, u.company_name FROM shipments s
        JOIN clients c ON c.id = s.client_id
        JOIN users u ON u.id = s.user_id
        WHERE s.tracking_number = ?
    ");
    $stmt->execute([$tracking]);
    $shipment = $stmt->fetch();
    if ($shipment) {
        $h = $pdo->prepare("SELECT * FROM shipment_status_history WHERE shipment_id = ? ORDER BY created_at DESC");
        $h->execute([$shipment['id']]);
        $history = $h->fetchAll();
    } else {
        $notFound = true;
    }
}

function badgeClass($status) {
    return 'badge-' . strtolower(str_replace(' ', '-', $status));
}

$pageTitle = 'Track Shipment';
include __DIR__ . '/includes/header.php';
?>
<?php if (!isLoggedIn()): ?>
<div class="auth-wrap" style="align-items:flex-start; padding-top:70px;">
<?php endif; ?>

<div class="track-hero">
    <h1 style="font-size:24px; margin-bottom:6px;">Track Your Shipment</h1>
    <p style="color:var(--text-dim); font-size:13px; margin-bottom:22px;">Enter your tracking number to see the latest status</p>
    <form method="GET" style="display:flex; gap:8px;">
        <input type="text" name="t" placeholder="e.g. LX9F2A1B7C" value="<?= htmlspecialchars($_GET['t'] ?? '') ?>" style="flex:1; padding:12px 14px; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:10px; color:var(--text); font-size:14px;">
        <button class="btn btn-primary" type="submit" style="width:auto; padding:12px 22px;">Track</button>
    </form>
</div>

<?php if ($notFound): ?>
    <div class="track-result">
        <div class="alert alert-error">No shipment found with that tracking number.</div>
    </div>
<?php elseif ($shipment): ?>
    <div class="track-result card">
        <p style="color:var(--text-dim); font-size:12px;">Shipped by</p>
        <h2 style="font-size:16px; margin-bottom:14px;"><?= htmlspecialchars($shipment['company_name']) ?></h2>
        <span class="badge <?= badgeClass($shipment['status']) ?>" style="margin-bottom:16px; display:inline-block;"><?= htmlspecialchars($shipment['status']) ?></span>
        <p style="font-size:14px; margin-top:10px;"><?= htmlspecialchars($shipment['origin']) ?> → <?= htmlspecialchars($shipment['destination']) ?></p>
        <?php if ($shipment['description']): ?><p style="color:var(--text-dim); font-size:13px; margin-top:4px;"><?= htmlspecialchars($shipment['description']) ?></p><?php endif; ?>

        <div class="timeline" style="margin-top:24px;">
            <?php foreach ($history as $h): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="status"><?= htmlspecialchars($h['status']) ?></div>
                        <?php if ($h['note']): ?><div class="note"><?= htmlspecialchars($h['note']) ?></div><?php endif; ?>
                        <div class="time"><?= date('M j, Y g:i A', strtotime($h['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!isLoggedIn()): ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
