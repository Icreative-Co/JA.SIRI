<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenya Scouts Association</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
    <style>
        /* Minimal modal styles to ensure AI assistant overlay works */
        #ai-chat-modal { display: none; position: fixed; inset: 0; background: #fff; z-index: 9999; overflow: hidden; }
        #ai-chat-modal iframe { width: 100%; height: 100%; border: none; }
        .close-chat { position: absolute; top: 20px; right: 25px; background: #006400; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; cursor: pointer; z-index: 10000; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <!-- Floating AI Button -->
    <div class="float-btn" id="open-ai-chat" title="Talk to Kenya Scouts AI Assistant">
        <i class="fas fa-robot"></i>
    </div>

    <!-- Navbar -->
    <nav class="navbar text-white py-5 shadow-2xl sticky top-0 z-50">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="assets/img/ksa_logo.png" alt="KSA Logo" >
                <div>
                    <h1 class="text-2xl font-bold">Kenya Scouts Association</h1>
                    <p class="text-sm opacity-90">Be Prepared • Kuwa Tayari</p>
                </div>
            </div>
            <ul class="hidden md:flex space-x-10 text-lg font-medium">
                <li><a href="#" class="hover:text-yellow-300 transition">Home</a></li>
                <li><a href="#" class="hover:text-yellow-300 transition">About</a></li>
                <li><a href="#" class="hover:text-yellow-300 transition">Programs</a></li>
                <li><a href="shop.php" class="hover:text-yellow-300 transition font-bold">Shop</a></li>
                <li><a href="#" class="hover:text-yellow-300 transition">News</a></li>
                <li><a href="#" class="hover:text-yellow-300 transition">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero flex items-center justify-center text-center text-white">
        <div class="max-w-5xl px-8">
            <h1 class="text-5xl md:text-8xl font-bold mb-8 leading-tight">
                Creating a Better World<br>
                <span class="text-yellow-400">Through Scouting</span>
            </h1>
            <p class="text-2xl md:text-3xl mb-12 opacity-95">
                Developing responsible citizens since 1910
            </p>
            <div class="space-x-6">
                <a href="shop.php" class="bg-yellow-400 text-green-900 px-10 py-5 rounded-full text-xl font-bold hover:bg-yellow-300 transition shadow-2xl">
                    Visit Shop → Lipa Mdogo Mdogo
                </a>
                <a href="#" class="border-4 border-white px-10 py-5 rounded-full text-xl font-bold hover:bg-white hover:text-green-900 transition">
                    Learn More
                </a>
                <a href="assets/resource/K.S.A/CP_k_scout_leaders_handbook_1.pdf" download class="bg-white text-green-900 px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-100 transition shadow-md" title="Download Scout Leaders Handbook">
                    Download Handbook
                </a>
            </div>
        </div>
    </section>

    <!-- Quick Stats -->
    <section class="bg-green-800 text-white py-20">
        <div class="container mx-auto px-6 grid grid-cols-2 md:grid-cols-4 gap-10 text-center">
            <div>
                <h3 class="text-5xl font-bold text-yellow-400">110+</h3>
                <p class="mt-3 text-lg">Years Strong</p>
            </div>
            <div>
                <h3 class="text-5xl font-bold text-yellow-400">47</h3>
                <p class="mt-3 text-lg">Counties</p>
            </div>
            <div>
                <h3 class="text-5xl font-bold text-yellow-400">500K+</h3>
                <p class="mt-3 text-lg">Youth Served</p>
            </div>
            <div>
                <h3 class="text-5xl font-bold text-yellow-400">100%</h3>
                <p class="mt-3 text-lg">Commitment</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-16 text-center">
        <img src="assets/img/ksa_logo.png" alt="KSA" class="h-20 mx-auto mb-6 rounded-full border-4 border-yellow-400">
        <p class="mb-4">
            <a href="assets/resource/K.S.A/CP_k_scout_leaders_handbook_1.pdf" download class="inline-block bg-yellow-400 text-green-900 px-4 py-2 rounded-md font-semibold hover:bg-yellow-300 transition" title="Download Scout Leaders Handbook">Download Scout Leaders Handbook (PDF)</a>
        </p>
        <p class="text-xl mb-4">&copy; 2025 Kenya Scouts Association</p>
        <p>Rowallan Camp, Nairobi | info@kenyascouts.org | 020 2020819</p>
    </footer>

    <!-- FULL SCREEN AI MODAL -->
    <div id="ai-chat-modal">
        <div class="close-chat" id="close-ai-chat">&times;</div>
        <iframe src="ai-assistant.html" allowfullscreen></iframe>
    </div>

    <script>
        const modal = document.getElementById('ai-chat-modal');
        const openBtn = document.getElementById('open-ai-chat');
        const closeBtn = document.getElementById('close-ai-chat');

        openBtn.onclick = () => { modal.style.display = 'block'; };
        closeBtn.onclick = () => { modal.style.display = 'none'; };
        window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
    </script>
</body>
</html>