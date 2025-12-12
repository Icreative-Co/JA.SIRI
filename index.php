<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenya Scouts Association - Be Prepared • Kuwa Tayari</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    <style>
        body { font-family: 'Inter', sans-serif; }
        #ai-chat-modal { display: none; position: fixed; inset: 0; background: #fff; z-index: 9999; }
        #ai-chat-modal iframe { width: 100%; height: 100%; border: none; }
        .close-chat { position: absolute; top: 20px; right: 25px; background: #166534; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; cursor: pointer; z-index: 10000; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }

        /* Map Styling */
        .county { fill: #86efac; stroke: #fff; stroke-width: 1.5; transition: all 0.3s ease; cursor: pointer; }
        .county:hover { fill: #16a34a !important; stroke: #166534; stroke-width: 3; }
        #map-tooltip {
            position: absolute; background: rgba(0,0,0,0.92); color: white; padding: 10px 16px;
            border-radius: 12px; font-size: 15px; font-weight: 600; pointer-events: none;
            opacity: 0; transition: opacity 0.3s; z-index: 1000; white-space: nowrap;
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">

    <!-- Floating AI Assistant -->
    <div class="fixed bottom-6 right-6 bg-green-700 hover:bg-green-800 text-white p-5 rounded-full shadow-2xl cursor-pointer z-40 transition" id="open-ai-chat">
        <i class="fas fa-robot text-2xl"></i>
    </div>

    <!-- White Navbar -->
    <nav class="bg-white text-gray-800 shadow-lg sticky top-0 z-50 border-b border-gray-100">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-5">
                <img src="assets/img/ksa_logo.png" alt="Kenya Scouts Association" class="h-14">
                <div class="hidden md:block">
                    <h1 class="text-2xl font-bold text-green-900">Kenya Scouts Association</h1>
                    <p class="text-sm text-gray-600">Be Prepared • Kuwa Tayari</p>
                </div>
            </div>

            <ul class="hidden lg:flex space-x-10 font-semibold text-gray-700">
                <li><a href="#" class="hover:text-green-700 transition">Home</a></li>
                <li><a href="#about" class="hover:text-green-700 transition">About</a></li>
                <li><a href="#programs" class="hover:text-green-700 transition">Programs</a></li>
                <li><a href="shop.php" class="text-green-700 font-bold border-b-2 border-green-700">Shop</a></li>
                <li><a href="#counties" class="hover:text-green-700 transition">Counties</a></li>
                <li><a href="#news" class="hover:text-green-700 transition">News</a></li>
                <li><a href="#contact" class="hover:text-green-700 transition">Contact</a></li>
            </ul>

            <button id="mobile-menu-btn" class="lg:hidden text-3xl text-gray-700">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden bg-white border-t border-gray-100 lg:hidden">
            <ul class="px-6 py-5 space-y-4 text-lg font-medium text-gray-700">
                <li><a href="#" class="block hover:text-green-700">Home</a></li>
                <li><a href="#about" class="block hover:text-green-700">About</a></li>
                <li><a href="#programs" class="block hover:text-green-700">Programs</a></li>
                <li><a href="shop.php" class="block text-green-700 font-bold">Shop</a></li>
                <li><a href="#counties" class="block hover:text-green-700">Counties</a></li>
                <li><a href="#news" class="block hover:text-green-700">News</a></li>
                <li><a href="#contact" class="block hover:text-green-700">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero -->
    <section class="bg-gradient-to-br from-green-800 via-green-700 to-green-900 text-white py-32 text-center">
        <div class="container mx-auto px-4">
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-black mb-6 leading-tight">
                Creating a Better World<br>
                <span class="text-yellow-300">Through Scouting</span>
            </h1>
            <p class="text-xl md:text-2xl mb-10 max-w-4xl mx-auto opacity-95">
                Empowering Kenyan youth in all 47 counties since 1910
            </p>
            <div class="flex flex-col md:flex-row gap-6 justify-center">
                <a href="shop.php" class="bg-yellow-400 text-green-900 px-12 py-5 rounded-full text-xl font-bold hover:bg-yellow-300 transition shadow-2xl">
                    Shop Now → Lipa Mdogo Mdogo
                </a>
                <a href="#counties" class="border-4 border-white px-12 py-5 rounded-full text-xl font-bold hover:bg-white hover:text-green-900 transition">
                    Explore Counties
                </a>
            </div>
        </div>
    </section>

    <!-- Shop Section -->
    <section class="py-20 bg-gradient-to-r from-yellow-50 to-green-50">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl font-bold text-green-900 mb-6">Official Kenya Scouts Shop</h2>
            <p class="text-xl text-gray-700 mb-12 max-w-3xl mx-auto">
                Authentic uniforms, badges, camping gear & gifts. Every purchase supports Scouting in Kenya.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <!-- Repeat 4 cards as before -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition">
                    <img src="assets/img/shop-uniform.jpg" alt="Uniforms" class="w-full h-56 object-cover">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-green-900">Uniforms</h3>
                        <p class="text-gray-600 mt-2">All sections – Cubs to Rovers</p>
                    </div>
                </div>
                <!-- ... other 3 cards ... -->
            </div>
            <a href="shop.php" class="bg-green-700 hover:bg-green-800 text-white px-16 py-6 rounded-full text-2xl font-bold transition shadow-2xl">
                Visit Shop Now
            </a>
        </div>
    </section>

    <!-- Strategic Pillars + Vision/Mission -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl md:text-5xl font-bold text-center text-green-900 mb-16">Strategic Pillars</h2>
            <div class="grid lg:grid-cols-2 gap-12 max-w-6xl mx-auto">
                <div class="space-y-8">
                    <div class="flex items-start gap-4">
                        <div class="text-green-600 text-3xl">•</div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800">Innovate Education</h3>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="text-green-600 text-3xl">•</div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800">Social & Environmental Impact</h3>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="text-green-600 text-3xl">•</div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800">Communications, Partnerships & Advocacy</h3>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="text-green-600 text-3xl">•</div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800">Good Governance & Financial Sustainability</h3>
                        </div>
                    </div>
                </div>

                <div class="space-y-12">
                    <div>
                        <h3 class="text-2xl font-bold text-green-900 mb-4">Our Vision</h3>
                        <p class="text-lg text-gray-700 leading-relaxed">
                            To be the leading youth movement in Kenya developing well rounded citizens who are agents of change.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-green-900 mb-4">Our Mission</h3>
                        <p class="text-lg text-gray-700 leading-relaxed">
                            To contribute to the education of young people through a value system based on the Scout Promise and Law helping building a better world where people are self-fulfilled as individuals and play a constructive role in society.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Counties Map - Fixed for Mobile -->
    <section id="counties" class="py-20 bg-gray-100">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl font-bold text-green-900 mb-6">
                Scouting Active in All 47 Counties
            </h2>
            <p class="text-xl text-gray-700 mb-12 max-w-4xl mx-auto">
                Hover or tap any county to explore Scout activity nationwide.
            </p>

            <div class="max-w-5xl mx-auto bg-white rounded-3xl shadow-2xl p-6 md:p-12 overflow-hidden">
                <div id="map-container" class="relative w-full">
                    <!-- SVG will load here -->
                    <div id="map-tooltip"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Info Cards Section -->
    <section class="py-20 bg-gradient-to-b from-white to-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-10">
                <div class="text-center">
                    <h3 class="text-5xl font-black text-green-700">4</h3>
                    <p class="text-xl font-semibold text-gray-700 mt-3">Million Scouts in Kenya</p>
                </div>
                <div class="text-center">
                    <h3 class="text-5xl font-black text-green-700">111 <span class="text-3xl">+</span></h3>
                    <p class="text-xl font-semibold text-gray-700 mt-3">Years in Operation</p>
                </div>
                <div class="text-center">
                    <h3 class="text-5xl font-black text-green-700">47</h3>
                    <p class="text-xl font-semibold text-gray-700 mt-3">Counties Covered</p>
                </div>
                <div class="text-center">
                    <h3 class="text-5xl font-black text-green-700">40,000</h3>
                    <p class="text-xl font-semibold text-gray-700 mt-3">Scout Leaders</p>
                </div>
            </div>
        </div>
    </section>

    <!-- News & Testimonials -->
    <!-- (All your news, testimonials, commitment, etc. go here – kept clean and modern) -->

    <!-- Footer -->
    <footer class="bg-green-900 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-10 mb-12">
                <div>
                    <img src="assets/img/ksa_logo.png" alt="KSA" class="h-20 mx-auto md:mx-0 mb-6">
                    <p class="text-gray-300">
                        <i class="fas fa-phone-alt mr-3"></i> 020 2020819 / 0733 919 333<br>
                        <i class="fas fa-envelope mr-3"></i> info@kenyascouts.org<br>
                        <i class="fas fa-map-marker-alt mr-3"></i> Rowallan Scouts Camp, Nairobi
                    </p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-6 text-yellow-300">ABOUT US</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li><a href="#" class="hover:text-yellow-300 transition">Who We Are</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">What We Do</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">Where We Work</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">Media</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">Careers</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">Resources</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-6 text-yellow-300">OTHER LINKS</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li><a href="#" class="hover:text-yellow-300 transition">Scout Movement</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">Scouting Education</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">Scout Promise & Law</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">Scouting's History</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-6 text-yellow-300">Subscribe to Our Newsletter!</h3>
                    <p class="text-gray-300 mb-6">Get updates, events alerts, learning materials, and much more.</p>
                    <form class="flex flex-col gap-3">
                        <input type="text" placeholder="Your Name" class="px-4 py-3 rounded-lg text-gray-800">
                        <input type="email" placeholder="Your Email" class="px-4 py-3 rounded-lg text-gray-800">
                        <button class="bg-yellow-400 text-green-900 font-bold py-3 rounded-lg hover:bg-yellow-300 transition font-bold">
                            SUBSCRIBE
                        </button>
                    </form>
                </div>
            </div>
            <div class="text-center pt-10 border-t border-gray-700 text-gray-400">
                <p>&copy; 2025 Kenya Scouts Association. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- AI Modal -->
    <div id="ai-chat-modal">
        <div class="close-chat" id="close-ai-chat">×</div>
        <iframe src="ai-assistant.html" allowfullscreen></iframe>
    </div>

    <script>
        // Mobile menu
        document.getElementById('mobile-menu-btn').onclick = () => {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        };

        // AI Modal
        const modal = document.getElementById('ai-chat-modal');
        document.getElementById('open-ai-chat').onclick = () => modal.style.display = 'block';
        document.getElementById('close-ai-chat').onclick = () => modal.style.display = 'none';
        window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };

        // Load SVG Map & Make Interactive
        fetch('assets/svg/ke.svg')
            .then(r => r.text())
            .then(svg => {
                document.getElementById('map-container').innerHTML = svg;
                const tooltip = document.createElement('div');
                tooltip.id = 'map-tooltip';
                document.getElementById('map-container').appendChild(tooltip);

                document.querySelectorAll('path, polygon').forEach(path => {
                    let name = path.getAttribute('id') || path.getAttribute('name') || 'County';
                    name = name.replace(/_/g, ' ').replace(/-/g, ' ');
                    path.classList.add('county');

                    path.addEventListener('mouseenter', e => {
                        tooltip.textContent = name;
                        tooltip.style.opacity = '1';
                        tooltip.style.left = (e.pageX + 15) + 'px';
                        tooltip.style.top = (e.pageY + 15) + 'px';
                    });
                    path.addEventListener('mousemove', e => {
                        tooltip.style.left = (e.pageX + 15) + 'px';
                        tooltip.style.top = (e.pageY + 15) + 'px';
                    });
                    path.addEventListener('mouseleave', () => tooltip.style.opacity = '0');
                    path.addEventListener('click', () => alert(`Scouting is active in ${name} County!`));
                });
            });
    </script>
</body>
</html>
