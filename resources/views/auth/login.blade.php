<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - GeoData API</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #000000; color: #ffffff; }
        /* Auto Dark Mode Overrides for Inputs */
        input, select, textarea { background-color: rgba(255,255,255,0.05) !important; color: white !important; border-color: rgba(255,255,255,0.1) !important; }
        input:focus, select:focus, textarea:focus { background-color: rgba(0,0,0,0.5) !important; border-color: #f59e0b !important; }
        input:-webkit-autofill, input:-webkit-autofill:hover, input:-webkit-autofill:focus, input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #0a0a0a inset !important;
            -webkit-text-fill-color: white !important;
        }
        label, .text-gray-700 { color: rgba(255,255,255,0.8) !important; }
        .text-gray-500, .text-gray-600 { color: rgba(255,255,255,0.5) !important; }
        .bg-white { background-color: rgba(10,10,10,0.8) !important; backdrop-filter: blur(16px); border-color: rgba(255,255,255,0.1) !important; }
        .bg-red-50 { background-color: rgba(239,68,68,0.1) !important; border-color: rgba(239,68,68,0.5) !important; }
        .text-red-700 { color: rgba(252,165,165,1) !important; }
    </style>
</head>
<body class="bg-[#000000] flex items-center justify-center min-h-screen selection:bg-amber-500 selection:text-white relative">
    
    <!-- GeoData Background Elements -->
    <div class="absolute inset-0 z-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:40px_40px] pointer-events-none"></div>
    <div class="absolute left-0 top-0 -z-10 w-[500px] h-[500px] rounded-full bg-amber-500 opacity-10 blur-[120px] pointer-events-none"></div>
    
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 border border-white/10 relative z-10">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-amber-500">GeoData</h1>
            <p class="text-gray-500 mt-2 text-sm">Welcome back! Please login to your account.</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-md">
                <div class="flex">
                    <div class="ml-3">
                        <ul class="list-disc list-inside text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-6">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" placeholder="Enter registered email address"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors blur-0">
                </div>
            </div>

            <div x-data="{ show: false }">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <a href="#" class="text-sm font-medium text-amber-600 hover:text-amber-500 transition-colors">Forgot password?</a>
                </div>
                <div class="mt-1 relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" autocomplete="current-password" required
                        placeholder="Enter Password"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors pr-10">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                        <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded cursor-pointer transition-colors">
                <label for="remember" class="ml-2 block text-sm text-gray-900 cursor-pointer">
                    Remember me
                </label>
            </div>

            <div class="mt-10">
                <button type="submit" class="w-full flex justify-center py-3.5 px-8 border border-transparent rounded-2xl shadow-xl text-lg font-black text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Sign In <i class="fas fa-sign-in-alt ml-2 mt-1"></i>
                </button>
            </div>
        </form>

        <div class="mt-8 text-center text-sm text-gray-600 border-t border-gray-100 pt-6">
            Don't have an account? 
            <a href="{{ route('register') }}" class="font-medium text-amber-600 hover:text-amber-500 transition-colors">Register now</a>
        </div>
        
        <div class="mt-4 text-center">
            <a href="{{ url('/') }}" class="text-sm text-gray-400 hover:text-gray-600 inline-flex items-center transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back
            </a>
        </div>
    </div>
</body>
</html>
