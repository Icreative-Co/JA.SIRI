<?php
require_once 'db.php';
session_start();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) header('Location: index.php');
if (!$product) { header('Location: index.php'); exit; }

// Mock sizes (you can customize per product later)
$sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

// Add to cart
if (isset($_POST['add_to_cart'])) {
    $qty = max(1, intval($_POST['qty'] ?? 1));
    $size = $_POST['size'] ?? 'M';

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $id && $item['size'] == $size) {
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
            'total' => $product['price'] * $qty,
            'size' => $size,
            'image' => $product['image'] ?? ''
        ];
    }
    echo "<script>alert('Added to cart!'); window.location='product.php?id=$id';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - KSA Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar text-white py-5 shadow-2xl sticky top-0 z-50">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="assets/img/ksa_logo.png" alt="KSA Logo" class="h-14 rounded-full border-4 border-yellow-400">
                <div>
                    <h1 class="text-2xl font-bold">Kenya Scouts Association</h1>
                    <p class="text-sm opacity-90">Be Prepared • Kuwa Tayari</p>
                </div>
            </div>
            <ul class="hidden md:flex space-x-10 text-lg font-medium">
                <li><a href="index.php" class="hover:text-yellow-300 transition">Home</a></li>
                <li><a href="about.php" class="hover:text-yellow-300 transition">About</a></li>
                <li><a href="programs.php" class="hover:text-yellow-300 transition">Programs</a></li>
                <li><a href="shop.php" class="hover:text-yellow-300 transition font-bold">Shop</a></li>
                <li><a href="news.php" class="hover:text-yellow-300 transition">News</a></li>
                <li><a href="contact.php" class="hover:text-yellow-300 transition">Contact</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="gallery">
            <img src="https://images.unsplash.com/photo-1588072432836-e10032774350?w=800&h=800&fit=crop&crop=center" alt="<?php echo $product['name']; ?>">
        </div>

        <div class="details">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="price">KES <?php echo number_format($product['price']); ?></div>

            <form method="POST">
                <div class="sizes">
                    <strong>Select Size:</strong><br>
                    <?php foreach ($sizes as $s): ?>
                        <button type="button" onclick="this.parentNode.querySelector('input[value=<?php echo $s; ?>]').checked=true; this.parentNode.querySelectorAll('button').forEach(b=>b.classList.remove('active')); this.classList.add('active')">
                            <?php echo $s; ?>
                        </button>
                        <input type="radio" name="size" value="<?php echo $s; ?>" style="display:none;" <?php echo $s==='M'?'checked':''; ?>>
                    <?php endforeach; ?>
                </div>

                <div style="margin:20px 0;">
                    <label>Quantity: <input type="number" name="qty" value="1" min="1" style="width:80px;padding:10px;"></label>
                </div>

                <button type="submit" name="add_to_cart" class="btn-add">
                    Add to Cart
                </button>
            </form>

            <div class="desc">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Official KSA merchandise. Made with pride for Kenyan scouts. Durable, comfortable, and supports youth programs nationwide.')); ?></p>
            </div>

            <div class="mt-4 mb-6">
                <a href="assets/resource/K.S.A/SHOP/KSA SCOUTS SHOP PRICE LIST EFFECTIVE 1ST JANUARY 2025.docx" class="inline-block bg-yellow-400 text-green-900 px-4 py-2 rounded-md font-semibold hover:bg-yellow-300 transition" title="Download shop price list">Download Shop Price List</a>
            </div>

            <div class="reviews">
                <h3>Customer Reviews</h3>
                <p>No reviews yet. Be the first!</p>
            </div>
        </div>
    </div>

    <div class="related">
        <div style="max-width:1200px;margin:0 auto;padding:20px;">
            <h3>You May Also Like</h3>
            <div class="related-grid">
                <div class="related-item">
                    <img src="https://images.unsplash.com/photo-1622519402722-8bdfc1a11b7f?w=400" alt="Neckerchief">
                    <strong>Scout Neckerchief</strong><br>KES 300
                </div>
                <div class="related-item">
                    <img src="https://images.unsplash.com/photo-1588075592446-4f0e7a7e8e3e?w=400" alt="Badge">
                    <strong>110th Anniversary Badge</strong><br>KES 150
                </div>
                <div class="related-item">
                    <img src="https://images.unsplash.com/photo-1594736797933-d0501ba2fe65?w=400" alt="Hat">
                    <strong>Scout Hat</strong><br>KES 800
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>Kenya Scouts Association © 2025 | All profits support scouting in Kenya</p>
    </footer>
</body>
</html>