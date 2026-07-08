<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$uid = currentUserId();

function badgeClass($status) {
    return 'badge-' . strtolower(str_replace(' ', '-', $status));
}

// Create shipment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shipment'])) {
    $client_id = $_POST['client_id'] ?? '';
    $origin = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $weight = $_POST['weight_kg'] ?? 0;
    $cost = $_POST['cost'] ?? 0;

    if ($client_id && $origin && $destination) {
        $tracking = generateTrackingNumber();
        $stmt = $pdo->prepare("INSERT INTO shipments (user_id, client_id, tracking_number, origin, destination, description, weight_kg, cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $client_id, $tracking, $origin, $destination, $description, $weight, $cost]);
        $shipmentId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, status, note) VALUES (?, 'Pending', 'Shipment created')")->execute([$shipmentId]);
        flash('success', "Shipment created. Tracking #: $tracking");
    } else {
        flash('error', 'Please fill in client, origin, and destination.');
    }
    header('Location: shipments.php');
    exit;
}

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $shipmentId = $_POST['shipment_id'];
    $status = $_POST['status'];
    $note = trim($_POST['note'] ?? '');

    $check = $pdo->prepare("SELECT id FROM shipments WHERE id = ? AND user_id = ?");
    $check->execute([$shipmentId, $uid]);
    if ($check->fetch()) {
        $pdo->prepare("UPDATE shipments SET status = ? WHERE id = ?")->execute([$status, $shipmentId]);
        $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, status, note) VALUES (?, ?, ?)")->execute([$shipmentId, $status, $note]);
        flash('success', 'Status updated.');
    }
    header('Location: shipments.php?view=' . $shipmentId);
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM shipments WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $uid]);
    flash('success', 'Shipment deleted.');
    header('Location: shipments.php');
    exit;
}

$clients = $pdo->prepare("SELECT id, name FROM clients WHERE user_id = ? ORDER BY name");
$clients->execute([$uid]);
$clients = $clients->fetchAll();

