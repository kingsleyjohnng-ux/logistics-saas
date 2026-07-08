<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$uid = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO clients (user_id, name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $name, $email, $phone, $address]);
        flash('success', 'Client added.');
    } else {
        flash('error', 'Client name is required.');
    }
    header('Location: clients.php');
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $uid]);
    flash('success', 'Client removed.');
    header('Location: clients.php');
    exit;
}

$clients = $pdo->prepare("
    SELECT c.*, COUNT(s.id) AS shipment_count FROM clients c
    LEFT JOIN shipments s ON s.client_id = c.id
    WHERE c.user_id = ? GROUP BY c.id ORDER BY c.created_at DESC
");
$clients->execute([$uid]);
$clients = $clients->fetchAll();

$pageTitle = 'Clients';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Clients</h1>
        <p>Manage the businesses and people you ship for</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addClientModal')">+ Add Client</button>
</div>

<div class="card">
    <?php if (empty($clients)): ?>
        <div class="empty-state">No clients yet. Add your first client to start creating shipments.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Shipments</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($clients as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['email'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($c['phone'] ?: '—') ?></td>
                    <td><?= $c['shipment_count'] ?></td>
                    <td><a href="clients.php?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove this client?')">Remove</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="addClientModal">
    <div class="modal">
        <span class="modal-close" onclick="closeModal('addClientModal')">&times;</span>
        <h2>Add Client</h2>
        <form method="POST">
            <input type="hidden" name="add_client" value="1">
            <div class="field"><label>Name</label><input type="text" name="name" required></div>
            <div class="field"><label>Email</label><input type="email" name="email"></div>
            <div class="field"><label>Phone</label><input type="text" name="phone"></div>
            <div class="field"><label>Address</label><input type="text" name="address"></div>
            <button class="btn btn-primary" type="submit">Add Client</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
