<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - SetuGeo API</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #000000; color: #ffffff; }
        .orange-gradient-line {
            height: 1px;
            background: linear-gradient(to right, transparent, #f59e0b, transparent);
            border: none;
        }
        input { 
            background-color: rgba(255,255,255,0.03) !important; 
            color: white !important; 
            border: 1px solid rgba(255,255,255,0.1) !important; 
            transition: all 0.3s ease;
        }
        input:focus { 
            border-color: #f59e0b !important; 
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.1) !important;
            background-color: rgba(255,255,255,0.05) !important;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            border-left: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="bg-[#000000] h-screen flex overflow-hidden">
    
    <!-- Left Side: Company Details -->
    <div class="hidden lg:flex lg:w-1/2 relative flex-col justify-center items-center px-12 xl:px-24 overflow-hidden">
        <!-- Background decorative elements -->
        <div class="absolute top-0 left-0 w-full h-full z-0 opacity-30">
            <div class="absolute top-[-10%] left-[-10%] w-[600px] h-[600px] rounded-full bg-amber-500/20 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[400px] h-[400px] rounded-full bg-yellow-500/10 blur-[100px]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#ffffff05_1px,transparent_1px)] [background-size:32px_32px]"></div>
        </div>

        <div class="relative z-10">
            <a href="{{ url('/') }}" class="inline-block mb-12">
                <img src="{{ asset('assets/img/logo.png') }}" alt="SetuGeo Logo" class="h-24 w-auto drop-shadow-[0_0_15px_rgba(245,158,11,0.3)]">
            </a>
            
            <h1 class="text-5xl xl:text-7xl font-extrabold text-white leading-[1.2] mb-6">
                Recovery with <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-yellow-500">peace of mind.</span>
            </h1>
            
            <p class="text-xl text-gray-400 max-w-lg mb-12 leading-relaxed">
                Lost your access? No worries. Follow the secure instructions sent to your email to regain access to your geographic data infrastructure.
            </p>

            <div class="space-y-6">
                <div class="flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center border border-amber-500/20 group-hover:border-amber-500/50 transition-all">
                        <i class="fas fa-lock text-amber-500"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold">Secure Recovery</h3>
                        <p class="text-gray-500 text-sm">Encrypted tokens with short expiration for your safety.</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center border border-amber-500/20 group-hover:border-amber-500/50 transition-all">
                        <i class="fas fa-headset text-amber-500"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold">24/7 Priority Support</h3>
                        <p class="text-gray-500 text-sm">Stuck? Contact our support for manual verification.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side: Reset Form -->
    <div class="w-full lg:w-1/2 flex justify-center p-6 sm:p-12 relative glass-panel h-screen overflow-y-auto">
        <div class="max-w-md w-full my-auto lg:py-10">
            <!-- Mobile Logo -->
            <div class="lg:hidden text-center mb-10">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('assets/img/logo.png') }}" alt="SetuGeo Logo" class="h-16 w-auto mx-auto mb-4">
                </a>
                <h2 class="text-2xl font-bold text-white">Reset Password</h2>
            </div>

            <div class="mb-6 hidden lg:block">
                <h2 class="text-4xl font-extrabold text-white mb-2 leading-snug">Recovery</h2>
                <p class="text-gray-400 text-sm">Enter your email address to receive a reset link.</p>
            </div>

            @if (session('status'))
                <div class="bg-green-500/10 border border-green-500/20 p-4 mb-8 rounded-2xl">
                    <p class="text-sm text-green-400 font-medium">{{ session('status') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/20 p-4 mb-8 rounded-2xl">
                    <ul class="list-disc list-inside text-sm text-red-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-400 mb-2">Email Address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" placeholder="name@company.com"
                        class="block w-full px-5 py-4 rounded-2xl focus:outline-none sm:text-sm">
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex items-center justify-center py-4 px-6 border border-transparent rounded-2xl text-lg font-bold text-white bg-amber-600 hover:bg-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/30 transition-all duration-300 shadow-[0_10px_30px_rgba(245,158,11,0.2)]">
                        Send reset link
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </button>
                </div>
            </form>

            <div class="mt-8">
                <div class="orange-gradient-line mb-6"></div>
                <p class="text-center text-sm text-gray-500">
                    Suddenly remembered? 
                    <a href="{{ route('login') }}" class="font-bold text-amber-500 hover:text-amber-400 ml-1 transition-colors">Back to Sign In</a>
                </p>
            </div>

            <div class="mt-8 text-center">
                <a href="{{ url('/') }}" class="text-xs font-bold text-gray-600 hover:text-gray-400 inline-flex items-center transition-colors uppercase tracking-widest">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Website
                </a>
            </div>
        </div>
    </div>
</body>
</html>
