<?php
require 'config.php';
require 'header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currency_id = $_POST['currency_id'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $buy_price = $_POST['buy_price'] ?? '';

    if ($currency_id && is_numeric($stock) && is_numeric($buy_price)) {
        try {
            $pdo->beginTransaction();

            // Check if currency exists
            $stmt = $pdo->prepare("SELECT * FROM currencies WHERE id = ?");
            $stmt->execute([$currency_id]);
            $currency = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currency) {
                throw new Exception("Currency not found.");
            }

            // Lock the holdings row (if exists)
            $stmt = $pdo->prepare("SELECT * FROM holdings WHERE currency_id = ? FOR UPDATE");
            $stmt->execute([$currency_id]);
            $holding = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($holding) {
                $prev_stock = $holding['stock'];
                $prev_avg_price = $holding['avg_price'];

                $new_total = ($prev_stock * $prev_avg_price) + ($stock * $buy_price);
                $new_stock = $prev_stock + $stock;
                $new_avg_price = $new_total / $new_stock;

                // Update holdings
                $stmt = $pdo->prepare("UPDATE holdings SET stock = ?, avg_price = ? WHERE currency_id = ?");
                $stmt->execute([$new_stock, $new_avg_price, $currency_id]);
            } else {
                // Insert new holding
                $stmt = $pdo->prepare("INSERT INTO holdings (currency_id, stock, avg_price) VALUES (?, ?, ?)");
                $stmt->execute([$currency_id, $stock, $buy_price]);

                $prev_stock = 0;
                $prev_avg_price = 0;
            }

            // Insert transaction record using your table columns
            $stmt = $pdo->prepare("
                INSERT INTO transactions 
                (currency_id, type, qty, price, total, profit, prev_qty, prev_avg_price, created_at)
                VALUES (?, 'buy', ?, ?, ?, 0, ?, ?, NOW())
            ");
            $stmt->execute([
                $currency_id,
                $stock,                // qty
                $buy_price,            // price
                $stock * $buy_price,   // total
                $prev_stock,           // prev_qty
                $prev_avg_price        // prev_avg_price
            ]);

            $pdo->commit();
            flash("Buy transaction recorded successfully.");
            header("Location: buy.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash("Error recording buy: " . $e->getMessage());
            header("Location: buy.php");
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
    <h2 class="text-2xl font-bold mb-4">Buy Currency</h2>
    <form method="POST">
        <label class="block mb-2 font-medium">Select Currency</label>
        <select name="currency_id" class="border rounded w-full p-2 mb-4" required>
            <option value="">-- Select --</option>
            <?php foreach ($currencies as $c): ?>
                <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="block mb-2 font-medium">Stock Amount</label>
        <input type="number" step="0.01" name="stock" class="border rounded w-full p-2 mb-4" required>

        <label class="block mb-2 font-medium">Buy Price</label>
        <input type="number" step="0.01" name="buy_price" class="border rounded w-full p-2 mb-4" required>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Submit</button>
    </form>
</div>

<?php require 'footer.php'; ?>
