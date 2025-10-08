<?php
require 'config.php';
require 'header.php';

// ---------------------------
// 1️⃣ Handle Add Expense
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = trim($_POST['title']);
    $amount = floatval($_POST['amount']);
    $note = trim($_POST['note']);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');

    if ($title && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO expenses (title, amount, note, expense_date) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $amount, $note, $expense_date])) {
            flash("Expense added successfully!");
            header("Location: expense.php");
            exit;
        } else {
            flash("Error adding expense!", "error");
        }
    } else {
        flash("Please fill in all required fields!", "error");
    }
}

// ---------------------------
// 2️⃣ Handle Delete Expense
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    if ($stmt->execute([$id])) {
        flash("Expense deleted successfully!");
        header("Location: expense.php");
        exit;
    } else {
        flash("Error deleting expense!", "error");
    }
}

// ---------------------------
// 3️⃣ Filter Logic
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
    case 'this_year':
        $start_date = date('Y-01-01 00:00:00');
        break;
}

$params = [];
$date_condition = '';
if ($start_date) {
    $date_condition = " WHERE expense_date BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
}

// ---------------------------
// 4️⃣ Fetch Expenses
// ---------------------------
$sql = "SELECT * FROM expenses";
if ($date_condition) $sql .= $date_condition;
$sql .= " ORDER BY expense_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------
// 5️⃣ Calculate Totals
// ---------------------------
$total_expense = array_sum(array_column($expenses, 'amount'));
?>

<div class="max-w-5xl mx-auto p-4 sm:p-6">
    <h1 class="text-2xl sm:text-3xl font-bold mb-4">Expense Management</h1>

    <!-- Add Expense Form -->
    <form method="POST" class="bg-white shadow rounded-lg p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <input type="hidden" name="action" value="add">
        <input type="text" name="title" placeholder="Expense Title" class="border p-2 rounded" required>
        <input type="number" name="amount" step="0.01" placeholder="Amount" class="border p-2 rounded" required>
        <input type="date" name="expense_date" class="border p-2 rounded" value="<?= date('Y-m-d') ?>" required>
        <input type="text" name="note" placeholder="Note (optional)" class="border p-2 rounded col-span-full lg:col-span-4">
        <button type="submit" class="col-span-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Add Expense</button>
    </form>

    <!-- Filter -->
    <form method="GET" class="flex flex-wrap gap-2 items-center mb-4">
        <label class="font-medium">Filter by Date:</label>
        <select name="filter" class="border rounded p-2" onchange="this.form.submit()">
            <option value="all" <?= $filter=='all' ? 'selected' : '' ?>>All Time</option>
            <option value="today" <?= $filter=='today' ? 'selected' : '' ?>>Today</option>
            <option value="this_week" <?= $filter=='this_week' ? 'selected' : '' ?>>This Week</option>
            <option value="last_week" <?= $filter=='last_week' ? 'selected' : '' ?>>Last Week</option>
            <option value="this_month" <?= $filter=='this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="last_month" <?= $filter=='last_month' ? 'selected' : '' ?>>Last Month</option>
            <option value="this_year" <?= $filter=='this_year' ? 'selected' : '' ?>>This Year</option>
        </select>
        <a href="expense.php" class="text-blue-600 hover:underline ml-2">Reset</a>
    </form>

    <!-- Total -->
    <div class="bg-yellow-100 text-yellow-800 font-semibold p-3 rounded mb-4 text-center">
        Total Expense: <?= number_format($total_expense, 2) ?>
    </div>

    <!-- Expense List -->
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm sm:text-base">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="border p-2 text-left">Date</th>
                    <th class="border p-2 text-left">Title</th>
                    <th class="border p-2 text-right">Amount</th>
                    <th class="border p-2 text-left">Note</th>
                    <th class="border p-2 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($expenses): ?>
                    <?php foreach ($expenses as $e): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border p-2"><?= htmlspecialchars(date('Y-m-d', strtotime($e['expense_date']))) ?></td>
                            <td class="border p-2"><?= htmlspecialchars($e['title']) ?></td>
                            <td class="border p-2 text-right"><?= number_format($e['amount'], 2) ?></td>
                            <td class="border p-2"><?= htmlspecialchars($e['note'] ?? '-') ?></td>
                            <td class="border p-2 text-center">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this expense?');" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-xs sm:text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-gray-500 p-4">No expenses found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>
