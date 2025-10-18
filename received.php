<?php
require 'config.php';
require 'header.php';

// ---------------------------
// 1️⃣ Handle Add / Edit / Delete
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    $title = trim($_POST['title']);
    $amount = floatval($_POST['amount']);
    $note = trim($_POST['note']);
    $receive_date = $_POST['receive_date'] ?? date('Y-m-d');

    if ($action === 'add') {
        if ($title && $amount > 0) {
            $stmt = $pdo->prepare("INSERT INTO received (title, amount, note, receive_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $amount, $note, $receive_date]);
            flash("Received amount added successfully!");
            header("Location: received.php");
            exit;
        } else {
            flash("Please fill in all required fields!", "error");
        }
    }

    if ($action === 'edit') {
        $id = intval($_POST['id']);
        if ($id && $title && $amount > 0) {
            $stmt = $pdo->prepare("UPDATE received SET title=?, amount=?, note=?, receive_date=? WHERE id=?");
            $stmt->execute([$title, $amount, $note, $receive_date, $id]);
            flash("Received record updated successfully!");
            header("Location: received.php");
            exit;
        } else {
            flash("Please fill in all required fields!", "error");
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM received WHERE id = ?");
        $stmt->execute([$id]);
        flash("Received record deleted successfully!");
        header("Location: received.php");
        exit;
    }
}

// ---------------------------
// 2️⃣ Filter Logic
// ---------------------------
$filter = $_GET['filter'] ?? 'all';
$start_date = null;
$end_date = date('Y-m-d 23:59:59');

switch ($filter) {
    case 'today': $start_date = date('Y-m-d 00:00:00'); break;
    case 'this_week': $start_date = date('Y-m-d 00:00:00', strtotime('monday this week')); break;
    case 'last_week':
        $start_date = date('Y-m-d 00:00:00', strtotime('monday last week'));
        $end_date = date('Y-m-d 23:59:59', strtotime('sunday last week'));
        break;
    case 'this_month': $start_date = date('Y-m-01 00:00:00'); break;
    case 'last_month':
        $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $end_date = date('Y-m-t 23:59:59', strtotime('last day of last month'));
        break;
    case 'this_year': $start_date = date('Y-01-01 00:00:00'); break;
}

$params = [];
$date_condition = '';
if ($start_date) {
    $date_condition = " WHERE receive_date BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
}

// ---------------------------
// 3️⃣ Fetch Received Data
// ---------------------------
$sql = "SELECT * FROM received";
if ($date_condition) $sql .= $date_condition;
$sql .= " ORDER BY receive_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------
// 4️⃣ Calculate Total
// ---------------------------
$total_received = array_sum(array_column($records, 'amount'));
?>

<div class="max-w-5xl mx-auto p-4 sm:p-6">
    <h1 class="text-2xl sm:text-3xl font-bold mb-4">Received Management</h1>

    <!-- Add Form -->
    <form method="POST" class="bg-white shadow rounded-lg p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <input type="hidden" name="action" value="add">
        <input type="text" name="title" placeholder="Received From" class="border p-2 rounded" required>
        <input type="number" name="amount" step="0.01" placeholder="Amount" class="border p-2 rounded" required>
        <input type="date" name="receive_date" class="border p-2 rounded" value="<?= date('Y-m-d') ?>" required>
        <input type="text" name="note" placeholder="Note (optional)" class="border p-2 rounded col-span-full lg:col-span-4">
        <button type="submit" class="col-span-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Add Received</button>
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
        <a href="received.php" class="text-blue-600 hover:underline ml-2">Reset</a>
    </form>

    <!-- Total -->
    <div class="bg-blue-100 text-blue-800 font-semibold p-3 rounded mb-4 text-center">
        Total Received: <?= number_format($total_received, 2) ?>
    </div>

    <!-- Received Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm sm:text-base">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="border p-2 text-left">Date</th>
                    <th class="border p-2 text-left">From</th>
                    <th class="border p-2 text-right">Amount</th>
                    <th class="border p-2 text-left">Note</th>
                    <th class="border p-2 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($records): ?>
                    <?php foreach ($records as $r): ?>
                        <tr id="row-<?= $r['id'] ?>" class="hover:bg-gray-50">
                            <td class="border p-2"><?= htmlspecialchars(date('Y-m-d', strtotime($r['receive_date']))) ?></td>
                            <td class="border p-2"><?= htmlspecialchars($r['title']) ?></td>
                            <td class="border p-2 text-right"><?= number_format($r['amount'], 2) ?></td>
                            <td class="border p-2"><?= htmlspecialchars($r['note'] ?? '-') ?></td>
                            <td class="border p-2 text-center">
                                <button onclick='openEditModal(<?= json_encode($r) ?>)' class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded">Edit</button>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded ml-2 hover:bg-red-600 text-xs sm:text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-gray-500 p-4">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Edit Received Record</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="mb-3">
                <label class="block mb-1 font-semibold">From</label>
                <input type="text" name="title" id="edit_title" class="border rounded w-full p-2" required>
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Amount</label>
                <input type="number" step="0.01" name="amount" id="edit_amount" class="border rounded w-full p-2" required>
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-semibold">Date</label>
                <input type="date" name="receive_date" id="edit_date" class="border rounded w-full p-2" required>
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
    document.getElementById('edit_date').value = data.receive_date;
    document.getElementById('edit_note').value = data.note;
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
