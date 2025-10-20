<?php
require 'config.php';
require 'header.php';

// ------------------------------
// Handle Add Transaction
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $note = $_POST['note'] ?? '';

    if ($type && is_numeric($amount) && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO debit_credit (type, amount, note, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$type, $amount, $note]);
        header("Location: debit_credit.php");
        exit;
    }
}

// ------------------------------
// Handle Edit Transaction
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $note = $_POST['note'] ?? '';

    if ($id && $type && is_numeric($amount) && $amount > 0) {
        $stmt = $pdo->prepare("UPDATE debit_credit SET type=?, amount=?, note=? WHERE id=?");
        $stmt->execute([$type, $amount, $note, $id]);
        header("Location: debit_credit.php");
        exit;
    }
}

// ------------------------------
// Handle Delete Transaction
// ------------------------------
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM debit_credit WHERE id=?");
    $stmt->execute([$id]);
    header("Location: debit_credit.php");
    exit;
}

// ------------------------------
// Fetch All Records
// ------------------------------
$records = $pdo->query("SELECT * FROM debit_credit ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Totals
$total_debit = $pdo->query("SELECT SUM(amount) FROM debit_credit WHERE type='debit'")->fetchColumn() ?? 0;
$total_credit = $pdo->query("SELECT SUM(amount) FROM debit_credit WHERE type='credit'")->fetchColumn() ?? 0;
?>

<div class="max-w-6xl mx-auto mt-10 p-6 bg-white rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-6 text-center">Debit & Credit Manager</h2>

    <!-- Add Form -->
    <form method="POST" class="grid md:grid-cols-4 gap-4 mb-8">
        <input type="hidden" name="action" value="add">
        <div>
            <label class="block font-semibold mb-1">Type</label>
            <select name="type" class="w-full border rounded p-2" required>
                <option value="">-- Select --</option>
                <option value="debit">Debit</option>
                <option value="credit">Credit</option>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Amount</label>
            <input type="number" step="0.001" name="amount" class="w-full border rounded p-2" required>
        </div>
        <div>
            <label class="block font-semibold mb-1">Note</label>
            <input type="text" name="note" placeholder="Optional" class="w-full border rounded p-2">
        </div>
        <div class="flex items-end justify-center">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">Add</button>
        </div>
    </form>

    <!-- Totals Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-red-100 p-4 rounded text-center">
            <h3 class="text-lg font-semibold text-gray-700">Total Debit</h3>
            <p class="text-2xl font-bold text-red-700 mt-2">৳<?= number_format($total_debit, 3) ?></p>
        </div>
        <div class="bg-green-100 p-4 rounded text-center">
            <h3 class="text-lg font-semibold text-gray-700">Total Credit</h3>
            <p class="text-2xl font-bold text-green-700 mt-2">৳<?= number_format($total_credit, 3) ?></p>
        </div>
    </div>

    <!-- Records Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-3 py-2">Date</th>
                    <th class="border px-3 py-2">Type</th>
                    <th class="border px-3 py-2 text-right">Amount</th>
                    <th class="border px-3 py-2">Note</th>
                    <th class="border px-3 py-2 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($records): ?>
                    <?php foreach ($records as $r): ?>
                        <tr id="row-<?= $r['id'] ?>">
                            <td class="border px-3 py-2"><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>
                            <td class="border px-3 py-2 capitalize"><?= htmlspecialchars($r['type']) ?></td>
                            <td class="border px-3 py-2 text-right"><?= number_format($r['amount'], 3) ?></td>
                            <td class="border px-3 py-2"><?= htmlspecialchars($r['note']) ?></td>
                            <td class="border px-3 py-2 text-center">
                                <button onclick='openEditModal(<?= json_encode($r) ?>)' class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded">Edit</button>
                                <a href="?delete=<?= $r['id'] ?>" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded ml-2">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="border p-4 text-center text-gray-500">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4 text-center">Edit Transaction</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Type</label>
                <select name="type" id="edit_type" class="w-full border rounded p-2">
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Amount</label>
                <input type="number" step="0.001" name="amount" id="edit_amount" class="w-full border rounded p-2">
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Note</label>
                <input type="text" name="note" id="edit_note" class="w-full border rounded p-2">
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal()" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_type').value = data.type;
    document.getElementById('edit_amount').value = parseFloat(data.amount).toFixed(3);
    document.getElementById('edit_note').value = data.note || '';
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.target.submit();
    closeModal();
});
</script>

<?php require 'footer.php'; ?>
