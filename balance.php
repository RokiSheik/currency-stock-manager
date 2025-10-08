<?php
require 'config.php';
include 'header.php';

// Helper function to get totals
// function getTotals($pdo, $date)
// {
//     // Total expense for the date
//     $stmt = $pdo->prepare("SELECT SUM(amount) AS total_expense FROM expenses WHERE DATE(created_at) = ?");
//     $stmt->execute([$date]);
//     $expense = $stmt->fetchColumn() ?? 0;

//     // Total sell value for the date
//     $stmt = $pdo->prepare("SELECT SUM(total) AS total_sell FROM transactions WHERE type='sell' AND DATE(created_at) = ?");
//     $stmt->execute([$date]);
//     $sell = $stmt->fetchColumn() ?? 0;

//     // Stock value calculation up to that date
//     $stmt = $pdo->prepare("
//         SELECT c.id, c.name, 
//                SUM(CASE WHEN t.type='buy' THEN t.qty ELSE -t.qty END) AS net_qty,
//                SUM(CASE WHEN t.type='buy' THEN t.qty * t.price ELSE 0 END) AS total_buy_value
//         FROM transactions t
//         JOIN currencies c ON c.id = t.currency_id
//         WHERE DATE(t.created_at) <= ?
//         GROUP BY c.id, c.name
//     ");
//     $stmt->execute([$date]);
//     $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     $total_stock_value = 0;
//     foreach ($stocks as $s) {
//         if ($s['net_qty'] > 0) {
//             $avg_price = $s['total_buy_value'] / ($s['net_qty'] ?: 1);
//             $total_stock_value += $s['net_qty'] * $avg_price;
//         }
//     }

//     // Final balance
//     $balance = ($total_stock_value + $expense) - $sell;

//     return [
//         'expense' => $expense,
//         'sell' => $sell,
//         'stock' => $total_stock_value,
//         'balance' => $balance
//     ];
// }
function getTotals($pdo, $date)
{
    // Total expense for the date
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total_expense FROM expenses WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $expense = $stmt->fetchColumn() ?? 0;

    // Total sell value for the date
    $stmt = $pdo->prepare("SELECT SUM(total) AS total_sell FROM transactions WHERE type='sell' AND DATE(created_at) = ?");
    $stmt->execute([$date]);
    $sell = $stmt->fetchColumn() ?? 0;

    // Stock value up to date
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

    $total_stock_value = 0;
    foreach ($stocks as $s) {
        if ($s['net_qty'] > 0) {
            $avg_price = $s['total_buy_value'] / ($s['net_qty'] ?: 1);
            $total_stock_value += $s['net_qty'] * $avg_price;
        }
    }

    // Capital (latest)
    $stmt = $pdo->query("SELECT amount FROM capital ORDER BY created_at DESC LIMIT 1");
    $capital = $stmt->fetchColumn() ?? 0;

    // Final balance
    $balance = ($total_stock_value + $expense + $capital) - $sell;

    return [
        'expense' => $expense,
        'sell' => $sell,
        'stock' => $total_stock_value,
        'capital' => $capital,
        'balance' => $balance
    ];
}


// Dates
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Get data
$todayData = getTotals($pdo, $today);
$yesterdayData = getTotals($pdo, $yesterday);

// $profit = $todayData['balance'] - $yesterdayData['balance'];
$profit = $yesterdayData['balance'] - $todayData['balance'];
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

        <!-- Total Expense -->
        <div class="bg-white p-5 shadow rounded-lg text-center">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Today’s Expenses</h2>
            <p class="text-3xl font-bold text-orange-500">৳<?= number_format($todayData['expense'], 2) ?></p>
        </div>

        <!-- Capital -->
        <div class="bg-white p-5 shadow rounded-lg text-center">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Total Capital</h2>
            <p class="text-3xl font-bold text-purple-600">৳<?= number_format($todayData['capital'], 2) ?></p>
        </div>

    </div>

    <div class="mt-10 bg-white p-6 shadow rounded-lg">
        <h2 class="text-xl font-semibold mb-4">Detailed Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-500">Total Stock Value (Today)</p>
                <p class="text-lg font-bold"><?= number_format($todayData['stock'], 2) ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-500">Total Sell (Today)</p>
                <p class="text-lg font-bold"><?= number_format($todayData['sell'], 2) ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-500">Total Expense (Today)</p>
                <p class="text-lg font-bold"><?= number_format($todayData['expense'], 2) ?></p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>