// Viewing a single shipment
$viewShipment = null;
$history = [];
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT s.*, c.name AS client_name FROM shipments s JOIN clients c ON c.id = s.client_id WHERE s.id = ? AND s.user_id = ?");
    $stmt->execute([$_GET['view'], $uid]);
    $viewShipment = $stmt->fetch();
    if ($viewShipment) {
        $h = $pdo->prepare("SELECT * FROM shipment_status_history WHERE shipment_id = ? ORDER BY created_at DESC");
        $h->execute([$viewShipment['id']]);
        $history = $h->fetchAll();
    }
}

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT s.*, c.name AS client_name FROM shipments s JOIN clients c ON c.id = s.client_id WHERE s.user_id = ?";
$params = [$uid];
if ($statusFilter) {
    $sql .= " AND s.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$shipments = $stmt->fetchAll();

$statuses = ['Pending','Picked Up','In Transit','Out for Delivery','Delivered','Cancelled'];
$pageTitle = 'Shipments';
include __DIR__ . '/includes/header.php';
?>

<?php if ($viewShipment): ?>
    <div class="page-head">
        <div>
            <h1><?= htmlspecialchars($viewShipment['tracking_number']) ?></h1>
            <p><?= htmlspecialchars($viewShipment['client_name']) ?> · <?= htmlspecialchars($viewShipment['origin']) ?> → <?= htmlspecialchars($viewShipment['destination']) ?></p>
        </div>
        <a href="shipments.php" class="btn btn-secondary">← Back to Shipments</a>
    </div>

    <div class="form-grid">
        <div class="card">
            <h2 style="font-size:16px; margin-bottom:16px;">Shipment Details</h2>
            <p style="color:var(--text-dim); font-size:13px; margin-bottom:6px;">Status</p>
            <span class="badge <?= badgeClass($viewShipment['status']) ?>" style="margin-bottom:16px; display:inline-block;"><?= htmlspecialchars($viewShipment['status']) ?></span>
            <p style="color:var(--text-dim); font-size:13px; margin:14px 0 4px;">Description</p>
            <p><?= htmlspecialchars($viewShipment['description'] ?: '—') ?></p>
            <p style="color:var(--text-dim); font-size:13px; margin:14px 0 4px;">Weight</p>
            <p><?= number_format($viewShipment['weight_kg'], 2) ?> kg</p>
            <p style="color:var(--text-dim); font-size:13px; margin:14px 0 4px;">Cost</p>
            <p>$<?= number_format($viewShipment['cost'], 2) ?></p>

            <form method="POST" style="margin-top:20px; border-top:1px solid var(--border); padding-top:18px;">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="shipment_id" value="<?= $viewShipment['id'] ?>">
                <div class="field">
                    <label>Update Status</label>
                    <select name="status">
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= $st === $viewShipment['status'] ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Note (optional)</label>
                    <input type="text" name="note" placeholder="e.g. Left warehouse in Lagos">
                </div>
                <button class="btn btn-primary" type="submit">Update Status</button>
            </form>
        </div>

        <div class="card">
            <h2 style="font-size:16px; margin-bottom:16px;">Tracking History</h2>
            <div class="timeline">
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
    </div>

<?php else: ?>

    <div class="page-head">
        <div>
            <h1>Shipments</h1>
            <p>Track and manage all your shipments</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addShipmentModal')">+ New Shipment</button>
    </div>

    <div class="card" style="margin-bottom:16px; padding:14px 20px;">
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="shipments.php" class="btn btn-sm <?= $statusFilter==''?'btn-primary':'btn-secondary' ?>">All</a>
            <?php foreach ($statuses as $st): ?>
                <a href="shipments.php?status=<?= urlencode($st) ?>" class="btn btn-sm <?= $statusFilter===$st?'btn-primary':'btn-secondary' ?>"><?= $st ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <?php if (empty($shipments)): ?>
            <div class="empty-state">
                <?php if (empty($clients)): ?>
                    Add a client first, then create your first shipment.
                <?php else: ?>
                    No shipments found.
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tracking #</th><th>Client</th><th>Route</th><th>Cost</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($shipments as $s): ?>
                    <tr>
                        <td><a href="shipments.php?view=<?= $s['id'] ?>"><?= htmlspecialchars($s['tracking_number']) ?></a></td>
                        <td><?= htmlspecialchars($s['client_name']) ?></td>
                        <td><?= htmlspecialchars($s['origin']) ?> → <?= htmlspecialchars($s['destination']) ?></td>
                        <td>$<?= number_format($s['cost'], 2) ?></td>
                        <td><span class="badge <?= badgeClass($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                        <td><?= date('M j, Y', strtotime($s['updated_at'])) ?></td>
                        <td>
                            <a href="shipments.php?view=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                            <a href="shipments.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this shipment?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="modal-overlay" id="addShipmentModal">
        <div class="modal">
            <span class="modal-close" onclick="closeModal('addShipmentModal')">&times;</span>
            <h2>New Shipment</h2>
            <?php if (empty($clients)): ?>
                <p style="color:var(--text-dim); font-size:13px;">You need to <a href="clients.php" style="color:var(--accent);">add a client</a> before creating a shipment.</p>
            <?php else: ?>
            <form method="POST">
                <input type="hidden" name="add_shipment" value="1">
                <div class="field">
                    <label>Client</label>
                    <select name="client_id" required>
                        <option value="">Select client</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grid">
                    <div class="field"><label>Origin</label><input type="text" name="origin" required></div>
                    <div class="field"><label>Destination</label><input type="text" name="destination" required></div>
                </div>
                <div class="field"><label>Description</label><input type="text" name="description" placeholder="e.g. 3 boxes, electronics"></div>
                <div class="form-grid">
                    <div class="field"><label>Weight (kg)</label><input type="number" step="0.01" name="weight_kg" value="0"></div>
                    <div class="field"><label>Cost ($)</label><input type="number" step="0.01" name="cost" value="0"></div>
                </div>
                <button class="btn btn-primary" type="submit">Create Shipment</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php if (isset($_GET['new'])): ?>
<script>document.addEventListener('DOMContentLoaded', function() { openModal('addShipmentModal'); });</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
