<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$uid = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_invoice'])) {
    $shipment_id = $_POST['shipment_id'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $due = $_POST['due_at'] ?? null;

    $check = $pdo->prepare("SELECT id FROM shipments WHERE id = ? AND user_id = ?");
    $check->execute([$shipment_id, $uid]);
    if ($check->fetch() && $amount > 0) {
        $num = generateInvoiceNumber();
        $stmt = $pdo->prepare("INSERT INTO invoices (user_id, shipment_id, invoice_number, amount, due_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $shipment_id, $num, $amount, $due ?: null]);
        flash('success', "Invoice $num created.");
    } else {
        flash('error', 'Please select a shipment and enter a valid amount.');
    }
    header('Location: invoices.php');
    exit;
}

if (isset($_GET['mark_paid'])) {
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'Paid' WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['mark_paid'], $uid]);
    flash('success', 'Invoice marked as paid.');
    header('Location: invoices.php');
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $uid]);
    header('Location: invoices.php');
    exit;
}

// auto-mark overdue
$pdo->prepare("UPDATE invoices SET status = 'Overdue' WHERE user_id = ? AND status = 'Unpaid' AND due_at IS NOT NULL AND due_at < CURDATE()")->execute([$uid]);

$shipments = $pdo->prepare("SELECT s.id, s.tracking_number, c.name AS client_name FROM shipments s JOIN clients c ON c.id = s.client_id WHERE s.user_id = ? ORDER BY s.created_at DESC");
$shipments->execute([$uid]);
$shipments = $shipments->fetchAll();

$invoices = $pdo->prepare("
    SELECT i.*, s.tracking_number, c.name AS client_name FROM invoices i
    JOIN shipments s ON s.id = i.shipment_id
    JOIN clients c ON c.id = s.client_id
    WHERE i.user_id = ? ORDER BY i.issued_at DESC
");
$invoices->execute([$uid]);
$invoices = $invoices->fetchAll();

function invBadge($status) { return 'badge-' . strtolower($status); }

$pageTitle = 'Invoices';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Invoices</h1>
        <p>Bill clients for completed and in-progress shipments</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addInvoiceModal')">+ New Invoice</button>
</div>

<div class="card">
    <?php if (empty($invoices)): ?>
        <div class="empty-state">No invoices yet.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Invoice #</th><th>Client</th><th>Shipment</th><th>Amount</th><th>Due</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i['invoice_number']) ?></td>
                    <td><?= htmlspecialchars($i['client_name']) ?></td>
                    <td><?= htmlspecialchars($i['tracking_number']) ?></td>
                    <td>$<?= number_format($i['amount'], 2) ?></td>
                    <td><?= $i['due_at'] ? date('M j, Y', strtotime($i['due_at'])) : '—' ?></td>
                    <td><span class="badge <?= invBadge($i['status']) ?>"><?= htmlspecialchars($i['status']) ?></span></td>
                    <td>
                        <?php if ($i['status'] !== 'Paid'): ?>
                            <a href="invoices.php?mark_paid=<?= $i['id'] ?>" class="btn btn-sm btn-secondary">Mark Paid</a>
                        <?php endif; ?>
                        <a href="invoices.php?delete=<?= $i['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this invoice?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="addInvoiceModal">
    <div class="modal">
        <span class="modal-close" onclick="closeModal('addInvoiceModal')">&times;</span>
        <h2>New Invoice</h2>
        <?php if (empty($shipments)): ?>
            <p style="color:var(--text-dim); font-size:13px;">Create a shipment first before invoicing.</p>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="add_invoice" value="1">
            <div class="field">
                <label>Shipment</label>
                <select name="shipment_id" required>
                    <option value="">Select shipment</option>
                    <?php foreach ($shipments as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['tracking_number']) ?> — <?= htmlspecialchars($s['client_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Amount ($)</label><input type="number" step="0.01" name="amount" required></div>
            <div class="field"><label>Due Date</label><input type="date" name="due_at"></div>
            <button class="btn btn-primary" type="submit">Create Invoice</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
