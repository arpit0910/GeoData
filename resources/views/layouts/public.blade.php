<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'GeoData API')</title>
    <!-- Preconnect to external CDNs for faster asset loading -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #000000; color: #ffffff; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .text-gradient { background: linear-gradient(to right, #f59e0b, #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        /* Dark mode overrides for common elements */
        input, select, textarea { background-color: rgba(255,255,255,0.05) !important; color: white !important; border: 1px solid rgba(255,255,255,0.2) !important; }
        label { color: rgba(255,255,255,0.7) !important; }
    </style>
</head>
<body class="bg-[#000000] text-gray-100 antialiased selection:bg-amber-500 selection:text-white flex flex-col min-h-screen">
    @if(request()->routeIs('home'))
    <!-- Globe Background Container (Top Right Only) -->
    <div id="globe-canvas-container" class="absolute top-0 right-0 w-full lg:w-[60%] h-[900px] lg:h-[1100px] z-0 pointer-events-none opacity-100 overflow-hidden" style="mask-image: linear-gradient(to bottom, black 60%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 60%, transparent 100%);"></div>
    @endif

    <!-- Navbar -->
    <nav x-data="{ mobileMenuOpen: false }" class="bg-transparent backdrop-blur-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex-shrink-0 flex items-center gap-2 group">
                        <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-lg flex items-center justify-center font-bold text-xl shadow-md border border-amber-400 group-hover:shadow-lg transition-all">
                            <i class="fas fa-globe-americas"></i>
                        </div>
                        <span class="font-extrabold text-2xl tracking-tight text-white">Geo<span class="text-amber-500">Data</span></span>
                    </a>
                    
                    <div class="hidden md:ml-12 md:flex md:space-x-8">
                        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'border-amber-500 text-white' : 'border-transparent text-gray-400 hover:text-white hover:border-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-colors">
                            Home
                        </a>
                        <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'border-amber-500 text-white' : 'border-transparent text-gray-400 hover:text-white hover:border-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-colors">
                            About Us
                        </a>
                        <a href="{{ route('pricing') }}" class="{{ request()->routeIs('pricing') ? 'border-amber-500 text-white' : 'border-transparent text-gray-400 hover:text-white hover:border-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-colors">
                            Pricing
                        </a>
                        <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'border-amber-500 text-white' : 'border-transparent text-gray-400 hover:text-white hover:border-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-colors">
                            Contact
                        </a>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-300 hover:text-amber-500 text-sm font-semibold px-3 py-2 rounded-md transition-colors">
                            Dashboard
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-white/10 text-white hover:bg-white/20 px-4 py-2 border border-white/20 rounded-lg text-sm font-bold transition-all shadow-sm">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-300 hover:text-amber-500 text-sm font-semibold px-3 py-2 rounded-md transition-colors">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="bg-gradient-to-r from-amber-600 to-amber-500 text-white hover:from-amber-700 hover:to-amber-600 px-5 py-2.5 rounded-lg text-sm font-bold transition-all shadow-md transform hover:-translate-y-0.5 border border-amber-600">
                            Get Started
                        </a>
                    @endauth
                </div>
                
                <div class="flex items-center md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-amber-600 hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-500">
                        <span class="sr-only">Open main menu</span>
                        <i class="fas fa-bars text-xl" x-show="!mobileMenuOpen"></i>
                        <i class="fas fa-times text-xl" x-show="mobileMenuOpen" x-cloak></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" class="md:hidden border-b border-white/10 bg-gray-900/90 backdrop-blur-xl" x-collapse x-cloak>
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'bg-amber-500/10 border-amber-500 text-amber-500' : 'border-transparent text-gray-400 hover:bg-white/5 hover:text-white' }} block pl-3 pr-4 py-3 border-l-4 text-base font-semibold transition-all">Home</a>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'bg-amber-500/10 border-amber-500 text-amber-500' : 'border-transparent text-gray-400 hover:bg-white/5 hover:text-white' }} block pl-3 pr-4 py-3 border-l-4 text-base font-semibold transition-all">About</a>
                <a href="{{ route('pricing') }}" class="{{ request()->routeIs('pricing') ? 'bg-amber-500/10 border-amber-500 text-amber-500' : 'border-transparent text-gray-400 hover:bg-white/5 hover:text-white' }} block pl-3 pr-4 py-3 border-l-4 text-base font-semibold transition-all">Pricing</a>
                <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'bg-amber-500/10 border-amber-500 text-amber-500' : 'border-transparent text-gray-400 hover:bg-white/5 hover:text-white' }} block pl-3 pr-4 py-3 border-l-4 text-base font-semibold transition-all">Contact</a>
            </div>
            <div class="pt-4 pb-4 border-t border-white/10 px-4 space-y-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="block w-full text-center bg-white/10 text-white hover:bg-white/20 px-4 py-3 rounded-lg text-base font-bold transition-all">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button type="submit" class="w-full bg-transparent border border-white/20 text-gray-400 hover:text-white hover:bg-white/5 px-4 py-3 rounded-lg text-base font-bold transition-all">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block w-full text-center bg-white/10 text-white hover:bg-white/20 px-4 py-3 rounded-lg text-base font-bold transition-all">Log in</a>
                    <a href="{{ route('register') }}" class="block w-full text-center bg-gradient-to-r from-amber-600 to-amber-500 text-white hover:from-amber-700 hover:to-amber-600 px-4 py-3 rounded-lg text-base font-bold shadow-md transition-all">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow relative z-10">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 pt-16 pb-8 mt-auto relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-2">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 bg-amber-600 text-white rounded-md flex items-center justify-center font-bold text-sm">
                            <i class="fas fa-globe-americas"></i>
                        </div>
                        <span class="font-extrabold text-2xl tracking-tight text-white">Geo<span class="text-amber-500">Data</span></span>
                    </a>
                    <p class="text-gray-400 text-sm leading-relaxed max-w-sm mb-6 font-medium">
                        Empowering applications with the most accurate, high-speed, and reliable geographic data APIs available globally.
                    </p>
                    <div class="flex space-x-5">
                        <a href="#" class="text-gray-500 hover:text-amber-500 transition-colors"><i class="fab fa-twitter text-xl"></i></a>
                        <a href="#" class="text-gray-500 hover:text-amber-500 transition-colors"><i class="fab fa-github text-xl"></i></a>
                        <a href="#" class="text-gray-500 hover:text-amber-500 transition-colors"><i class="fab fa-linkedin text-xl"></i></a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-xs font-bold text-gray-300 tracking-widest uppercase mb-5">Product</h3>
                    <ul class="space-y-4">
                        <li><a href="{{ route('pricing') }}" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="{{ route('docs') }}" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Documentation</a></li>
                        <li><a href="{{ route('status') }}" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">API Status</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-bold text-gray-300 tracking-widest uppercase mb-5">Company</h3>
                    <ul class="space-y-4">
                        <li><a href="{{ route('about') }}" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="{{ route('contact') }}" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Contact</a></li>
                        <li><a href="{{ route('privacy') }}" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm font-medium text-gray-500">&copy; {{ date('Y') }} GeoData API Providers. All rights reserved.</p>
                <div class="mt-4 md:mt-0 flex items-center space-x-2 text-sm font-medium text-gray-500">
                    Made with <i class="fas fa-heart text-red-500 mx-1"></i> for global developers
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
    
    @if(request()->routeIs('home'))
    <!-- Three.js and Globe Implementation -->
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        // Defer 3D rendering until entire page is fully loaded to prevent blocking UI
        window.addEventListener('load', function() {
            const container = document.getElementById('globe-canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            
            const containerHeight = container.clientHeight;
            const containerWidth = container.clientWidth;
            renderer.setSize(containerWidth, containerHeight);
            renderer.setPixelRatio(window.devicePixelRatio);
            container.appendChild(renderer.domElement);

            // Lighting
            // Only SpotLight for Day side
            const spotLight = new THREE.SpotLight(0xffffff, 2.0);
            spotLight.position.set(5, 3, 5);
            scene.add(spotLight);

            // Globe Geometry
            const globeRadius = 2.5;
            const geometry = new THREE.SphereGeometry(globeRadius, 64, 64);
            
            // Texture Loader (Migrated from GitHub raw to high-speed CDN to fix load times)
            const loader = new THREE.TextureLoader();
            const earthTexture = loader.load('https://cdn.jsdelivr.net/gh/mrdoob/three.js@r128/examples/textures/planets/earth_atmos_2048.jpg');
            const earthNormal = loader.load('https://cdn.jsdelivr.net/gh/mrdoob/three.js@r128/examples/textures/planets/earth_normal_2048.jpg');
            const earthLights = loader.load('https://cdn.jsdelivr.net/gh/mrdoob/three.js@r128/examples/textures/planets/earth_lights_2048.png');

            // Custom Shader for Day/Night effect
            const earthShader = {
                uniforms: {
                    dayTexture: { value: earthTexture },
                    nightTexture: { value: earthLights },
                    sunDirection: { value: new THREE.Vector3(1, 0, 0).normalize() }
                },
                vertexShader: `
                    varying vec2 vUv;
                    varying vec3 vNormal;
                    void main() {
                        vUv = uv;
                        vNormal = normalize(normalMatrix * normal);
                        gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                    }
                `,
                fragmentShader: `
                    uniform sampler2D dayTexture;
                    uniform sampler2D nightTexture;
                    uniform vec3 sunDirection;
                    varying vec2 vUv;
                    varying vec3 vNormal;
                    void main() {
                        vec3 dayColor = texture2D(dayTexture, vUv).rgb;
                        vec3 nightColor = texture2D(nightTexture, vUv).rgb;
                        
                        // Dot product to find light intensity
                        float intensity = dot(vNormal, sunDirection);
                        
                        // Super soft blend for a cinematic twilight zone
                        float mixAmount = smoothstep(-0.3, 0.3, intensity);
                        
                        // Night side: Mask the city lights
                        vec3 nightLights = nightColor;
                        nightLights.b *= 0.1; // Suppress ambient blue
                        
                        float luma = dot(nightLights, vec3(0.299, 0.587, 0.114));
                        if (luma < 0.12) nightLights = vec3(0.0); // Slightly more inclusive
                        else nightLights *= 15.0;
                        nightLights.b = 0.0;
                        
                        // Composite with soft mix
                        vec3 finalDay = dayColor * mixAmount;
                        vec3 finalNight = nightLights * (1.0 - mixAmount);
                        
                        gl_FragColor = vec4(finalDay + finalNight, 1.0);
                    }
                `
            };

            const material = new THREE.ShaderMaterial({
                uniforms: earthShader.uniforms,
                vertexShader: earthShader.vertexShader,
                fragmentShader: earthShader.fragmentShader
            });

            const earth = new THREE.Mesh(geometry, material);
            scene.add(earth);

            // Connecting Lines (Arcs)
            function latLongToVector3(lat, lon, radius) {
                const phi = (90 - lat) * (Math.PI / 180);
                const theta = (lon + 180) * (Math.PI / 180);
                return new THREE.Vector3(
                    -radius * Math.sin(phi) * Math.cos(theta),
                    radius * Math.cos(phi),
                    radius * Math.sin(phi) * Math.sin(theta)
                );
            }

            function createArc(v1, v2) {
                const mid = v1.clone().lerp(v2, 0.5);
                mid.normalize().multiplyScalar(globeRadius * 1.4);
                const curve = new THREE.QuadraticBezierCurve3(v1, mid, v2);
                const points = curve.getPoints(50);
                const geometry = new THREE.BufferGeometry().setFromPoints(points);
                const lineMaterial = new THREE.LineBasicMaterial({ 
                    color: 0xcc9933, 
                    transparent: true, 
                    opacity: 0.25,
                    blending: THREE.AdditiveBlending 
                });
                return new THREE.Line(geometry, lineMaterial);
            }

            function createPoint(v) {
                const pointGeo = new THREE.SphereGeometry(0.015, 12, 12);
                const pointMat = new THREE.MeshBasicMaterial({ 
                    color: 0xffcc33,
                    transparent: true,
                    opacity: 0.8
                });
                const point = new THREE.Mesh(pointGeo, pointMat);
                point.position.copy(v);
                return point;
            }

            // Elegant selection of global hubs
            const locations = [
                {lat: 40.7128, lon: -74.0060}, // NY
                {lat: 34.0522, lon: -118.2437}, // LA
                {lat: 51.5074, lon: -0.1278}, // London
                {lat: 35.6762, lon: 139.6503}, // Tokyo
                {lat: -33.8688, lon: 151.2093}, // Sydney
                {lat: 28.6139, lon: 77.2090}, // Delhi
                {lat: -23.5505, lon: -46.6333}, // Sao Paulo
                {lat: 48.8566, lon: 2.3522}, // Paris
                {lat: 1.3521, lon: 103.8198}, // Singapore
                {lat: 25.2048, lon: 55.2708}  // Dubai
            ];

            const arcs = new THREE.Group();
            const points = new THREE.Group();
            for(let i=0; i<locations.length; i++) {
                const start = latLongToVector3(locations[i].lat, locations[i].lon, globeRadius);
                points.add(createPoint(start));
                
                // Fewer, more focused connections
                const nextIndex = (i + 3) % locations.length;
                const end = latLongToVector3(locations[nextIndex].lat, locations[nextIndex].lon, globeRadius);
                arcs.add(createArc(start, end));

                const acrossIndex = (i + 5) % locations.length;
                const endAcross = latLongToVector3(locations[acrossIndex].lat, locations[acrossIndex].lon, globeRadius);
                arcs.add(createArc(start, endAcross));
            }
            earth.add(arcs);
            earth.add(points);

            // Stars (Optimized count for better initialization and render performance)
            const starGeometry = new THREE.BufferGeometry();
            const starMaterial = new THREE.PointsMaterial({ color: 0xffffff, size: 0.1 });
            const starVertices = [];
            for (let i = 0; i < 3000; i++) {
                const x = (Math.random() - 0.5) * 2000;
                const y = (Math.random() - 0.5) * 2000;
                const z = (Math.random() - 0.5) * 2000;
                starVertices.push(x, y, z);
            }
            starGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starVertices, 3));
            const stars = new THREE.Points(starGeometry, starMaterial);
            scene.add(stars);

            camera.position.z = 6;

            // Responsive
            window.addEventListener('resize', () => {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });

            // Scroll interaction removed based on requirements.
            
            function animate() {
                requestAnimationFrame(animate);
                
                // Pure automatic base rotation
                earth.rotation.y += 0.001; 
                
                // Add a subtle floating effect with time
                const time = Date.now() * 0.001;
                earth.rotation.y = time * 0.15;
                earth.rotation.x = 0.2; // Slight tilt
                
                // Keep earth stable at the center organically (since container is now 60% width on right)
                earth.position.x = Math.sin(time * 0.5) * 0.05;
                earth.position.y = -0.1 + Math.cos(time * 0.3) * 0.05;
                
                // Pulse arcs
                arcs.children.forEach(line => {
                    line.material.opacity = 0.15 + Math.sin(Date.now() * 0.002) * 0.1;
                });
                
                // Pulse points
                points.children.forEach(p => {
                    const s = 1 + Math.sin(Date.now() * 0.004) * 0.3;
                    p.scale.set(s, s, s);
                    p.material.opacity = 0.5 + Math.sin(Date.now() * 0.004) * 0.3;
                });
                
                // Pause rendering when tab is inactive to save battery/CPU
                if (!document.hidden) renderer.render(scene, camera);
            }

            animate();
        });
    </script>
    @endif
</body>
</html>
