<?php
require_once 'db.php';
session_start();

// Fetch products
$category = $_GET['category'] ?? '';
$where = $category ? "WHERE category = ?" : '';
$stmt = $db->prepare("SELECT * FROM products $where ORDER BY created_at DESC");
$stmt->execute($category ? [$category] : []);
$products = $stmt->fetchAll();

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $qty = max(1, intval($_POST['qty'] ?? 1));
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if ($product) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $product_id) {
                $item['qty'] += $qty;
                $item['total'] = $item['price'] * $item['qty'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'qty' => $qty,
                'total' => $product['price'] * $qty
            ];
        }
        header('Location: shop.php?msg=added' . ($category ? "&category=$category" : ''));
        exit;
    }
}

$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$cartTotal = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'total')) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSA Shop - Lipa Mdogo Mdogo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="bg-gray-50">

    <!-- Floating AI Button -->
    <div class="float-btn" id="open-ai-chat" title="Talk to Kenya Scouts AI Assistant">
        <i class="fas fa-robot"></i>
    </div>

    <!-- Navbar (Same as index.php) -->
    <nav class="navbar text-white py-5 shadow-2xl sticky top-0 z-50">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="assets/img/ksa_logo.png" alt="KSA Logo" class="h-14 rounded-full border-4 border-yellow-400">
                <div>
                    <h1 class="text-2xl font-bold">Kenya Scouts Association</h1>
                    <p class="text-sm opacity-90">Be Prepared • Kuwa Tayari</p>
                </div>
            </div>
            <div class="flex items-center gap-8">
                <ul class="hidden md:flex space-x-10 text-lg font-medium">
                    <li><a href="index.php" class="hover:text-yellow-300 transition">Home</a></li>
                    <li><a href="shop.php" class="text-yellow-300 font-bold">Shop</a></li>
                    <li><a href="check.php" class="hover:text-yellow-300 transition">Track Order</a></li>
                </ul>
                <!-- Cart -->
                <a href="checkout.php" class="relative bg-yellow-400 text-green-900 px-6 py-3 rounded-full font-bold hover:bg-yellow-300 transition">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    Cart (<?php echo $cartCount; ?>)
                    <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <section class="hero flex items-center justify-center text-center text-white">
        <div class="max-w-4xl px-8">
            <h1 class="text-5xl md:text-7xl font-bold mb-6">KSA Official Shop</h1>
            <p class="text-2xl mb-8">Uniforms • Badges • Essentials</p>
            <div class="bg-yellow-400 text-green-900 inline-block px-10 py-5 rounded-full text-2xl font-bold">
                Lipa Mdogo Mdogo Available • Pay in Installments!
            </div>
        </div>
    </section>

    <!-- Main Shop -->
    <div class="container mx-auto px-6 py-16">
        <div class="flex flex-col lg:flex-row gap-10">
            <!-- Categories Sidebar -->
            <aside class="lg:w-80 bg-white p-8 rounded-2xl shadow-xl border-l-8 border-green-700">
                <h2 class="text-2xl font-bold text-green-800 mb-6">Shop by Category</h2>
                <ul class="space-y-4 text-lg">
                    <li><a href="shop.php" class="block py-3 px-4 rounded-lg hover:bg-green-50 <?php echo empty($category) ? 'bg-green-100 font-bold' : ''; ?>">All Products</a></li>
                    <li><a href="shop.php?category=Uniforms" class="block py-3 px-4 rounded-lg hover:bg-green-50 <?php echo $category=='Uniforms' ? 'bg-green-100 font-bold' : ''; ?>">Uniforms</a></li>
                    <li><a href="shop.php?category=Badges" class="block py-3 px-4 rounded-lg hover:bg-green-50 <?php echo $category=='Badges' ? 'bg-green-100 font-bold' : ''; ?>">Badges & Awards</a></li>
                    <li><a href="shop.php?category=Essentials" class="block py-3 px-4 rounded-lg hover:bg-green-50 <?php echo $category=='Essentials' ? 'bg-green-100 font-bold' : ''; ?>">Camping & Essentials</a></li>
                </ul>
                <div class="mt-8 p-6 bg-green-50 rounded-xl border-2 border-green-300">
                    <h3 class="font-bold text-green-800 mb-2">Lipa Mdogo Mdogo</h3>
                    <p class="text-sm">Pay any amount today.<br>No interest. No hidden fees.</p>
                </div>
                <div class="mt-6 text-center">
                    <a href="assets/resource/K.S.A/SHOP/KSA SCOUTS SHOP PRICE LIST EFFECTIVE 1ST JANUARY 2025.docx" class="inline-block bg-white text-green-800 px-4 py-2 rounded-md font-semibold hover:bg-gray-100 transition" title="Download shop price list">Download Shop Price List (DOCX)</a>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="flex-1">
                <div class="mb-8 text-center">
                    <h2 class="text-4xl font-bold text-green-800"><?php echo $category ?: 'All Products'; ?></h2>
                    <p class="text-xl text-gray-600 mt-2"><?php echo count($products); ?> items available</p>
                    <?php if (isset($_GET['msg']) && $_GET['msg']=='added'): ?>
                        <p class="text-green-600 font-bold text-xl mt-4">Item added to cart!</p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($products as $p): ?>
                    <div class="product-card bg-white rounded-2xl shadow-xl overflow-hidden border-t-8 border-green-700">
                        <img src="https://images.unsplash.com/photo-1588072432836-e10032774350?w=600&h=600&fit=crop" alt="<?php echo htmlspecialchars($p['name']); ?>" class="w-full h-64 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-green-800 mb-2"><?php echo htmlspecialchars($p['name']); ?></h3>
                            <p class="text-gray-600 mb-4"><?php echo substr($p['description'], 0, 100); ?>...</p>
                            <div class="text-3xl font-bold text-green-600 mb-4">KES <?php echo number_format($p['price']); ?></div>
                            <form method="POST" class="space-y-3">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <div class="flex gap-3">
                                    <input type="number" name="qty" value="1" min="1" class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center">
                                    <button type="submit" name="add_to_cart" class="flex-1 bg-yellow-400 text-green-900 font-bold py-3 rounded-lg hover:bg-yellow-300 transition">
                                        Add to Cart
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-16 text-center">
        <img src="assets/img/ksa_logo.png" alt="KSA" class="h-20 mx-auto mb-6 rounded-full border-4 border-yellow-400">
        <p class="text-xl mb-4">© 2025 Kenya Scouts Association</p>
        <p>All profits support scouting programs across Kenya</p>
    </footer>

    <!-- AI MODAL -->
    <div id="ai-chat-modal">
        <div class="close-chat" id="close-ai-chat">&times;</div>
        <iframe src="ai-assistant.html" class="w-full h-full border-0"></iframe>
    </div>

    <script>
        const modal = document.getElementById('ai-chat-modal');
        const openBtn = document.getElementById('open-ai-chat');
        const closeBtn = document.getElementById('close-ai-chat');
        openBtn.onclick = () => modal.style.display = 'block';
        closeBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
    </script>
</body>
</html>