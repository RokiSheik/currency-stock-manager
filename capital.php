<?php
require 'config.php';
include 'header.php';

// ✅ Add or Update Capital for a Date
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($amount > 0) {
        // Check if this date already has a record
        $check = $pdo->prepare("SELECT id FROM capital WHERE DATE(created_at) = ?");
        $check->execute([$date]);
        $existing = $check->fetchColumn();

        if ($existing) {
            // 🔄 Update existing record
            $stmt = $pdo->prepare("UPDATE capital SET amount = ? WHERE DATE(created_at) = ?");
            $stmt->execute([$amount, $date]);
            $msg = "Capital for $date updated successfully!";
            $alertClass = "bg-blue-100 text-blue-800";
        } else {
            // ➕ Insert new record
            $stmt = $pdo->prepare("INSERT INTO capital (amount, created_at) VALUES (?, ?)");
            $stmt->execute([$amount, $date]);
            $msg = "Capital for $date added successfully!";
            $alertClass = "bg-green-100 text-green-800";
        }

        echo "<div class='max-w-lg mx-auto $alertClass p-3 rounded mb-4 text-center'>$msg</div>";
    } else {
        echo "<div class='max-w-lg mx-auto bg-red-100 text-red-800 p-3 rounded mb-4 text-center'>
                Please enter a valid amount.
              </div>";
    }
}

// ✅ Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM capital WHERE id = ?");
    $stmt->execute([$id]);
    echo "<div class='max-w-lg mx-auto bg-red-100 text-red-800 p-3 rounded mb-4 text-center'>
            Capital entry deleted successfully!
          </div>";
}

// ✅ Get All Capital Records
$capitals = $pdo->query("SELECT * FROM capital ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Get Latest Capital Entry
$stmt = $pdo->query("SELECT amount, created_at FROM capital ORDER BY created_at DESC LIMIT 1");
$latestCapital = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="max-w-4xl mx-auto bg-white shadow rounded-lg p-6 mt-6">
    <h1 class="text-2xl font-bold mb-4 text-center">Add / Update Capital</h1>

    <form method="POST" class="space-y-4">
        <!-- Date Input -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Select Date</label>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" required
                   class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- Amount Input -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Capital Amount (৳)</label>
            <input type="number" step="0.01" name="amount" required
                   class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">
            Save Capital
        </button>
    </form>

    <?php if ($latestCapital): ?>
        <div class="mt-6 text-center text-gray-700">
            <p class="text-sm">Last Capital Added:</p>
            <p class="font-semibold text-lg text-blue-600">
                ৳<?= number_format($latestCapital['amount'], 2) ?>
            </p>
            <p class="text-xs text-gray-500"><?= date('Y-m-d', strtotime($latestCapital['created_at'])) ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Capital History Table -->
<div class="max-w-5xl mx-auto mt-10 bg-white shadow rounded-lg p-6">
    <h2 class="text-xl font-semibold mb-4 text-center">Capital History</h2>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="border p-2 text-left">#</th>
                    <th class="border p-2 text-left">Date</th>
                    <th class="border p-2 text-right">Amount (৳)</th>
                    <th class="border p-2 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($capitals) > 0): ?>
                    <?php foreach ($capitals as $index => $c): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border p-2"><?= $index + 1 ?></td>
                            <td class="border p-2"><?= date('Y-m-d', strtotime($c['created_at'])) ?></td>
                            <td class="border p-2 text-right"><?= number_format($c['amount'], 2) ?></td>
                            <td class="border p-2 text-center">
                                <a href="?delete=<?= $c['id'] ?>"
                                   onclick="return confirm('Are you sure you want to delete this entry?')"
                                   class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="border p-3 text-center text-gray-500">
                            No capital entries found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
