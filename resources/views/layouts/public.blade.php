<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'GeoData API')</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased selection:bg-amber-500 selection:text-white flex flex-col min-h-screen">

    <!-- Navbar -->
    <nav x-data="{ mobileMenuOpen: false }" class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex-shrink-0 flex items-center gap-2 group">
                        <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-lg flex items-center justify-center font-bold text-xl shadow-md border border-amber-400 group-hover:shadow-lg transition-all">
                            <i class="fas fa-globe-americas"></i>
                        </div>
                        <span class="font-extrabold text-2xl tracking-tight text-gray-900">Geo<span class="text-amber-600">Data</span></span>
                    </a>
                    
                    <div class="hidden md:ml-12 md:flex md:space-x-8">
                        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'border-amber-600 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-colors">
                            Home
                        </a>
                        <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'border-amber-600 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-colors">
                            About Us
                        </a>
                        <a href="{{ route('pricing') }}" class="{{ request()->routeIs('pricing') ? 'border-amber-600 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-colors">
                            Pricing
                        </a>
                        <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'border-amber-600 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-colors">
                            Contact
                        </a>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-amber-600 text-sm font-semibold px-3 py-2 rounded-md transition-colors">
                            Dashboard
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 border border-gray-200 rounded-lg text-sm font-bold transition-all shadow-sm">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-amber-600 text-sm font-semibold px-3 py-2 rounded-md transition-colors">
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
        <div x-show="mobileMenuOpen" class="md:hidden border-b border-gray-200 bg-white" x-collapse x-cloak>
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'bg-amber-50 border-amber-500 text-amber-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-3 border-l-4 text-base font-semibold">Home</a>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'bg-amber-50 border-amber-500 text-amber-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-3 border-l-4 text-base font-semibold">About</a>
                <a href="{{ route('pricing') }}" class="{{ request()->routeIs('pricing') ? 'bg-amber-50 border-amber-500 text-amber-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-3 border-l-4 text-base font-semibold">Pricing</a>
                <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'bg-amber-50 border-amber-500 text-amber-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }} block pl-3 pr-4 py-3 border-l-4 text-base font-semibold">Contact</a>
            </div>
            <div class="pt-4 pb-4 border-t border-gray-100 px-4 space-y-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="block w-full text-center bg-gray-100 text-gray-800 hover:bg-gray-200 px-4 py-3 rounded-lg text-base font-bold">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button type="submit" class="w-full bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-3 rounded-lg text-base font-bold">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block w-full text-center bg-gray-100 text-gray-800 hover:bg-gray-200 px-4 py-3 rounded-lg text-base font-bold">Log in</a>
                    <a href="{{ route('register') }}" class="block w-full text-center bg-amber-600 text-white hover:bg-amber-700 px-4 py-3 rounded-lg text-base font-bold shadow-md">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 pt-16 pb-8 mt-auto">
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
</body>
</html>
