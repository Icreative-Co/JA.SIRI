<?php
require_once 'db.php';
require_once 'mpesa.php';
session_start();

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit;
}

$cart = $_SESSION['cart'];
$total = array_sum(array_column($cart, 'total'));
$cartCount = count($cart);

$error = '';
if (isset($_POST['pay_installment'])) {
    $phone = preg_replace('/\D/', '', $_POST['phone']);
    if (substr($phone, 0, 1) === '0') $phone = '254' . substr($phone, 1);
    $amount = intval($_POST['amount']);

    if ($amount < 50) {
        $error = "Minimum payment is KES 50";
    } elseif ($amount > $total) {
        $error = "Amount cannot exceed total KES " . number_format($total);
    } else {
        $result = stkPush($phone, $amount, "KSA-ORDER-" . time());
        $responseCode = $result['ResponseCode'] ?? $result['errorCode'] ?? null;
        
        if ($responseCode === '0' || $responseCode === 0) {
            // Success: payment initiated
            $db->prepare("INSERT INTO customers (phone, target_amount) VALUES (?, ?) ON DUPLICATE KEY UPDATE target_amount = ?")
                ->execute([$phone, $total, $total]);
            unset($_SESSION['cart']);
            echo "<script>alert('Payment initiated! Track at check.php'); window.location='check.php';</script>";
        } else {
            // Error: provide detailed feedback
            $errorMsg = $result['CustomerMessage'] ?? $result['errorMessage'] ?? 'Payment initiation failed';
            $error = "M-Pesa Error: " . htmlspecialchars($errorMsg);
            
            // Log the full error for debugging
            @error_log("[CHECKOUT_ERROR] Phone: $phone, Amount: $amount, Error: " . json_encode($result));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - KSA Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>

    <!-- Floating AI Button -->
    <div class="float-btn" id="open-ai-chat" title="Talk to Kenya Scouts AI Assistant">
        <i class="fas fa-robot"></i>
    </div>

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
            <div class="flex items-center gap-8">
                <ul class="hidden md:flex space-x-10 text-lg font-medium">
                    <li><a href="index.php" class="hover:text-yellow-300 transition">Home</a></li>
                    <li><a href="shop.php" class="hover:text-yellow-300 transition">Shop</a></li>
                    <li><a href="check.php" class="hover:text-yellow-300 transition">Track Order</a></li>
                </ul>
                <a href="checkout.php" class="bg-yellow-400 text-green-900 px-6 py-3 rounded-full font-bold relative">
                    <i class="fas fa-shopping-cart mr-2"></i> Checkout (<?php echo $cartCount; ?>)
                    <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero flex items-center justify-center text-center text-white">
        <div class="max-w-4xl px-8">
            <h1 class="text-5xl md:text-7xl font-bold mb-6">Checkout</h1>
            <p class="text-2xl">Complete your order with Lipa Mdogo Mdogo</p>
        </div>
    </section>

    <!-- Main Checkout -->
    <div class="container mx-auto px-6 py-16 max-w-7xl">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Order Items & Details -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Cart Items -->
                <div class="bg-white rounded-3xl shadow-2xl p-8 border-t-8 border-green-700">
                    <h2 class="text-3xl font-bold text-green-800 mb-8">Your Order</h2>
                    <?php foreach ($cart as $item): ?>
                    <div class="flex gap-6 py-8 border-b border-gray-200 last:border-0 product-card">
                        <img src="https://images.unsplash.com/photo-1588072432836-e10032774350?w=600" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-32 h-32 object-cover rounded-2xl shadow-lg">
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-green-800"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <?php if (!empty($item['size'])): ?>
                                <p class="text-lg text-gray-600 mt-2"><strong>Size:</strong> <?php echo htmlspecialchars($item['size']); ?></p>
                            <?php endif; ?>
                            <p class="text-lg text-gray-600 mt-1"><strong>Quantity:</strong> <?php echo $item['qty']; ?></p>
                            <p class="text-3xl font-bold text-green-600 mt-4">KES <?php echo number_format($item['total']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="mt-10 pt-8 border-t-4 border-yellow-400 bg-green-50 rounded-2xl p-6">
                        <div class="flex justify-between text-4xl font-bold">
                            <span class="text-green-800">Total Amount</span>
                            <span class="text-green-600">KES <?php echo number_format($total); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Product Specifications -->
                <div class="bg-white rounded-3xl shadow-2xl p-8 border-t-8 border-green-700">
                    <h3 class="text-2xl font-bold text-green-800 mb-6">Product Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-lg">
                        <div>
                            <p><strong>Brand:</strong> Kenya Scouts Association</p>
                            <p><strong>Material:</strong> Premium cotton/poly blend</p>
                            <p><strong>Warranty:</strong> 30-day quality guarantee</p>
                        </div>
                        <div>
                            <p><strong>Made for:</strong> Scouts & Leaders</p>
                            <p><strong>Supports:</strong> Youth development programs</p>
                            <p><strong>Origin:</strong> Proudly Kenyan</p>
                        </div>
                    </div>
                </div>

                <!-- Delivery Info -->
                <div class="bg-green-50 rounded-3xl p-8 border-4 border-green-300">
                    <h3 class="text-2xl font-bold text-green-800 mb-6">Delivery & Collection</h3>
                    <div class="space-y-4 text-lg">
                        <p><strong>Free Pickup:</strong> KSA Headquarters, Rowallan Camp, Nairobi</p>
                        <p><strong>Delivery:</strong> Nationwide via G4S • KES 300–600</p>
                        <p><strong>Processing:</strong> Within 24 hours after full payment</p>
                        <p><strong>Track Order:</strong> <a href="check.php" class="underline font-bold">check.php</a></p>
                    </div>
                </div>
            </div>

            <!-- Payment Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-2xl p-8 border-t-8 border-yellow-400 sticky top-24">
                    <h2 class="text-4xl font-bold text-center text-green-800 mb-8">Lipa Mdogo Mdogo</h2>
                    <div class="bg-gradient-to-br from-green-100 to-green-50 p-8 rounded-2xl text-center mb-8 border-4 border-green-300">
                        <p class="text-5xl font-bold text-green-700">KES <?php echo number_format($total); ?></p>
                        <p class="text-2xl mt-3">Total Amount</p>
                        <p class="text-lg mt-4 font-semibold">Pay any amount today<br><span class="text-green-600">No interest • No fees</span></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="bg-red-100 text-red-700 p-5 rounded-xl mb-6 text-center font-bold"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-xl font-bold mb-3">M-Pesa Phone Number</label>
                            <input type="text" name="phone" placeholder="0712345678" required class="w-full px-6 py-5 text-xl border-2 border-gray-300 rounded-xl focus:border-green-600 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xl font-bold mb-3">Amount to Pay Now</label>
                            <input type="number" name="amount" min="50" max="<?php echo $total; ?>" placeholder="e.g. 2000" required class="w-full px-6 py-5 text-xl border-2 border-gray-300 rounded-xl focus:border-green-600 focus:outline-none">
                        </div>
                        <button type="submit" name="pay_installment" class="w-full bg-green-700 hover:bg-green-800 text-white font-bold text-2xl py-6 rounded-xl transition transform hover:scale-105 shadow-xl">
                            Pay with M-Pesa → STK Push
                        </button>
                    </form>

                    <div class="mt-8 text-center">
                        <p class="text-sm text-gray-600">After payment, track your progress at</p>
                        <a href="check.php" class="text-xl font-bold text-green-600 underline">check.php →</a>
                    </div>
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
        <div class="close-chat" id="close-ai-chat">×</div>
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