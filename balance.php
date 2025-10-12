<?php
require 'config.php';
include 'header.php';

// -----------------------------
// Function: Calculate Daily Totals (uses payments, not sell)
// -----------------------------
function getTotals($pdo, $date)
{
    // Total expense for that date (expenses.payment_date or expense_date depending on your schema)
    // Adjust column name if your expenses table uses expense_date (most of your code used that)
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE DATE(expense_date) = ?");
    $stmt->execute([$date]);
    $expense = $stmt->fetchColumn() ?? 0;

    // Total payments for that date (we subtract payments)
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE DATE(payment_date) = ?");
    $stmt->execute([$date]);
    $payment = $stmt->fetchColumn() ?? 0;

    // Capital for that date (sum of capital records on that date)
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM capital WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $capital = $stmt->fetchColumn() ?? 0;

    // Stock value up to and including the given date
    $stmt = $pdo->prepare("
        SELECT c.id, c.name,
               SUM(CASE WHEN t.type='buy' THEN t.qty ELSE -t.qty END) AS net_qty,
               SUM(CASE WHEN t.type='buy' THEN t.qty * t.price ELSE 0 END) AS total_buy_value
        FROM transactions t
        JOIN currencies c ON c.id = t.currency_id
        WHERE DATE(t.created_at) <= ?
        GROUP BY c.id, c.name
    ");
    $stmt->execute([$date]);
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_stock_value = 0.0;
    foreach ($stocks as $s) {
        $net_qty = (float) $s['net_qty'];
        $total_buy_value = (float) $s['total_buy_value'];
        if ($net_qty > 0) {
            $avg_price = $total_buy_value / ($net_qty ?: 1);
            $total_stock_value += $net_qty * $avg_price;
        }
    }

    // Final balance: Stock + Capital + Expense - Payment
    $balance = ($total_stock_value + $capital + $expense) - $payment;

    return [
        'stock'   => $total_stock_value,
        'capital' => $capital,
        'expense' => $expense,
        'payment' => $payment,
        'balance' => $balance
    ];
}

// -----------------------------
// Dates (default)
 // -----------------------------
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Get today's and yesterday's totals (using payments)
$todayData = getTotals($pdo, $today);
$yesterdayData = getTotals($pdo, $yesterday);

// Profit = today's balance - yesterday's balance
$profit = $todayData['balance'] - $yesterdayData['balance'];

// Handle selected date from form
$selectedDate = $_POST['selected_date'] ?? null;
$selectedData = $selectedDate ? getTotals($pdo, $selectedDate) : null;
?>

<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6 text-center">Daily Balance Overview</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today's Balance -->
        <div class="bg-white p-5 shadow rounded-lg text-center">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Today’s Balance</h2>
            <p class="text-3xl font-bold text-green-600">৳<?= number_format($todayData['balance'], 2) ?></p>
        </div>

        <!-- Yesterday’s Balance -->
        <div class="bg-white p-5 shadow rounded-lg text-center">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Yesterday’s Balance</h2>
            <p class="text-3xl font-bold text-blue-600">৳<?= number_format($yesterdayData['balance'], 2) ?></p>
        </div>

        <!-- Profit -->
        <div class="bg-white p-5 shadow rounded-lg text-center">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Profit / Difference</h2>
            <p class="text-3xl font-bold <?= $profit >= 0 ? 'text-green-500' : 'text-red-500' ?>">
                ৳<?= number_format($profit, 2) ?>
            </p>
        </div>

        <!-- Today's Expense -->
        <div class="bg-white p-5 shadow rounded-lg text-center">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Today’s Expenses</h2>
            <p class="text-3xl font-bold text-orange-500">৳<?= number_format($todayData['expense'], 2) ?></p>
        </div>

        <!-- Today's Capital -->
        <div class="bg-white p-5 shadow rounded-lg text-center">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Today’s Capital</h2>
            <p class="text-3xl font-bold text-purple-600">৳<?= number_format($todayData['capital'], 2) ?></p>
        </div>

        <!-- Today's Payments -->
        <div class="bg-white p-5 shadow rounded-lg text-center">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Today’s Payments</h2>
            <p class="text-3xl font-bold text-red-600">৳<?= number_format($todayData['payment'], 2) ?></p>
        </div>
    </div>

    <div class="mt-10 bg-white p-6 shadow rounded-lg">
        <h2 class="text-xl font-semibold mb-4">Detailed Summary (Today)</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-500">Total Stock Value</p>
                <p class="text-lg font-bold"><?= number_format($todayData['stock'], 2) ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-500">Total Capital</p>
                <p class="text-lg font-bold"><?= number_format($todayData['capital'], 2) ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-500">Total Expense</p>
                <p class="text-lg font-bold"><?= number_format($todayData['expense'], 2) ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-500">Total Payment</p>
                <p class="text-lg font-bold"><?= number_format($todayData['payment'], 2) ?></p>
            </div>
        </div>
    </div>

    <!-- Date Selector -->
    <div class="mt-10 bg-white p-6 shadow rounded-lg">
        <h2 class="text-xl font-semibold mb-4 text-center">Check Balance by Date</h2>
        <form method="POST" class="flex flex-col sm:flex-row justify-center items-center gap-4">
            <input type="date" name="selected_date" required class="border rounded p-2 focus:ring-2 focus:ring-blue-500" value="<?= htmlspecialchars($selectedDate ?? '') ?>">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Show Summary</button>
        </form>
    </div>

    <!-- Selected Date Summary -->
    <?php if ($selectedData): ?>
    <div class="mt-8 bg-white p-6 shadow rounded-lg">
        <h2 class="text-xl font-semibold mb-4 text-center">Summary for <?= htmlspecialchars($selectedDate) ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
            <div class="p-4 border rounded">
                <p class="text-gray-500">Total Capital</p>
                <p class="text-lg font-bold text-purple-600">৳<?= number_format($selectedData['capital'], 2) ?></p>
            </div>
            <div class="p-4 border rounded">
                <p class="text-gray-500">Total Expense</p>
                <p class="text-lg font-bold text-orange-600">৳<?= number_format($selectedData['expense'], 2) ?></p>
            </div>
            <div class="p-4 border rounded">
                <p class="text-gray-500">Total Payments</p>
                <p class="text-lg font-bold text-red-600">৳<?= number_format($selectedData['payment'], 2) ?></p>
            </div>
            <div class="p-4 border rounded">
                <p class="text-gray-500">Total Stock Value (up to date)</p>
                <p class="text-lg font-bold text-green-600">৳<?= number_format($selectedData['stock'], 2) ?></p>
            </div>

            <div class="p-4 border rounded col-span-1 md:col-span-3">
                <p class="text-gray-500">Final Balance</p>
                <p class="text-2xl font-bold text-indigo-600">৳<?= number_format($selectedData['balance'], 2) ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
