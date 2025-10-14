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
// 2️⃣ Handle Edit Expense
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $amount = floatval($_POST['amount']);
    $note = trim($_POST['note']);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');

    if ($id && $title && $amount > 0) {
        $stmt = $pdo->prepare("UPDATE expenses SET title=?, amount=?, note=?, expense_date=? WHERE id=?");
        if ($stmt->execute([$title, $amount, $note, $expense_date, $id])) {
            flash("Expense updated successfully!");
            header("Location: expense.php");
            exit;
        } else {
            flash("Error updating expense!", "error");
        }
    } else {
        flash("Please fill in all required fields!", "error");
    }
}

// ---------------------------
// 3️⃣ Handle Delete Expense
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
// 4️⃣ Filter Logic
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
// 5️⃣ Fetch Expenses
// ---------------------------
$sql = "SELECT * FROM expenses";
if ($date_condition) $sql .= $date_condition;
$sql .= " ORDER BY expense_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------
// 6️⃣ Calculate Total
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
                        <tr id="row-<?= $e['id'] ?>" class="hover:bg-gray-50">
                            <td class="border p-2"><?= htmlspecialchars(date('Y-m-d', strtotime($e['expense_date']))) ?></td>
                            <td class="border p-2"><?= htmlspecialchars($e['title']) ?></td>
                            <td class="border p-2 text-right"><?= number_format($e['amount'], 2) ?></td>
                            <td class="border p-2"><?= htmlspecialchars($e['note'] ?? '-') ?></td>
                            <td class="border p-2 text-center">
                                <button onclick='openEditModal(<?= json_encode($e) ?>)' class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded text-xs sm:text-sm">Edit</button>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded ml-1 text-xs sm:text-sm">Delete</button>
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

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Edit Expense</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Title</label>
                <input type="text" name="title" id="edit_title" class="border rounded w-full p-2">
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Amount</label>
                <input type="number" step="0.01" name="amount" id="edit_amount" class="border rounded w-full p-2">
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Date</label>
                <input type="date" name="expense_date" id="edit_date" class="border rounded w-full p-2">
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Note</label>
                <input type="text" name="note" id="edit_note" class="border rounded w-full p-2">
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
    document.getElementById('edit_title').value = data.title;
    document.getElementById('edit_amount').value = parseFloat(data.amount).toFixed(2);
    document.getElementById('edit_date').value = data.expense_date;
    document.getElementById('edit_note').value = data.note;
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.target.submit();
    closeModal();
});
</script>

<?php require 'footer.php'; ?>
