<!-- header.php -->
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Currency Stock Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">
    <nav class="bg-white shadow mb-6">
        <div class="container mx-auto flex justify-between items-center p-4">
            <div class="font-bold text-lg">Currency Stock Manager</div>

            <!-- Hamburger Button (Mobile) -->
            <div class="sm:hidden">
                <button id="menu-btn" class="text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <!-- Menu Links -->
            <div id="menu" class="hidden sm:flex space-x-3">
                <a href="index.php" class="px-3 py-1 rounded hover:bg-gray-100">Dashboard</a>
                <a href="add_currency.php" class="px-3 py-1 rounded hover:bg-gray-100">Add Currency</a>
                <a href="buy.php" class="px-3 py-1 rounded hover:bg-gray-100">Buy</a>
                <a href="sell.php" class="px-3 py-1 rounded hover:bg-gray-100">Sell</a>
                <a href="stock.php" class="px-3 py-1 rounded hover:bg-gray-100">Stock</a>
            </div>
        </div>

        <!-- Mobile Menu (Dropdown) -->
        <div id="mobile-menu" class="sm:hidden hidden px-4 pb-4">
            <a href="index.php" class="block px-3 py-2 rounded hover:bg-gray-100">Dashboard</a>
            <a href="add_currency.php" class="block px-3 py-2 rounded hover:bg-gray-100">Add Currency</a>
            <a href="buy.php" class="block px-3 py-2 rounded hover:bg-gray-100">Buy</a>
            <a href="sell.php" class="block px-3 py-2 rounded hover:bg-gray-100">Sell</a>
            <a href="stock.php" class="block px-3 py-2 rounded hover:bg-gray-100">Stock</a>
        </div>
    </nav>

    <div class="container mx-auto">
        <?php if ($msg = flash()): ?>
            <div class="mb-4 p-3 bg-green-100 border-l-4 border-green-500"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
    </div>

    <script>
        // Toggle mobile menu
        const btn = document.getElementById('menu-btn');
        const menu = document.getElementById('mobile-menu');
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>
