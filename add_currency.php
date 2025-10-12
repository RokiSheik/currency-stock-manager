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

// Handle Edit Currency
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

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM currencies WHERE id = ?");
    $stmt->execute([$id]);
    flash("Currency deleted successfully!");
    header("Location: add_currency.php");
    exit;
}

// Prefill form if editing
$editCurrency = null;
if (isset($_GET['edit_id'])) {
    $id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM currencies WHERE id = ?");
    $stmt->execute([$id]);
    $editCurrency = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all currencies
$currencies = $pdo->query("SELECT * FROM currencies ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-md mx-auto bg-white p-6 mt-6 rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-4"><?= $editCurrency ? "Edit Currency" : "Add Currency" ?></h2>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="id" value="<?= $editCurrency['id'] ?? '' ?>">
        <div>
            <label class="block mb-1 font-medium">Currency Code</label>
            <input type="text" name="code" class="border rounded w-full p-2" placeholder="e.g., USD" required
                   value="<?= htmlspecialchars($editCurrency['code'] ?? '') ?>">
        </div>
        <div>
            <label class="block mb-1 font-medium">Currency Name</label>
            <input type="text" name="name" class="border rounded w-full p-2" placeholder="e.g., US Dollar" required
                   value="<?= htmlspecialchars($editCurrency['name'] ?? '') ?>">
        </div>
        <div class="flex gap-2">
            <?php if ($editCurrency): ?>
                <button type="submit" name="edit" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Update Currency</button>
                <a href="add_currency.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add Currency</button>
            <?php endif; ?>
        </div>
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
                <td class="border p-2 text-center flex justify-center gap-2">
                    <!-- Edit Icon -->
                    <a href="add_currency.php?edit_id=<?= $c['id'] ?>" class="text-yellow-500 hover:text-yellow-600" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2m-1 0v2m0 0v12h-2V7h2zM19 5h-2v14h2V5zM5 5H3v14h2V5z" />
                        </svg>
                    </a>
                    <!-- Delete Icon -->
                    <a href="add_currency.php?delete=<?= $c['id'] ?>" onclick="return confirm('Are you sure?');" class="text-red-500 hover:text-red-600" title="Delete">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
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
