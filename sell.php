<?php
require 'config.php';
require 'header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currency_id = $_POST['currency_id'] ?? '';
    $sell_qty = $_POST['stock'] ?? '';
    $sell_price = $_POST['sell_price'] ?? '';

    if ($currency_id && is_numeric($sell_qty) && is_numeric($sell_price)) {
        try {
            $pdo->beginTransaction();

            // Lock holding for this currency
            $stmt = $pdo->prepare("SELECT * FROM holdings WHERE currency_id = ? FOR UPDATE");
            $stmt->execute([$currency_id]);
            $holding = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$holding || $holding['stock'] < $sell_qty) {
                throw new Exception("Insufficient stock available for sale.");
            }

            $prev_qty = $holding['stock'];
            $prev_avg_price = $holding['avg_price'];

            // Calculate profit
            $revenue = $sell_qty * $sell_price;
            $cost = $sell_qty * $prev_avg_price;
            $profit = $revenue - $cost;

            // Update remaining stock
            $new_stock = $prev_qty - $sell_qty;
            $stmt = $pdo->prepare("UPDATE holdings SET stock = ? WHERE currency_id = ?");
            $stmt->execute([$new_stock, $currency_id]);

            // Insert transaction using correct column names
            $stmt = $pdo->prepare("
                INSERT INTO transactions
                (currency_id, type, qty, price, total, profit, prev_qty, prev_avg_price, created_at)
                VALUES (?, 'sell', ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $currency_id,
                $sell_qty,          // qty
                $sell_price,        // price
                $revenue,           // total
                $profit,            // profit
                $prev_qty,          // prev_qty
                $prev_avg_price     // prev_avg_price
            ]);

            $pdo->commit();
            flash("Sell transaction recorded successfully. Revenue: " . number_format($revenue, 2));
            header("Location: sell.php");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash("Error recording sell: " . $e->getMessage());
            header("Location: sell.php");
            exit;
        }
    } else {
        flash("Please fill all fields correctly.");
    }
}

// Fetch all currencies
$currencies = $pdo->query("SELECT * FROM currencies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-lg mx-auto bg-white shadow-md rounded-lg p-6 mt-6">
    <h2 class="text-2xl font-bold mb-4">Sell Currency</h2>
    <form method="POST">
        <label class="block mb-2 font-medium">Select Currency</label>
        <select name="currency_id" class="border rounded w-full p-2 mb-4" required>
            <option value="">-- Select --</option>
            <?php foreach ($currencies as $c): ?>
                <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="block mb-2 font-medium">Sell Quantity</label>
        <input type="number" step="0.01" name="stock" class="border rounded w-full p-2 mb-4" required>

        <label class="block mb-2 font-medium">Sell Price</label>
        <input type="number" step="0.01" name="sell_price" class="border rounded w-full p-2 mb-4" required>

        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Sell</button>
    </form>
</div>

<?php require 'footer.php'; ?>
