<?php
require_once 'db.php';
session_start();

/*
Session cart structure used here:
$_SESSION['cart'] = [
    product_id => qty,
    ...
];
*/

// --- Handle POST actions: add, update, remove ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add to cart
    if (isset($_POST['add_to_cart'])) {
        $product_id = intval($_POST['product_id']);
        $qty = max(1, intval($_POST['qty'] ?? 1));
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (!isset($_SESSION['cart'][$product_id])) $_SESSION['cart'][$product_id] = 0;
        $_SESSION['cart'][$product_id] += $qty;
        header('Location: shop.php?msg=added');
        exit;
    }

    // Update cart quantity
    if (isset($_POST['update_cart'])) {
        $product_id = intval($_POST['product_id']);
        $qty = max(0, intval($_POST['qty'] ?? 0));
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if ($qty <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id] = $qty;
        }
        header('Location: shop.php?msg=updated');
        exit;
    }

    // Remove item from cart
    if (isset($_POST['remove_from_cart'])) {
        $product_id = intval($_POST['product_id']);
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
        header('Location: shop.php?msg=removed');
        exit;
    }
}

// --- Fetch products for listing (with optional category) ---
$category = $_GET['category'] ?? '';
$where = $category ? "WHERE category = ?" : '';
$stmt = $db->prepare("SELECT * FROM products $where ORDER BY created_at DESC");
$stmt->execute($category ? [$category] : []);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Build cart summary for display (fetch product details for items in cart) ---
$cart_items = [];
$cartCount = 0;
$cartTotal = 0.0;
if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    // Prepare placeholders
    $placeholders = rtrim(str_repeat('?,', count($ids)), ',');
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // map by id for easy lookup
    $byId = [];
    foreach ($rows as $r) $byId[$r['id']] = $r;
    foreach ($_SESSION['cart'] as $pid => $qty) {
        if (isset($byId[$pid])) {
            $item = $byId[$pid];
            $subtotal = floatval($item['price']) * intval($qty);
            $cart_items[] = [
                'id' => $pid,
                'name' => $item['name'],
                'price' => floatval($item['price']),
                'qty' => intval($qty),
                'subtotal' => $subtotal,
                'image' => $item['image'] ?? ''
            ];
            $cartCount += intval($qty);
            $cartTotal += $subtotal;
        } else {
            // product removed from DB — remove from cart
            unset($_SESSION['cart'][$pid]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KSA Shop • Official Scouts Store</title>

    <!-- Tailwind + FontAwesome + Inter -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        /* small enhancement for cart dropdown scroll */
        .cart-dropdown { max-height: 360px; overflow-y: auto; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<!-- Floating AI Assistant -->
<div class="fixed bottom-6 right-6 bg-green-700 hover:bg-green-800 text-white p-4 rounded-full shadow-2xl cursor-pointer z-40 transition" id="open-ai-chat" title="Talk to Kenya Scouts AI Assistant">
    <i class="fas fa-robot text-xl"></i>
</div>

<!-- NAVBAR (same layout + cart link and dropdown) -->
<nav class="bg-white text-gray-800 shadow-lg sticky top-0 z-50 border-b border-gray-100">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-5">
            <a href="index.php"><img src="assets/img/ksa_logo.png" alt="Kenya Scouts Association" class="h-14"></a>
            <div class="hidden md:block">
                <h1 class="text-2xl font-bold text-green-900">Kenya Scouts Association</h1>
                <p class="text-sm text-gray-600">Be Prepared • Kuwa Tayari</p>
            </div>
        </div>

        <ul class="hidden lg:flex space-x-10 font-semibold text-gray-700">
            <li><a href="index.php" class="hover:text-green-700 transition">Home</a></li>
            <li><a href="index.php#about" class="hover:text-green-700 transition">About</a></li>
            <li><a href="index.php#programs" class="hover:text-green-700 transition">Programs</a></li>
            <li><a href="shop.php" class="text-green-700 font-bold border-b-2 border-green-700">Shop</a></li>
            <li><a href="index.php#counties" class="hover:text-green-700 transition">Counties</a></li>
            <li><a href="index.php#news" class="hover:text-green-700 transition">News</a></li>
            <li><a href="index.php#contact" class="hover:text-green-700 transition">Contact</a></li>
        </ul>

        <div class="flex items-center gap-6">
            <!-- Cart button + dropdown -->
            <div class="relative">
                <button id="cart-toggle" class="bg-yellow-400 text-green-900 px-4 py-2 rounded-full font-bold hover:bg-yellow-300 transition flex items-center gap-3">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="hidden sm:inline">Cart</span>
                    <span class="ml-1">(<span id="cart-count"><?php echo $cartCount; ?></span>)</span>
                </button>

                <!-- Dropdown -->
                <div id="cart-dropdown" class="hidden absolute right-0 mt-3 w-96 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50">
                    <div class="p-4 border-b">
                        <div class="flex justify-between items-center">
                            <h3 class="font-bold text-lg">Your Cart</h3>
                            <span class="text-sm text-gray-500"><?php echo $cartCount; ?> item<?php echo $cartCount == 1 ? '' : 's'; ?></span>
                        </div>
                    </div>

                    <div class="p-4 cart-dropdown space-y-4">
                        <?php if (empty($cart_items)): ?>
                            <div class="text-center text-gray-500 py-6">
                                Your cart is empty.
                            </div>
                        <?php else: ?>
                            <?php foreach ($cart_items as $ci): ?>
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo htmlspecialchars($ci['image'] ? 'uploads/'.$ci['image'] : 'assets/img/placeholder.png'); ?>" alt="" class="w-16 h-16 object-cover rounded-lg border">
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($ci['name']); ?></div>
                                                <div class="text-sm text-gray-500">KSh <?php echo number_format($ci['price']); ?> × <?php echo $ci['qty']; ?></div>
                                            </div>
                                            <div class="text-right font-bold text-green-700">KSh <?php echo number_format($ci['subtotal']); ?></div>
                                        </div>

                                        <!-- update qty / remove -->
                                        <form method="POST" class="mt-3 flex items-center gap-2">
                                            <input type="hidden" name="product_id" value="<?php echo $ci['id']; ?>">
                                            <input type="number" name="qty" value="<?php echo $ci['qty']; ?>" min="0" class="w-20 px-2 py-1 border rounded-md text-center" />
                                            <button type="submit" name="update_cart" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm">Update</button>
                                            <button type="submit" name="remove_from_cart" class="px-3 py-1 bg-red-600 text-white rounded-md text-sm">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 border-t">
                        <div class="flex justify-between items-center mb-4">
                            <div class="text-sm text-gray-600">Subtotal</div>
                            <div class="font-bold text-xl text-green-700">KSh <?php echo number_format($cartTotal); ?></div>
                        </div>
                        <div class="flex gap-3">
                            <a href="cart.php" class="flex-1 text-center px-4 py-3 border rounded-lg font-semibold hover:bg-gray-50">View Cart</a>
                            <a href="checkout.php" class="flex-1 text-center px-4 py-3 bg-green-700 text-white rounded-lg font-bold">Checkout →</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <button id="mobile-menu-btn" class="lg:hidden text-3xl text-gray-700">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden bg-white border-t border-gray-100 lg:hidden">
        <ul class="px-6 py-5 space-y-4 text-lg font-medium text-gray-700">
            <li><a href="index.php" class="block hover:text-green-700">Home</a></li>
            <li><a href="index.php#about" class="block hover:text-green-700">About</a></li>
            <li><a href="index.php#programs" class="block hover:text-green-700">Programs</a></li>
            <li><a href="shop.php" class="block text-green-700 font-bold">Shop</a></li>
            <li><a href="index.php#counties" class="block hover:text-green-700">Counties</a></li>
            <li><a href="index.php#news" class="block hover:text-green-700">News</a></li>
            <li><a href="index.php#contact" class="block hover:text-green-700">Contact</a></li>
            <li><a href="checkout.php" class="block hover:text-green-700">Checkout</a></li>
        </ul>
    </div>
</nav>

<!-- SHOP HEADER -->
<section class="bg-gradient-to-r from-green-800 to-green-900 text-white py-20 text-center">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-black mb-2">Official Scouts Shop</h1>
        <p class="text-lg md:text-xl opacity-90">Authentic uniforms, badges, camping gear, books & more.</p>
    </div>
</section>

<!-- MAIN -->
<div class="container mx-auto px-4 py-12">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar -->
        <aside class="lg:w-80 bg-white p-6 rounded-2xl shadow border-l-8 border-green-700">
            <h2 class="text-2xl font-bold text-green-800 mb-4">Shop by Category</h2>
            <ul class="space-y-3">
                <li><a href="shop.php" class="block p-3 rounded <?php echo empty($category) ? 'bg-green-100 font-bold' : 'hover:bg-gray-50'; ?>">All Products</a></li>
                <li><a href="shop.php?category=Uniforms" class="block p-3 rounded <?php echo $category=='Uniforms' ? 'bg-green-100 font-bold' : 'hover:bg-gray-50'; ?>">Uniforms</a></li>
                <li><a href="shop.php?category=Badges" class="block p-3 rounded <?php echo $category=='Badges' ? 'bg-green-100 font-bold' : 'hover:bg-gray-50'; ?>">Badges & Awards</a></li>
                <li><a href="shop.php?category=Essentials" class="block p-3 rounded <?php echo $category=='Essentials' ? 'bg-green-100 font-bold' : 'hover:bg-gray-50'; ?>">Camping & Essentials</a></li>
            </ul>

            <div class="mt-6 p-4 bg-green-50 rounded-lg">
                <h3 class="font-semibold text-green-800">Lipa Mdogo Mdogo</h3>
                <p class="text-sm text-gray-700">Pay any amount today. No interest. No hidden fees.</p>
            </div>

            <div class="mt-4 text-center">
                <a href="assets/resource/K.S.A/SHOP/KSA SCOUTS SHOP PRICE LIST EFFECTIVE 1ST JANUARY 2025.docx" class="inline-block px-4 py-2 bg-white border rounded text-green-800 hover:bg-gray-100">Download Price List</a>
            </div>
        </aside>

        <!-- Product grid -->
        <main class="flex-1">
            <?php if (isset($_GET['msg'])): ?>
                <div class="mb-6 p-4 rounded bg-green-100 text-green-800 font-semibold"><?php
                    if ($_GET['msg']=='added') echo "Item added to cart.";
                    elseif ($_GET['msg']=='updated') echo "Cart updated.";
                    elseif ($_GET['msg']=='removed') echo "Item removed.";
                ?></div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($products as $p): ?>
                <div class="bg-white rounded-2xl shadow overflow-hidden border-t-8 border-green-700">
                    <img src="<?php echo htmlspecialchars($p['image'] ? 'uploads/'.$p['image'] : 'assets/img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="w-full h-56 object-cover">
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($p['name']); ?></h3>
                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($p['category']); ?></p>
                        <div class="mt-3 text-2xl font-bold text-green-700">KSh <?php echo number_format($p['price']); ?></div>

                        <form method="POST" class="mt-4 flex gap-3 items-center">
                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                            <input type="number" name="qty" value="1" min="1" class="w-20 px-2 py-2 border rounded-lg text-center">
                            <button type="submit" name="add_to_cart" class="flex-1 bg-yellow-400 text-green-900 font-bold py-2 rounded-lg hover:bg-yellow-300 transition">Add to Cart</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-green-900 text-white py-12">
    <div class="container mx-auto px-4 text-center">
        <img src="assets/img/ksa_logo.png" alt="KSA" class="h-16 mx-auto mb-4 rounded-full border-4 border-yellow-400">
        <p class="text-gray-200">&copy; <?php echo date('Y'); ?> Kenya Scouts Association</p>
    </div>
</footer>

<!-- AI Modal (simple) -->
<div id="ai-chat-modal" style="display:none;position:fixed;inset:0;background:#fff;z-index:60;">
    <div class="close-chat" id="close-ai-chat" style="position:absolute;right:18px;top:18px;background:#166534;color:#fff;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:24px;">×</div>
    <iframe src="ai-assistant.html" style="width:100%;height:100%;border:0;"></iframe>
</div>

<script>
    // toggle mobile menu
    document.getElementById('mobile-menu-btn').onclick = () => {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    };

    // cart dropdown toggle
    const cartToggle = document.getElementById('cart-toggle');
    const cartDropdown = document.getElementById('cart-dropdown');
    cartToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        cartDropdown.classList.toggle('hidden');
    });

    // close cart when clicking outside
    document.addEventListener('click', (e) => {
        if (!cartDropdown.classList.contains('hidden')) {
            if (!cartDropdown.contains(e.target) && !cartToggle.contains(e.target)) {
                cartDropdown.classList.add('hidden');
            }
        }
    });

    // AI modal open/close
    document.getElementById('open-ai-chat').onclick = () => { document.getElementById('ai-chat-modal').style.display = 'block'; };
    document.getElementById('close-ai-chat').onclick = () => { document.getElementById('ai-chat-modal').style.display = 'none'; };
</script>
</body>
</html>
