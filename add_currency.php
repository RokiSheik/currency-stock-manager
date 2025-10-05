<?php
require 'config.php';
require 'header.php';

// Handle Add Currency
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = trim($_POST['name'] ?? '');

    if ($code && $name) {
        try {
            // Check if code exists
            $stmt = $pdo->prepare("SELECT id FROM currencies WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                flash("Currency code '$code' already exists!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO currencies (code, name) VALUES (?, ?)");
                $stmt->execute([$code, $name]);
                flash("Currency '$code - $name' added successfully!");
            }
            header("Location: add_currency.php");
            exit;
        } catch (Exception $e) {
            flash("Error: " . $e->getMessage());
        }
    } else {
        flash("Please fill both code and name.");
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM currencies WHERE id = ?");
    $stmt->execute([$id]);
    flash("Currency deleted successfully!");
    header("Location: add_currency.php");
    exit;
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    if ($code && $name) {
        $stmt = $pdo->prepare("UPDATE currencies SET code = ?, name = ? WHERE id = ?");
        $stmt->execute([$code, $name, $id]);
        flash("Currency updated successfully!");
        header("Location: add_currency.php");
        exit;
    } else {
        flash("Please fill both code and name.");
    }
}

// Fetch all currencies
$currencies = $pdo->query("SELECT * FROM currencies ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-md mx-auto bg-white p-6 mt-6 rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-4">Add Currency</h2>
    <form method="POST">
        <input type="hidden" name="id" value="">
        <label class="block mb-2 font-medium">Currency Code</label>
        <input type="text" name="code" class="border rounded w-full p-2 mb-4" placeholder="e.g., USD" required>

        <label class="block mb-2 font-medium">Currency Name</label>
        <input type="text" name="name" class="border rounded w-full p-2 mb-4" placeholder="e.g., US Dollar" required>

        <button type="submit" name="add" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add Currency</button>
        <button type="submit" name="edit" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 ml-2">Update Currency</button>
    </form>
</div>

<!-- Currency List -->
<div class="max-w-md mx-auto bg-white p-6 mt-6 rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-4">Existing Currencies</h2>
    <table class="min-w-full table-auto border-collapse">
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="border p-2 text-left">Code</th>
                <th class="border p-2 text-left">Name</th>
                <th class="border p-2 text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($currencies as $c): ?>
            <tr class="hover:bg-gray-50">
                <td class="border p-2"><?= htmlspecialchars($c['code']) ?></td>
                <td class="border p-2"><?= htmlspecialchars($c['name']) ?></td>
                <td class="border p-2 text-center">
                    <a href="add_currency.php?edit_id=<?= $c['id'] ?>" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">Edit</a>
                    <a href="add_currency.php?delete=<?= $c['id'] ?>" onclick="return confirm('Are you sure?');" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($currencies) === 0): ?>
            <tr>
                <td colspan="3" class="border p-4 text-center text-gray-500">No currencies found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'footer.php'; ?>
