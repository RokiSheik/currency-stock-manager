<?php
require 'config.php';
require 'header.php';

// Fetch all holdings with currency info
$stmt = $pdo->query("
    SELECT h.id, c.name AS currency_name, c.code AS currency_code, h.stock, h.avg_price,
           (h.stock * h.avg_price) AS total_value
    FROM holdings h
    JOIN currencies c ON h.currency_id = c.id
    ORDER BY c.name ASC
");
$holdings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg p-6 mt-6">
    <h2 class="text-2xl font-bold mb-4">Current Stock</h2>

    <table class="min-w-full table-auto border-collapse">
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="border p-2 text-left">Currency</th>
                <th class="border p-2 text-left">Code</th>
                <th class="border p-2 text-right">Stock Amount</th>
                <th class="border p-2 text-right">Average Buy Price</th>
                <th class="border p-2 text-right">Total Value</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($holdings): ?>
                <?php foreach ($holdings as $h): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border p-2"><?= htmlspecialchars($h['currency_name']) ?></td>
                        <td class="border p-2"><?= htmlspecialchars($h['currency_code']) ?></td>
                        <td class="border p-2 text-right"><?= number_format($h['stock'], 2) ?></td>
                        <td class="border p-2 text-right"><?= number_format($h['avg_price'], 2) ?></td>
                        <td class="border p-2 text-right"><?= number_format($h['total_value'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="border p-4 text-center text-gray-500">No holdings found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'footer.php'; ?>
