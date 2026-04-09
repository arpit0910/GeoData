<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - SetuGeo API</title>
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
        input { background-color: rgba(255,255,255,0.05) !important; color: white !important; border-color: rgba(255,255,255,0.1) !important; }
        input:focus { background-color: rgba(0,0,0,0.5) !important; border-color: #f59e0b !important; }
        label { color: rgba(255,255,255,0.8) !important; }
        .bg-white { background-color: rgba(10,10,10,0.8) !important; backdrop-filter: blur(16px); border-color: rgba(255,255,255,0.1) !important; }
        .text-gray-400 { color: rgba(255,255,255,0.4) !important; }
        .text-gray-500 { color: rgba(255,255,255,0.5) !important; }
        .text-gray-600 { color: rgba(255,255,255,0.6) !important; }
    </style>
</head>
<body class="bg-[#000000] flex items-center justify-center min-h-screen relative overflow-hidden">
    
    <div class="absolute inset-0 z-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:40px_40px] pointer-events-none"></div>
    <div class="absolute right-0 bottom-0 -z-10 w-[500px] h-[500px] rounded-full bg-amber-500 opacity-10 blur-[120px] pointer-events-none"></div>
    
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 border border-white/10 relative z-10">
        <div class="text-center mb-8">
            <a href="{{ url('/') }}" class="inline-flex items-center justify-center">
                <img src="{{ asset('assets/img/logo.png') }}" alt="SetuGeo Logo" class="h-20 w-auto">
            </a>
            <h2 class="text-2xl font-bold mt-6">Set New Password</h2>
            <p class="text-gray-500 mt-2 text-sm">Please choose a strong password to secure your account.</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-500/10 border-l-4 border-red-500 p-4 mb-6 rounded-r-md">
                <ul class="list-disc list-inside text-sm text-red-400">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div x-data="{ show: false }">
                <label for="password" class="block text-sm font-medium">New Password</label>
                <div class="mt-1 relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" autocomplete="new-password" required
                        placeholder="••••••••"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-500 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-all bg-transparent pr-10">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-500 hover:text-white transition-colors">
                        <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium">Confirm New Password</label>
                <div class="mt-1">
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        placeholder="••••••••"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-500 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-all bg-transparent">
                </div>
            </div>

            <div class="mt-10">
                <button type="submit" class="w-full flex justify-center py-3.5 px-8 border border-transparent rounded-2xl shadow-xl text-lg font-black text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Reset Password <i class="fas fa-key ml-3 text-sm mt-1"></i>
                </button>
            </div>
        </form>
    </div>
</body>
</html>
