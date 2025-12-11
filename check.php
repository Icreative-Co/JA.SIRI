<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - KSA Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>

    <!-- Floating AI Button -->
    <div class="float-btn" id="open-ai-chat">
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
            <ul class="hidden md:flex space-x-10 text-lg font-medium">
                <li><a href="index.php" class="hover:text-yellow-300 transition">Home</a></li>
                <li><a href="about.php" class="hover:text-yellow-300 transition">About</a></li>
                <li><a href="shop.php" class="hover:text-yellow-300 transition">Shop</a></li>
                <li><a href="news.php" class="hover:text-yellow-300 transition">News</a></li>
                <li><a href="check.php" class="text-yellow-300 font-bold">Track Order</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero flex items-center justify-center text-center text-white">
        <div class="max-w-4xl px-8">
            <h1 class="text-5xl md:text-7xl font-bold mb-6">Track Your Order</h1>
            <p class="text-2xl">See how close you are to getting your gear!</p>
        </div>
    </section>

    <!-- Track Form -->
    <div class="container mx-auto px-6 py-16 max-w-3xl">
        <div class="bg-white rounded-3xl shadow-2xl p-10 border-t-8 border-green-700">
            <h2 class="text-3xl font-bold text-center text-green-800 mb-10">Enter Your Phone Number</h2>
            <form method="GET" class="max-w-md mx-auto">
                <input type="text" name="phone" placeholder="0712345678" value="<?php echo htmlspecialchars($_GET['phone'] ?? ''); ?>" required class="w-full px-6 py-5 text-xl border-2 border-gray-300 rounded-xl focus:border-green-600 focus:outline-none">
                <button type="submit" class="w-full mt-6 bg-green-700 hover:bg-green-800 text-white font-bold text-xl py-5 rounded-xl transition">Check Progress</button>
            </form>

            <?php
            if (isset($_GET['phone'])) {
                $phone = preg_replace('/\D/', '', $_GET['phone']);
                if (substr($phone,0,1) === '0') $phone = '254' . substr($phone,1);

                $stmt = $db->prepare("SELECT * FROM customers WHERE phone = ?");
                $stmt->execute([$phone]);
                $c = $stmt->fetch();

                $paid = $c['total_paid'] ?? 0;
                $target = $c['target_amount'] ?? 0;

                if ($target > 0) {
                    $percentage = min(100, ($paid / $target) * 100);
                    $remaining = $target - $paid;
                    $isComplete = $paid >= $target;
                    ?>
                    <div class="mt-12 text-center">
                        <div class="text-6xl font-bold text-green-700">KES <?php echo number_format($paid); ?></div>
                        <p class="text-xl text-gray-600 mt-4">Paid out of <strong>KES <?php echo number_format($target); ?></strong></p>

                        <div class="mt-8 bg-gray-200 rounded-full h-12 overflow-hidden">
                            <div class="progress-bar bg-green-600 h-full transition-all duration-1500 ease-out" style="width: <?php echo $percentage; ?>%"></div>
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
                } else {
                    echo '<div class="mt-12 text-center text-2xl text-gray-600">No order found.<br><br><a href="shop.php" class="text-green-600 underline font-bold">Start shopping now!</a></div>';
                }
            }
            ?>
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