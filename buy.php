<?php
require 'config.php';
require 'header.php';

// Handle Add Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $currency_id = $_POST['currency_id'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $buy_price = $_POST['buy_price'] ?? '';

    if ($currency_id && is_numeric($stock) && is_numeric($buy_price)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM holdings WHERE currency_id = ? FOR UPDATE");
            $stmt->execute([$currency_id]);
            $holding = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($holding) {
                $prev_stock = $holding['stock'];
                $prev_avg_price = $holding['avg_price'];
                $new_total = ($prev_stock * $prev_avg_price) + ($stock * $buy_price);
                $new_stock = $prev_stock + $stock;
                $new_avg_price = $new_total / $new_stock;

                $stmt = $pdo->prepare("UPDATE holdings SET stock=?, avg_price=? WHERE currency_id=?");
                $stmt->execute([$new_stock, $new_avg_price, $currency_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO holdings (currency_id, stock, avg_price) VALUES (?, ?, ?)");
                $stmt->execute([$currency_id, $stock, $buy_price]);
                $prev_stock = 0;
                $prev_avg_price = 0;
            }

            $stmt = $pdo->prepare("
                INSERT INTO transactions (currency_id, type, qty, price, total, profit, prev_qty, prev_avg_price, created_at)
                VALUES (?, 'buy', ?, ?, ?, 0, ?, ?, NOW())
            ");
            $stmt->execute([
                $currency_id, $stock, $buy_price, $stock * $buy_price, $prev_stock, $prev_avg_price
            ]);

            $pdo->commit();
            header("Location: buy.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo "<p class='text-red-600 text-center mt-4'>Error: " . $e->getMessage() . "</p>";
        }
    }
}

// Handle Edit Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'] ?? '';
    $qty = $_POST['qty'] ?? '';
    $price = $_POST['price'] ?? '';

    if ($id && is_numeric($qty) && is_numeric($price)) {
        $stmt = $pdo->prepare("UPDATE transactions SET qty=?, price=?, total=? WHERE id=?");
        $stmt->execute([$qty, $price, $qty * $price, $id]);
        header("Location: buy.php");
        exit;
    }
}

// Handle Delete Transaction
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id=?");
    $stmt->execute([$id]);
    header("Location: buy.php");
    exit;
}

// Fetch currencies
$currencies = $pdo->query("SELECT * FROM currencies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch buys
$stmt = $pdo->query("SELECT t.*, c.name AS currency_name 
                    FROM transactions t 
                    JOIN currencies c ON c.id = t.currency_id 
                    WHERE t.type='buy' 
                    ORDER BY t.created_at DESC");
$buys = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-6xl mx-auto mt-10 p-6 bg-white rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-6 text-center">Buy Currency</h2>

    <!-- Add Form -->
    <form method="POST" class="grid md:grid-cols-3 gap-4 mb-8">
        <input type="hidden" name="action" value="add">
        <div>
            <label class="block font-semibold mb-1">Currency</label>
            <select name="currency_id" class="w-full border rounded p-2" required>
                <option value="">-- Select --</option>
                <?php foreach ($currencies as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Stock</label>
            <input type="number" step="0.001" name="stock" class="w-full border rounded p-2" required>
        </div>
        <div>
            <label class="block font-semibold mb-1">Buy Price</label>
            <input type="number" step="0.001" name="buy_price" class="w-full border rounded p-2" required>
        </div>
        <div class="md:col-span-3 flex justify-center">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">Add Buy</button>
        </div>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-3 py-2">Currency</th>
                    <th class="border px-3 py-2">Qty</th>
                    <th class="border px-3 py-2">Price</th>
                    <th class="border px-3 py-2">Total</th>
                    <th class="border px-3 py-2">Date</th>
                    <th class="border px-3 py-2 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buys as $b): ?>
                    <tr id="row-<?= $b['id'] ?>">
                        <td class="border px-3 py-2"><?= htmlspecialchars($b['currency_name']) ?></td>
                        <td class="border px-3 py-2"><?= number_format($b['qty'], 3) ?></td>
                        <td class="border px-3 py-2"><?= number_format($b['price'], 3) ?></td>
                        <td class="border px-3 py-2"><?= number_format($b['total'], 3) ?></td>
                        <td class="border px-3 py-2"><?= date('Y-m-d', strtotime($b['created_at'])) ?></td>
                        <td class="border px-3 py-2 text-center">
                            <button onclick='openEditModal(<?= json_encode($b) ?>)' class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded">Edit</button>
                            <a href="?delete=<?= $b['id'] ?>" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded ml-2">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Edit Buy</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Quantity</label>
                <input type="number" step="0.001" name="qty" id="edit_qty" class="border rounded w-full p-2">
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Price</label>
                <input type="number" step="0.001" name="price" id="edit_price" class="border rounded w-full p-2">
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
    document.getElementById('edit_qty').value = parseFloat(data.qty).toFixed(3);
    document.getElementById('edit_price').value = parseFloat(data.price).toFixed(3);
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
