<?php
require 'config.php';
include 'header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;

    // Insert capital
    $stmt = $pdo->prepare("INSERT INTO capital (amount, created_at) VALUES (?, NOW())");
    $stmt->execute([$amount]);

    echo "<div class='max-w-lg mx-auto bg-green-100 text-green-800 p-3 rounded mb-4'>
            Capital added successfully!
          </div>";
}

// Get last added capital
$stmt = $pdo->query("SELECT amount, created_at FROM capital ORDER BY created_at DESC LIMIT 1");
$latestCapital = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="max-w-lg mx-auto bg-white shadow rounded-lg p-6 mt-6">
    <h1 class="text-2xl font-bold mb-4 text-center">Add Capital</h1>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Capital Amount (৳)</label>
            <input type="number" step="0.01" name="amount" required
                   class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">
            Add Capital
        </button>
    </form>

    <?php if ($latestCapital): ?>
        <div class="mt-6 text-center text-gray-700">
            <p class="text-sm">Last Capital Added:</p>
            <p class="font-semibold text-lg text-blue-600">
                ৳<?= number_format($latestCapital['amount'], 2) ?>
            </p>
            <p class="text-xs text-gray-500"><?= date('Y-m-d H:i', strtotime($latestCapital['created_at'])) ?></p>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
