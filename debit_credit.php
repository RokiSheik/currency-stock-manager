<?php
require 'config.php';
include 'header.php';

// ------------------------------
// 1️⃣ Handle Form Submission
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $note = $_POST['note'] ?? '';

    if ($type && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO debit_credit (type, amount, note) VALUES (?, ?, ?)");
        $stmt->execute([$type, $amount, $note]);
        flash("New $type added successfully!");
        header("Location: debit_credit.php");
        exit;
    } else {
        flash("Please select type and enter a valid amount.");
    }
}

// ------------------------------
// 2️⃣ Filter Logic
// ------------------------------
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
    case 'this_month':
        $start_date = date('Y-m-01 00:00:00');
        break;
    case 'this_year':
        $start_date = date('Y-01-01 00:00:00');
        break;
    default:
        $start_date = null;
        $end_date = null;
}

$params = [];
$date_condition = '';
if ($start_date) {
    $date_condition = "WHERE created_at BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
}

// ------------------------------
// 3️⃣ Fetch Records
// ------------------------------
$sql = "SELECT * FROM debit_credit";
if ($date_condition) $sql .= " $date_condition";
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
$total_debit = 0;
$total_credit = 0;
foreach ($records as $r) {
    if ($r['type'] === 'debit') $total_debit += $r['amount'];
    else $total_credit += $r['amount'];
}
?>

<div class="max-w-6xl mx-auto p-4 sm:p-6">
    <h1 class="text-2xl sm:text-3xl font-bold mb-6 text-center">Debit & Credit Manager</h1>

    <!-- Flash Message -->
    <?php if ($msg = flash()): ?>
        <div class="mb-4 p-3 bg-green-100 border-l-4 border-green-500 text-green-700">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <!-- Add Form -->
    <div class="bg-white p-5 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold mb-3">Add New Transaction</h2>
        <form method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <select name="type" class="border rounded p-2" required>
                <option value="">Select Type</option>
                <option value="debit">Debit</option>
                <option value="credit">Credit</option>
            </select>

            <input type="number" step="0.01" name="amount" placeholder="Amount" class="border rounded p-2" required>

            <input type="text" name="note" placeholder="Note (optional)" class="border rounded p-2">

            <button type="submit" class="bg-blue-600 text-white rounded p-2 hover:bg-blue-700 transition">
                Add
            </button>
        </form>
    </div>

    <!-- Filter -->
    <div class="mb-6">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <label class="font-medium">Filter by:</label>
            <select name="filter" class="border rounded p-2" onchange="this.form.submit()">
                <option value="all" <?= $filter=='all'?'selected':'' ?>>All</option>
                <option value="today" <?= $filter=='today'?'selected':'' ?>>Today</option>
                <option value="this_week" <?= $filter=='this_week'?'selected':'' ?>>This Week</option>
                <option value="this_month" <?= $filter=='this_month'?'selected':'' ?>>This Month</option>
                <option value="this_year" <?= $filter=='this_year'?'selected':'' ?>>This Year</option>
            </select>
            <a href="debit_credit.php" class="text-blue-600 hover:underline ml-2">Reset</a>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 mb-6">
        <div class="bg-red-100 p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold text-gray-700">Total Debit</h2>
            <p class="text-2xl font-bold text-red-700 mt-2">৳<?= number_format($total_debit, 2) ?></p>
        </div>
        <div class="bg-green-100 p-4 rounded-lg shadow text-center">
            <h2 class="text-lg font-semibold text-gray-700">Total Credit</h2>
            <p class="text-2xl font-bold text-green-700 mt-2">৳<?= number_format($total_credit, 2) ?></p>
        </div>
    </div>

    <!-- Records Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full border-collapse text-sm sm:text-base">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="border p-2 text-left">Date</th>
                    <th class="border p-2 text-left">Type</th>
                    <th class="border p-2 text-right">Amount</th>
                    <th class="border p-2 text-left">Note</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($records): ?>
                    <?php foreach ($records as $r): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border p-2"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['created_at']))) ?></td>
                            <td class="border p-2">
                                <span class="<?= $r['type']=='debit'?'text-red-600':'text-green-600' ?>">
                                    <?= ucfirst($r['type']) ?>
                                </span>
                            </td>
                            <td class="border p-2 text-right"><?= number_format($r['amount'], 2) ?></td>
                            <td class="border p-2"><?= htmlspecialchars($r['note']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="border p-4 text-center text-gray-500">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
