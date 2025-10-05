<?php
require 'config.php';
require 'header.php';

// ---------------------------
// 1️⃣ Handle filter selection
// ---------------------------
$filter = $_GET['filter'] ?? 'all';
$start_date = null;
$end_date = date('Y-m-d 23:59:59');

switch ($filter) {
    case 'today':
        $start_date = date('Y-m-d 00:00:00');
        break;
    case 'this_week':
        $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
        break;
    case 'last_week':
        $start_date = date('Y-m-d 00:00:00', strtotime('monday last week'));
        $end_date = date('Y-m-d 23:59:59', strtotime('sunday last week'));
        break;
    case 'this_month':
        $start_date = date('Y-m-01 00:00:00');
        break;
    case 'last_month':
        $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $end_date = date('Y-m-t 23:59:59', strtotime('last day of last month'));
        break;
    case 'last_3_months':
        $start_date = date('Y-m-01 00:00:00', strtotime('-3 months'));
        break;
    case 'last_6_months':
        $start_date = date('Y-m-01 00:00:00', strtotime('-6 months'));
        break;
    case 'this_year':
        $start_date = date('Y-01-01 00:00:00');
        break;
    case 'last_year':
        $start_date = date('Y-01-01 00:00:00', strtotime('-1 year'));
        $end_date = date('Y-12-31 23:59:59', strtotime('-1 year'));
        break;
    default:
        $start_date = null;
        $end_date = null;
}

$params = [];
$date_condition = '';
if ($start_date) {
    $date_condition = " WHERE t.created_at BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
}

// ---------------------------
// 2️⃣ Fetch totals
// ---------------------------
$sql_totals = "SELECT 
    SUM(CASE WHEN type = 'sell' THEN total ELSE 0 END) AS total_revenue,
    SUM(CASE WHEN type = 'sell' THEN profit ELSE 0 END) AS total_profit
FROM transactions t";

if ($date_condition) $sql_totals .= $date_condition;

$stmt = $pdo->prepare($sql_totals);
$stmt->execute($params);
$totals = $stmt->fetch(PDO::FETCH_ASSOC);

$total_revenue = $totals['total_revenue'] ?? 0;
$total_profit = $totals['total_profit'] ?? 0;

// ---------------------------
// 3️⃣ Fetch total stock value
// ---------------------------
$stmt = $pdo->query("SELECT SUM(stock * avg_price) AS total_stock_value FROM holdings");
$total_stock_value = $stmt->fetchColumn() ?? 0;

// ---------------------------
// 4️⃣ Fetch transactions
// ---------------------------
$sql_transactions = "
    SELECT t.*, c.name AS currency_name
    FROM transactions t
    JOIN currencies c ON c.id = t.currency_id
";

if ($date_condition) $sql_transactions .= $date_condition;
$sql_transactions .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql_transactions);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ---------------------------
      5️⃣ Dashboard HTML
---------------------------- -->
<div class="max-w-6xl mx-auto p-4 sm:p-6">
    <h1 class="text-2xl sm:text-3xl font-bold mb-4 sm:mb-6">Dashboard</h1>

    <!-- Filter -->
    <div class="mb-4 sm:mb-6">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <label class="font-medium">Filter by Date:</label>
            <select name="filter" class="border rounded p-2" onchange="this.form.submit()">
                <option value="all" <?= $filter=='all' ? 'selected' : '' ?>>All Time</option>
                <option value="today" <?= $filter=='today' ? 'selected' : '' ?>>Today</option>
                <option value="this_week" <?= $filter=='this_week' ? 'selected' : '' ?>>This Week</option>
                <option value="last_week" <?= $filter=='last_week' ? 'selected' : '' ?>>Last Week</option>
                <option value="this_month" <?= $filter=='this_month' ? 'selected' : '' ?>>This Month</option>
                <option value="last_month" <?= $filter=='last_month' ? 'selected' : '' ?>>Last Month</option>
                <option value="last_3_months" <?= $filter=='last_3_months' ? 'selected' : '' ?>>Last 3 Months</option>
                <option value="last_6_months" <?= $filter=='last_6_months' ? 'selected' : '' ?>>Last 6 Months</option>
                <option value="this_year" <?= $filter=='this_year' ? 'selected' : '' ?>>This Year</option>
                <option value="last_year" <?= $filter=='last_year' ? 'selected' : '' ?>>Last Year</option>
            </select>
            <a href="index.php" class="text-blue-600 hover:underline ml-2">Reset</a>
        </form>
    </div>

    <!-- Totals -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
        <div class="bg-blue-100 p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold text-gray-600">Total Revenue</h2>
            <p class="text-xl sm:text-2xl font-bold text-blue-700 mt-2"><?= number_format($total_revenue, 2) ?></p>
        </div>

        <div class="bg-green-100 p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold text-gray-600">Total Profit</h2>
            <p class="text-xl sm:text-2xl font-bold text-green-700 mt-2"><?= number_format($total_profit, 2) ?></p>
        </div>

        <div class="bg-yellow-100 p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold text-gray-600">Total Stock Value</h2>
            <p class="text-xl sm:text-2xl font-bold text-yellow-700 mt-2"><?= number_format($total_stock_value, 2) ?></p>
        </div>
    </div>

    <!-- Transactions -->
    <h2 class="text-xl sm:text-2xl font-bold mb-2 sm:mb-4">Recent Transactions</h2>

    <div class="overflow-x-auto">
        <div class="min-w-full inline-block align-middle">
            <table class="min-w-full border-collapse text-sm sm:text-base">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="border p-2 text-left text-xs sm:text-sm">Date</th>
                        <th class="border p-2 text-left text-xs sm:text-sm">Currency</th>
                        <th class="border p-2 text-left text-xs sm:text-sm">Type</th>
                        <th class="border p-2 text-right text-xs sm:text-sm">Qty</th>
                        <th class="border p-2 text-right text-xs sm:text-sm">Price</th>
                        <th class="border p-2 text-right text-xs sm:text-sm">Total</th>
                        <th class="border p-2 text-right text-xs sm:text-sm">Profit</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($transactions): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-2 text-xs sm:text-sm"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($t['created_at']))) ?></td>
                                <td class="border p-2 text-xs sm:text-sm"><?= htmlspecialchars($t['currency_name']) ?></td>
                                <td class="border p-2 text-xs sm:text-sm">
                                    <span class="<?= $t['type'] === 'buy' ? 'text-blue-600' : 'text-green-600' ?>">
                                        <?= ucfirst($t['type']) ?>
                                    </span>
                                </td>
                                <td class="border p-2 text-right text-xs sm:text-sm"><?= number_format($t['qty'], 2) ?></td>
                                <td class="border p-2 text-right text-xs sm:text-sm"><?= number_format($t['price'], 2) ?></td>
                                <td class="border p-2 text-right text-xs sm:text-sm"><?= number_format($t['total'], 2) ?></td>
                                <td class="border p-2 text-right text-xs sm:text-sm">
                                    <?= $t['type'] === 'sell' ? number_format($t['profit'], 2) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="border p-4 text-center text-gray-500 text-xs sm:text-sm">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>
