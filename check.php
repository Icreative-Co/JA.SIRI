<?php
require_once 'db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - KSA Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 font-inter text-gray-800">

<!-- Floating AI Assistant -->
<div class="fixed bottom-6 right-6 bg-green-700 hover:bg-green-800 text-white p-4 rounded-full shadow-2xl cursor-pointer z-50 transition" id="open-ai-chat" title="Talk to Kenya Scouts AI Assistant">
    <i class="fas fa-robot text-2xl"></i>
</div>

<!-- NAVBAR -->
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
            <li><a href="shop.php" class="hover:text-green-700 transition">Shop</a></li>
            <li><a href="index.php#counties" class="hover:text-green-700 transition">Counties</a></li>
            <li><a href="index.php#news" class="hover:text-green-700 transition">News</a></li>
            <li><a href="check.php" class="text-green-700 font-bold border-b-2 border-green-700">Track Order</a></li>
            <li><a href="index.php#contact" class="hover:text-green-700 transition">Contact</a></li>
        </ul>

        <!-- Mobile menu button -->
        <button id="mobile-menu-btn" class="lg:hidden text-3xl text-gray-700">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden bg-white border-t border-gray-100 lg:hidden">
        <ul class="px-6 py-5 space-y-4 text-lg font-medium text-gray-700">
            <li><a href="index.php" class="block hover:text-green-700">Home</a></li>
            <li><a href="index.php#about" class="block hover:text-green-700">About</a></li>
            <li><a href="index.php#programs" class="block hover:text-green-700">Programs</a></li>
            <li><a href="shop.php" class="block hover:text-green-700">Shop</a></li>
            <li><a href="index.php#counties" class="block hover:text-green-700">Counties</a></li>
            <li><a href="index.php#news" class="block hover:text-green-700">News</a></li>
            <li><a href="check.php" class="block text-green-700 font-bold">Track Order</a></li>
            <li><a href="index.php#contact" class="block hover:text-green-700">Contact</a></li>
        </ul>
    </div>
</nav>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-green-800 to-green-900 text-white py-20 text-center">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-black mb-2">Track Your Order</h1>
        <p class="text-lg md:text-xl opacity-90">See how close you are to getting your gear!</p>
    </div>
</section>

<!-- Track Form -->
<div class="container mx-auto px-4 py-12 max-w-3xl">
    <div class="bg-white rounded-3xl shadow-2xl p-10 border-t-8 border-green-700">
        <h2 class="text-3xl font-bold text-center text-green-800 mb-10">Enter Your Phone Number</h2>
        <form method="GET" class="max-w-md mx-auto">
            <input type="text" name="phone" placeholder="0712345678" value="<?php echo htmlspecialchars($_GET['phone'] ?? ''); ?>" required class="w-full px-6 py-5 text-xl border-2 border-gray-300 rounded-xl focus:border-green-600 focus:outline-none">
            <button type="submit" class="w-full mt-6 bg-green-700 hover:bg-green-800 text-white font-bold text-xl py-5 rounded-xl transition">Check Progress</button>
        </form>

        <?php
        if (!empty($_GET['phone'])) {
            $phone = preg_replace('/\D/', '', $_GET['phone']);
            if (strlen($phone) === 10 && substr($phone,0,1) === '0') $phone = '254'.substr($phone,1);
            elseif (strlen($phone) === 12 && substr($phone,0,3) === '254') $phone = $phone;
            else $phone = null;

            if ($phone) {
                $stmt = $db->prepare("SELECT * FROM customers WHERE phone = ?");
                $stmt->execute([$phone]);
                $customer = $stmt->fetch();

                $paid = $customer['total_paid'] ?? 0;
                $target = $customer['target_amount'] ?? 0;

                if ($customer && $target > 0):
                    $percentage = min(100, ($paid / $target) * 100);
                    $remaining = max(0, $target - $paid);
                    $isComplete = $paid >= $target;
        ?>
        <div class="mt-12 text-center">
            <div class="text-6xl font-bold text-green-700">KES <?php echo number_format($paid); ?></div>
            <p class="text-xl text-gray-600 mt-4">Paid out of <strong>KES <?php echo number_format($target); ?></strong></p>
            <div class="mt-8 bg-gray-200 rounded-full h-12 overflow-hidden">
                <div class="bg-green-600 h-full transition-all duration-1500 ease-out" style="width: <?php echo $percentage; ?>%"></div>
            </div>
            <p class="text-2xl font-bold mt-4"><?php echo round($percentage); ?>% Complete</p>

            <?php if ($isComplete): ?>
            <div class="mt-10 p-8 bg-green-100 rounded-2xl border-4 border-green-600">
                <h3 class="text-4xl font-bold text-green-800">PAID IN FULL!</h3>
                <p class="text-2xl mt-4">Your order is ready for collection!</p>
                <div class="mt-6 p-6 bg-white rounded-xl text-left">
                    <p><strong>Collection Point:</strong><br>Kenya Scouts Association HQ<br>Rowallan Camp, Nairobi</p>
                    <p class="mt-4"><strong>Hours:</strong> Mon–Fri 8:30am–4:30pm | Sat 9am–1pm</p>
                    <p class="mt-4"><strong>Bring:</strong> This phone number + ID</p>
                </div>
                <a href="index.php" class="inline-block mt-8 bg-yellow-400 text-green-900 font-bold px-10 py-5 rounded-full text-xl hover:bg-yellow-300 transition">Shop More</a>
            </div>
            <?php else: ?>
            <div class="mt-10 p-8 bg-yellow-100 rounded-2xl border-4 border-yellow-500">
                <h3 class="text-4xl font-bold text-yellow-800">KES <?php echo number_format($remaining); ?></h3>
                <p class="text-2xl mt-4">Remaining to complete your order</p>
                <a href="index.php" class="inline-block mt-8 bg-green-700 text-white font-bold px-10 py-5 rounded-full text-xl hover:bg-green-800 transition">Continue Paying</a>
            </div>
            <?php endif; ?>
        </div>
        <?php
                else:
                    echo '<div class="mt-12 text-center text-2xl text-gray-600">No order found.<br><br><a href="shop.php" class="text-green-600 underline font-bold">Start shopping now!</a></div>';
                endif;
            } else {
                echo '<div class="mt-12 text-center text-2xl text-red-600">Invalid phone number format.</div>';
            }
        }
        ?>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-green-900 text-white py-12">
    <div class="container mx-auto px-4 text-center">
        <img src="assets/img/ksa_logo.png" alt="KSA" class="h-16 mx-auto mb-4 rounded-full border-4 border-yellow-400">
        <p class="text-gray-200">&copy; <?php echo date('Y'); ?> Kenya Scouts Association</p>
    </div>
</footer>

<!-- AI Modal -->
<div id="ai-chat-modal" style="display:none;position:fixed;inset:0;background:#fff;z-index:60;">
    <div class="close-chat" id="close-ai-chat" style="position:absolute;right:18px;top:18px;background:#166534;color:#fff;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:24px;">×</div>
    <iframe src="ai-assistant.html" style="width:100%;height:100%;border:0;"></iframe>
</div>

<script>
    // toggle mobile menu
    document.getElementById('mobile-menu-btn').onclick = () => {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    };

    // AI modal open/close
    document.getElementById('open-ai-chat').onclick = () => { document.getElementById('ai-chat-modal').style.display = 'block'; };
    document.getElementById('close-ai-chat').onclick = () => { document.getElementById('ai-chat-modal').style.display = 'none'; };
</script>
</body>
</html>
