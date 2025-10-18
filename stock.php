<?php
require 'config.php';
require 'header.php';

// ---------------------------
// Handle Add/Edit/Delete Stock
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = intval($_POST['id'] ?? 0);
    $currency_id = intval($_POST['currency_id'] ?? 0);
    $stock = floatval($_POST['stock'] ?? 0);
    $avg_price = floatval($_POST['avg_price'] ?? 0);

    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare("UPDATE holdings SET currency_id=?, stock=?, avg_price=? WHERE id=?");
        $stmt->execute([$currency_id, $stock, $avg_price, $id]);
        flash("Holding updated successfully!");
        header("Location: stock.php");
        exit;
    }

    if ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM holdings WHERE id=?");
        $stmt->execute([$id]);
        flash("Holding deleted successfully!");
        header("Location: stock.php");
        exit;
    }
}

// ---------------------------
// Fetch Holdings with Currency
// ---------------------------
$stmt = $pdo->query("
    SELECT h.id, c.name AS currency_name, c.code AS currency_code, h.stock, h.avg_price,
           (h.stock * h.avg_price) AS total_value, h.currency_id
    FROM holdings h
    JOIN currencies c ON h.currency_id = c.id
    ORDER BY c.name ASC
");
$holdings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all currencies for edit dropdown
$currencies = $pdo->query("SELECT id, name, code FROM currencies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg p-6 mt-6">
    <h2 class="text-2xl font-bold mb-4">Current Stock</h2>

    <table class="min-w-full table-auto border-collapse">
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="border p-2 text-left">Currency</th>
                <th class="border p-2 text-left">Code</th>
                <th class="border p-2 text-right">Stock Amount</th>
                <th class="border p-2 text-right">Average Buy Price</th>
                <th class="border p-2 text-right">Total Value</th>
                <th class="border p-2 text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($holdings): ?>
                <?php foreach ($holdings as $h): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border p-2"><?= htmlspecialchars($h['currency_name']) ?></td>
                        <td class="border p-2"><?= htmlspecialchars($h['currency_code']) ?></td>
                        <td class="border p-2 text-right"><?= number_format($h['stock'], 2) ?></td>
                        <td class="border p-2 text-right"><?= number_format($h['avg_price'], 2) ?></td>
                        <td class="border p-2 text-right"><?= number_format($h['total_value'], 2) ?></td>
                        <td class="border p-2 text-center">
                            <button onclick='openEditModal(<?= json_encode($h) ?>)' class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded">Edit</button>
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this holding?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded ml-2 hover:bg-red-600">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="border p-4 text-center text-gray-500">No holdings found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Edit Holding</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Currency</label>
                <select name="currency_id" id="edit_currency" class="border rounded w-full p-2" required>
                    <?php foreach ($currencies as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'] . " ({$c['code']})") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Stock Amount</label>
                <input type="number" step="0.0001" name="stock" id="edit_stock" class="border rounded w-full p-2" required>
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Average Buy Price</label>
                <input type="number" step="0.0001" name="avg_price" id="edit_avg_price" class="border rounded w-full p-2" required>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal()" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_currency').value = data.currency_id;
    document.getElementById('edit_stock').value = parseFloat(data.stock).toFixed(4);
    document.getElementById('edit_avg_price').value = parseFloat(data.avg_price).toFixed(4);
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
}

document.getElementById('editForm').addEventListener('submit', function(e){
    e.target.submit();
    closeModal();
});
</script>

<?php require 'footer.php'; ?>
