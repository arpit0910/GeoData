<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - SetuGeo API</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
    
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 border border-white/10 relative z-10 transition-all duration-500">
        <div class="text-center mb-8">
            <a href="{{ url('/') }}" class="inline-flex items-center justify-center">
                <img src="{{ asset('assets/img/logo.png') }}" alt="SetuGeo Logo" class="h-20 w-auto">
            </a>
            <h2 class="text-2xl font-bold mt-6">Forgot Password?</h2>
            <p class="text-gray-500 mt-2 text-sm px-4">Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        @if (session('status'))
            <div class="bg-green-500/10 border-l-4 border-green-500 p-4 mb-6 rounded-r-md">
                <p class="text-sm text-green-400 font-medium">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-500/10 border-l-4 border-red-500 p-4 mb-6 rounded-r-md">
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
                <label for="email" class="block text-sm font-medium">Email Address</label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" placeholder="registered@email.com"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-500 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-all bg-transparent">
                </div>
            </div>

            <div class="mt-10">
                <button type="submit" class="w-full flex justify-center py-3.5 px-8 border border-transparent rounded-2xl shadow-xl text-lg font-black text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Send Reset Link <i class="fas fa-paper-plane ml-3 text-sm mt-1.5"></i>
                </button>
            </div>
        </form>

        <div class="mt-8 text-center text-sm text-gray-600 border-t border-white/5 pt-6">
            Remembered your password? 
            <a href="{{ route('login') }}" class="font-medium text-amber-600 hover:text-amber-500 transition-colors">Sign in here</a>
        </div>
    </div>
</body>
</html>
