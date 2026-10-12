<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}?v=1.0.1">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/favicon.png') }}?v=1.0.1">
    <title>@yield('title', 'Admin Dashboard') | {{ config('app.name', 'SetuGeo') }}</title>
    <meta name="robots" content="noindex, nofollow, noarchive">

    <!-- Tailwind CSS via CDN for instant visual improvement -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        richdark: {
                            bg: '#0b0f19',
                            surface: '#111827',
                            card: '#161e2d',
                            border: '#242f42',
                            accent: '#f59e0b'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js for interactivity -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Theme Script to prevent flash -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables with Tailwind -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <!-- Toastr for notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .dataTables_wrapper .dataTables_length select { padding-right: 2rem; border-radius: 0.375rem; border-color: #d1d5db; }
        .dataTables_wrapper .dataTables_filter input { padding: 0.5rem 0.75rem; border-radius: 0.375rem; border-color: #d1d5db; border-width: 1px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #d97706 !important; color: white !important; border: none; border-radius: 0.375rem; }
        table.dataTable { border-collapse: collapse !important; border-spacing: 0; width: 100% !important; margin-top: 1rem !important; margin-bottom: 1rem !important; }
        table.dataTable thead th { background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #374151; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 0.75rem 1.5rem; }
        table.dataTable tbody td { padding: 1rem 1.5rem; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 0.875rem; }

        /* Dark Mode DataTables */
        .dark .dataTables_wrapper { color: #94a3b8 !important; }
        .dark .dataTables_wrapper .dataTables_length,
        .dark .dataTables_wrapper .dataTables_filter,
        .dark .dataTables_wrapper .dataTables_info,
        .dark .dataTables_wrapper .dataTables_processing,
        .dark .dataTables_wrapper .dataTables_paginate { color: #94a3b8 !important; }
        .dark .dataTables_wrapper .dataTables_length select,
        .dark .dataTables_wrapper .dataTables_filter input { background-color: #161e2d !important; border-color: #242f42 !important; color: #f3f4f6 !important; }
        .dark table.dataTable thead th { background-color: #1e293b !important; border-bottom: 1px solid #2d3748 !important; color: #f3f4f6 !important; }
        .dark table.dataTable tbody td { border-bottom: 1px solid #1e293b !important; color: #cbd5e0 !important; background-color: transparent !important; }
        .dark .dataTables_wrapper .dataTables_info { color: #94a3b8 !important; }
        .dark .dataTables_wrapper .dataTables_paginate .paginate_button { color: #94a3b8 !important; }
        .dark .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #d97706 !important; color: white !important; }
        .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #242f42 !important; color: white !important; border: none !important; }

        /* Premium Dark Mode Overrides */
        .dark body { background-color: #0b1120; }
        .dark .bg-white { background-color: #111827 !important; }
        .dark .bg-gray-50 { background-color: #0b1120 !important; }
        .dark .bg-gray-100 { background-color: #1f2937 !important; }
        .dark .bg-gray-200 { background-color: #334155 !important; }
        .dark .bg-amber-50 { background-color: rgba(245, 158, 11, 0.05) !important; border-color: rgba(245, 158, 11, 0.15) !important; }
        .dark .bg-green-50 { background-color: rgba(16, 185, 129, 0.05) !important; border-color: rgba(16, 185, 129, 0.15) !important; }
        .dark .bg-blue-50 { background-color: rgba(59, 130, 246, 0.05) !important; border-color: rgba(59, 130, 246, 0.15) !important; }
        .dark .bg-indigo-50 { background-color: rgba(99, 102, 241, 0.05) !important; border-color: rgba(99, 102, 241, 0.15) !important; }
        
        .dark .text-gray-900 { color: #f8fafc !important; }
        .dark .text-gray-800 { color: #f1f5f9 !important; }
        .dark .text-gray-700 { color: #e2e8f0 !important; }
        .dark .text-gray-600 { color: #94a3b8 !important; }
        .dark .text-gray-500 { color: #64748b !important; }
        .dark .border-gray-100, 
        .dark .border-gray-200 { border-color: #1e293b !important; }
        .dark .divide-gray-200 > :not([hidden]) ~ :not([hidden]) { border-color: #1e293b !important; }
        
        .dark .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.3) !important; }
        .dark .shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4), 0 2px 4px -1px rgba(0, 0, 0, 0.2) !important; }
        
        /* Glassmorphism Classes */
        .glass-dark { background: rgba(17, 24, 39, 0.7) !important; backdrop-filter: blur(12px) !important; -webkit-backdrop-filter: blur(12px) !important; border: 1px solid rgba(255, 255, 255, 0.05) !important; }
        
        /* Form elements premium dark */
        .dark input:not([type="checkbox"]):not([type="radio"]), 
        .dark select, 
        .dark textarea { background-color: #0f172a !important; border-color: #1e293b !important; color: #f8fafc !important; }
        .dark input:focus { border-color: #f59e0b !important; ring-color: rgba(245, 158, 11, 0.2) !important; }
        
        /* Custom scrollbar rich dark */
        .dark ::-webkit-scrollbar { width: 8px; }
        .dark ::-webkit-scrollbar-track { background: #0b0f19; }
        .dark ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; border: 2px solid #0b0f19; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</head>
<body class="h-full">
    <div class="h-screen flex flex-col overflow-hidden">
        <!-- Sidebar and Content wrapper -->
        <div class="flex flex-1 overflow-hidden" x-data="{ sidebarOpen: false }">
            <!-- Mobile backdrop -->
            <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm md:hidden" style="display: none;"></div>

            <!-- Navigation -->
            <nav :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}" class="-translate-x-full md:translate-x-0 transform fixed md:static inset-y-0 left-0 z-50 w-64 bg-amber-600 dark:bg-[#080c14] text-white flex-shrink-0 flex flex-col transition-transform duration-300 border-r dark:border-white/5 overflow-y-auto">
                <div class="p-6">
                    <a href="{{ route('home') }}" class="flex items-center group">
                        <img src="{{ asset('assets/img/logo.png') }}?v=1.0.1" alt="SetuGeo Logo" class="h-20 w-auto">
                    </a>
                </div>
                <div class="mt-4 px-3 pb-8 space-y-1">
                    {{-- General Overview Links --}}
                    <a href="{{ route('dashboard') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-home"></i></span>
                        <span class="truncate">Dashboard</span>
                    </a>
                    
                    <a href="{{ route('profile.index') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('profile.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-user-circle"></i></span>
                        <span class="truncate">Profile</span>
                    </a>

                    <a href="{{ route('docs') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('docs') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-book-open"></i></span>
                        <span class="truncate">Docs</span>
                    </a>

                    <a href="{{ route('market.index') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('market.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-tower-broadcast"></i></span>
                        <span class="truncate flex-1">Live Market</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse ml-2 flex-shrink-0"></span>
                    </a>

                    {{-- Customer / Non-Admin Links --}}
                    @if(auth()->check() && !auth()->user()->is_admin)
                    <div class="pt-4 pb-1">
                        <p class="px-3 text-[10px] font-bold text-amber-200/80 dark:text-gray-500 uppercase tracking-[0.2em]">API Access</p>
                    </div>
                    <a href="{{ route('available-apis.index') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('available-apis.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-code"></i></span>
                        <span class="truncate">Available APIs</span>
                    </a>

                    <a href="{{ route('api-keys.index') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('api-keys.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-key"></i></span>
                        <span class="truncate">API Keys</span>
                    </a>

                    <a href="{{ route('api-logs.index') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('api-logs.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-history"></i></span>
                        <span class="truncate">API Logs</span>
                    </a>

                    <a href="{{ route('transactions.index') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('transactions.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-file-invoice"></i></span>
                        <span class="truncate">Transactions</span>
                    </a>

                    <a href="{{ route('support.index') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('support.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-life-ring"></i></span>
                        <span class="truncate">Help & Support</span>
                    </a>
                    @endif
                    
                    {{-- Admin Sections --}}
                    @if(auth()->check() && auth()->user()->is_admin)

                    {{-- 1. MARKET DATA --}}
                    <div class="pt-4 pb-1">
                        <p class="px-3 text-[10px] font-bold text-amber-200/80 dark:text-gray-500 uppercase tracking-[0.2em]">Market Data</p>
                    </div>

                    {{-- Equities Dropdown --}}
                    @php
                        $isEquitiesActive = request()->routeIs('equities.*');
                    @endphp
                    <div x-data="{ open: {{ $isEquitiesActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg {{ $isEquitiesActive ? 'text-white bg-amber-700/60 dark:bg-amber-500/10 dark:text-amber-400 font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                            <div class="flex items-center min-w-0">
                                <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-chart-line"></i></span>
                                <span class="truncate">Equities</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] ml-2 transition-transform duration-200 opacity-70" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-3 ml-5 border-l border-amber-400/30 dark:border-white/10">
                            <a href="{{ route('equities.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('equities.index') || request()->routeIs('equities.show') || request()->routeIs('equities.edit') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('equities.index') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">All Equities</span>
                            </a>
                            <a href="{{ route('equities.prices') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('equities.prices*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('equities.prices*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Equity Prices</span>
                            </a>
                            <a href="{{ route('equities.quotes') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('equities.quotes*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('equities.quotes*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Latest Quotes</span>
                            </a>
                        </div>
                    </div>

                    {{-- Indices Dropdown --}}
                    @php
                        $isIndicesActive = request()->routeIs('admin.indices.*');
                    @endphp
                    <div x-data="{ open: {{ $isIndicesActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg {{ $isIndicesActive ? 'text-white bg-amber-700/60 dark:bg-amber-500/10 dark:text-amber-400 font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                            <div class="flex items-center min-w-0">
                                <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-arrow-trend-up"></i></span>
                                <span class="truncate">Indices</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] ml-2 transition-transform duration-200 opacity-70" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-3 ml-5 border-l border-amber-400/30 dark:border-white/10">
                            <a href="{{ route('admin.indices.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.indices.index') || request()->routeIs('admin.indices.show') || request()->routeIs('admin.indices.edit') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.indices.index') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">All Indices</span>
                            </a>
                            <a href="{{ route('admin.indices.quotes') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.indices.quotes*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.indices.quotes*') ? 'bg-emerald-400 animate-pulse' : 'bg-emerald-400/60' }}"></span>
                                <span class="truncate">Live Index Quotes</span>
                            </a>
                            <a href="{{ route('admin.indices.prices') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.indices.prices*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.indices.prices*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Index Prices</span>
                            </a>
                        </div>
                    </div>

                    {{-- Mutual Funds Dropdown --}}
                    @php
                        $isMfActive = request()->routeIs('mutual-funds.*');
                    @endphp
                    <div x-data="{ open: {{ $isMfActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg {{ $isMfActive ? 'text-white bg-amber-700/60 dark:bg-amber-500/10 dark:text-amber-400 font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                            <div class="flex items-center min-w-0">
                                <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-chart-pie"></i></span>
                                <span class="truncate">Mutual Funds</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] ml-2 transition-transform duration-200 opacity-70" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-3 ml-5 border-l border-amber-400/30 dark:border-white/10">
                            <a href="{{ route('mutual-funds.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('mutual-funds.index') || request()->routeIs('mutual-funds.show') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('mutual-funds.index') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">All Mutual Funds</span>
                            </a>
                            <a href="{{ route('mutual-funds.prices') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('mutual-funds.prices*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('mutual-funds.prices*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">MF NAV Prices</span>
                            </a>
                        </div>
                    </div>

                    {{-- Market Content Dropdown --}}
                    @php
                        $isDatasetsActive = request()->routeIs('admin.market-news.*') || request()->routeIs('admin.corporate-actions.*') || request()->routeIs('admin.exchange-calendar.*') || request()->routeIs('admin.market-datasets.*');
                    @endphp
                    <div x-data="{ open: {{ $isDatasetsActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg {{ $isDatasetsActive ? 'text-white bg-amber-700/60 dark:bg-amber-500/10 dark:text-amber-400 font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                            <div class="flex items-center min-w-0">
                                <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-database"></i></span>
                                <span class="truncate">Market Content</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] ml-2 transition-transform duration-200 opacity-70" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-3 ml-5 border-l border-amber-400/30 dark:border-white/10">
                            <a href="{{ route('admin.market-news.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.market-news.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.market-news.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Market News</span>
                            </a>
                            <a href="{{ route('admin.corporate-actions.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.corporate-actions.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.corporate-actions.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Corporate Actions</span>
                            </a>
                            <a href="{{ route('admin.exchange-calendar.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.exchange-calendar.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.exchange-calendar.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Exchange Calendar</span>
                            </a>
                            <a href="{{ route('admin.market-datasets.company-fundamentals') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.market-datasets.company-fundamentals*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.market-datasets.company-fundamentals*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Fundamentals</span>
                            </a>
                            <a href="{{ route('admin.market-datasets.global-instruments') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.market-datasets.global-instruments*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.market-datasets.global-instruments*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Global Instruments</span>
                            </a>
                        </div>
                    </div>

                    {{-- 2. GEOGRAPHY & BANKING --}}
                    <div class="pt-4 pb-1">
                        <p class="px-3 text-[10px] font-bold text-amber-200/80 dark:text-gray-500 uppercase tracking-[0.2em]">Geo &amp; Banking</p>
                    </div>

                    {{-- Locations Dropdown --}}
                    @php
                        $isGeoActive = request()->routeIs('countries.*') || request()->routeIs('states.*') || request()->routeIs('cities.*') || request()->routeIs('regions.*') || request()->routeIs('subregions.*') || request()->routeIs('pincodes.*') || request()->routeIs('timezones.*');
                    @endphp
                    <div x-data="{ open: {{ $isGeoActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg {{ $isGeoActive ? 'text-white bg-amber-700/60 dark:bg-amber-500/10 dark:text-amber-400 font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                            <div class="flex items-center min-w-0">
                                <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-globe"></i></span>
                                <span class="truncate">Locations</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] ml-2 transition-transform duration-200 opacity-70" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-3 ml-5 border-l border-amber-400/30 dark:border-white/10">
                            <a href="{{ route('countries.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('countries.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('countries.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Countries</span>
                            </a>
                            <a href="{{ route('states.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('states.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('states.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">States</span>
                            </a>
                            <a href="{{ route('cities.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('cities.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('cities.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Cities</span>
                            </a>
                            <a href="{{ route('regions.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('regions.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('regions.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Regions</span>
                            </a>
                            <a href="{{ route('subregions.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('subregions.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('subregions.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Sub Regions</span>
                            </a>
                            <a href="{{ route('pincodes.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('pincodes.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('pincodes.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Pincodes</span>
                            </a>
                            <a href="{{ route('timezones.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('timezones.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('timezones.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Timezones</span>
                            </a>
                        </div>
                    </div>

                    {{-- Banking & Currency Dropdown --}}
                    @php
                        $isBankingActive = request()->routeIs('banks.*') || request()->routeIs('bank-branches.*') || request()->routeIs('admin.currency-conversions.*');
                    @endphp
                    <div x-data="{ open: {{ $isBankingActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg {{ $isBankingActive ? 'text-white bg-amber-700/60 dark:bg-amber-500/10 dark:text-amber-400 font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                            <div class="flex items-center min-w-0">
                                <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-university"></i></span>
                                <span class="truncate">Banking &amp; Rates</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] ml-2 transition-transform duration-200 opacity-70" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-3 ml-5 border-l border-amber-400/30 dark:border-white/10">
                            <a href="{{ route('banks.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('banks.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('banks.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Banks</span>
                            </a>
                            <a href="{{ route('bank-branches.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('bank-branches.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('bank-branches.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Bank Branches</span>
                            </a>
                            <a href="{{ route('admin.currency-conversions.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.currency-conversions.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.currency-conversions.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Currency Rates</span>
                            </a>
                        </div>
                    </div>

                    {{-- 3. MANAGEMENT & BILLING --}}
                    <div class="pt-4 pb-1">
                        <p class="px-3 text-[10px] font-bold text-amber-200/80 dark:text-gray-500 uppercase tracking-[0.2em]">Management</p>
                    </div>

                    {{-- Users (Direct Item) --}}
                    <a href="{{ route('user.list') }}" class="w-full flex items-center px-3.5 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('user.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-users"></i></span>
                        <span class="truncate">Users</span>
                    </a>

                    {{-- Plans & Subscriptions Dropdown --}}
                    @php
                        $isBillingActive = request()->routeIs('plans.*') || request()->routeIs('subscription-features.*') || request()->routeIs('admin.subscriptions.*') || request()->routeIs('admin.coupons.*') || request()->routeIs('admin.transactions.*');
                    @endphp
                    <div x-data="{ open: {{ $isBillingActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg {{ $isBillingActive ? 'text-white bg-amber-700/60 dark:bg-amber-500/10 dark:text-amber-400 font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                            <div class="flex items-center min-w-0">
                                <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-file-invoice-dollar"></i></span>
                                <span class="truncate">Plans &amp; Billing</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] ml-2 transition-transform duration-200 opacity-70" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-3 ml-5 border-l border-amber-400/30 dark:border-white/10">
                            <a href="{{ route('plans.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('plans.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('plans.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Plans</span>
                            </a>
                            <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.subscriptions.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.subscriptions.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Subscriptions</span>
                            </a>
                            <a href="{{ route('subscription-features.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('subscription-features.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('subscription-features.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Subscription Features</span>
                            </a>
                            <a href="{{ route('admin.coupons.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.coupons.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.coupons.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Coupons</span>
                            </a>
                            <a href="{{ route('admin.transactions.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.transactions.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.transactions.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Transactions</span>
                            </a>
                        </div>
                    </div>

                    {{-- 4. SUPPORT --}}
                    <div class="pt-4 pb-1">
                        <p class="px-3 text-[10px] font-bold text-amber-200/80 dark:text-gray-500 uppercase tracking-[0.2em]">Support</p>
                    </div>

                    {{-- Support & Tickets Dropdown --}}
                    @php
                        $isSupportActive = request()->routeIs('admin.tickets.*') || request()->routeIs('admin.ticket-categories.*') || request()->routeIs('admin.ticket-sub-categories.*') || request()->routeIs('admin.website-queries.*') || request()->routeIs('faqs.*');
                    @endphp
                    <div x-data="{ open: {{ $isSupportActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg {{ $isSupportActive ? 'text-white bg-amber-700/60 dark:bg-amber-500/10 dark:text-amber-400 font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                            <div class="flex items-center min-w-0">
                                <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-headset"></i></span>
                                <span class="truncate">Support &amp; Help</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] ml-2 transition-transform duration-200 opacity-70" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-3 ml-5 border-l border-amber-400/30 dark:border-white/10">
                            <a href="{{ route('admin.tickets.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.tickets.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.tickets.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">User Tickets</span>
                            </a>
                            <a href="{{ route('admin.ticket-categories.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.ticket-categories.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.ticket-categories.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Categories</span>
                            </a>
                            <a href="{{ route('admin.ticket-sub-categories.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.ticket-sub-categories.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.ticket-sub-categories.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Sub-Categories</span>
                            </a>
                            <a href="{{ route('admin.website-queries.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.website-queries.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.website-queries.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Website Queries</span>
                            </a>
                            <a href="{{ route('faqs.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('faqs.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('faqs.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">FAQs</span>
                            </a>
                        </div>
                    </div>

                    {{-- 5. SYSTEM & DEVELOPER --}}
                    <div class="pt-4 pb-1">
                        <p class="px-3 text-[10px] font-bold text-amber-200/80 dark:text-gray-500 uppercase tracking-[0.2em]">Developer &amp; System</p>
                    </div>

                    {{-- Developer & System Tools Dropdown --}}
                    @php
                        $isSystemActive = request()->routeIs('admin.api-tester.*') || request()->routeIs('admin.crons.*') || request()->routeIs('admin.logs');
                    @endphp
                    <div x-data="{ open: {{ $isSystemActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg {{ $isSystemActive ? 'text-white bg-amber-700/60 dark:bg-amber-500/10 dark:text-amber-400 font-semibold' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500/80 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                            <div class="flex items-center min-w-0">
                                <span class="w-5 text-center mr-3 flex-shrink-0 text-sm"><i class="fas fa-terminal"></i></span>
                                <span class="truncate">System Tools</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] ml-2 transition-transform duration-200 opacity-70" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-3 ml-5 border-l border-amber-400/30 dark:border-white/10">
                            <a href="{{ route('admin.api-tester.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.api-tester.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.api-tester.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">API Tester</span>
                            </a>
                            <a href="{{ route('admin.crons.index') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.crons.*') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.crons.*') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Cron Jobs</span>
                            </a>
                            <a href="{{ route('admin.logs') }}" class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('admin.logs') ? 'bg-amber-700 dark:bg-amber-600/25 text-white dark:text-amber-400 font-semibold' : 'text-amber-100/80 dark:text-gray-400 hover:text-white dark:hover:text-white hover:bg-amber-500/50 dark:hover:bg-white/5' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full mr-2 {{ request()->routeIs('admin.logs') ? 'bg-amber-300 dark:bg-amber-400' : 'bg-current opacity-40' }}"></span>
                                <span class="truncate">Server Logs</span>
                            </a>
                        </div>
                    </div>

                    @endif
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 bg-gray-50 dark:bg-[#0b1120] transition-colors duration-500">
                <!-- Top Header -->
                <header class="bg-white dark:bg-[#0f172a] border-b border-gray-200 dark:border-white/5 h-16 flex items-center justify-between px-4 md:px-8 shadow-sm transition-all duration-300 sticky top-0 z-40">
                    <div class="flex items-center">
                        <button @click="sidebarOpen = true" class="md:hidden text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mr-4 focus:outline-none">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-lg font-bold text-gray-800 dark:text-white tracking-tight">
                            @yield('header')
                        </h2>
                    </div>
                    <div class="flex items-center space-x-6">
                        <!-- Dark Mode Toggle -->
                        <button id="theme-toggle" type="button" class="group text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-white/5 rounded-xl text-sm p-2.5 transition-all">
                            <i id="theme-toggle-dark-icon" class="hidden fas fa-moon text-lg group-hover:rotate-12 transition-transform"></i>
                            <i id="theme-toggle-light-icon" class="hidden fas fa-sun text-lg text-amber-400 group-hover:rotate-45 transition-transform"></i>
                        </button>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-sm font-bold text-gray-700 dark:text-gray-200 hover:text-amber-600 dark:hover:text-amber-500 transition-colors focus:outline-none bg-gray-100 dark:bg-white/5 py-1.5 px-3 rounded-lg border border-transparent dark:border-white/5">
                                <span class="mr-2">{{ auth()->user()->name ?? 'Admin' }}</span>
                                <i class="fas fa-chevron-down text-[10px] transition-transform opacity-60" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <!-- Dropdown -->
                            <div x-show="open" @click.away="open = false" x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-[#1e293b] rounded-xl shadow-2xl py-1.5 ring-1 ring-black ring-opacity-5 z-50 border dark:border-white/10 backdrop-blur-xl">
                                <a href="{{ route('profile.index') }}" class="block px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors">
                                    <i class="fas fa-user-circle mr-2 opacity-60"></i> Profile
                                </a>
                                <div class="my-1.5 border-t border-gray-100 dark:border-white/5"></div>
                                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                                    <i class="fas fa-sign-out-alt mr-2 opacity-60"></i> Logout
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 md:p-8 overflow-y-auto">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    @stack('scripts')
    <script>
        $(document).ready(function() {
            $(document).on('page.dt', '.dataTable', function() {
                $('html, body').animate({
                    scrollTop: 0
                }, 800);
            });

            // Dark Mode Toggle Logic
            const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
            const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

            // Change the icons inside the button based on previous settings
            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                themeToggleLightIcon.classList.remove('hidden');
            } else {
                themeToggleDarkIcon.classList.remove('hidden');
            }

            const themeToggleBtn = document.getElementById('theme-toggle');

            themeToggleBtn.addEventListener('click', function() {
                // toggle icons inside button
                themeToggleDarkIcon.classList.toggle('hidden');
                themeToggleLightIcon.classList.toggle('hidden');

                // if set via local storage previously
                if (localStorage.getItem('color-theme')) {
                    if (localStorage.getItem('color-theme') === 'light') {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('color-theme', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('color-theme', 'light');
                    }

                // if NOT set via local storage previously
                } else {
                    if (document.documentElement.classList.contains('dark')) {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('color-theme', 'light');
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('color-theme', 'dark');
                    }
                } 
            });
        });
    </script>
    <!-- Global Delete Confirmation Modal -->
    <div id="global-delete-modal" tabindex="-1" class="fixed inset-0 z-[100] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex flex-col items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/40 dark:bg-black/80 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeDeleteModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block relative align-bottom bg-white dark:bg-[#161e2d] rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-white/5 ring-1 ring-black/5 dark:ring-white/10 mb-8 mt-[15vh]">
                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100/50 dark:bg-red-500/10 sm:mx-0 sm:h-10 sm:w-10 ring-4 ring-red-50 dark:ring-red-500/5">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-500"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-black text-gray-900 dark:text-white uppercase tracking-tight" id="modal-title">Confirm Deletion</h3>
                            <div class="mt-2 text-left">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed" id="delete-modal-message">
                                    Are you sure you want to delete this record? This action cannot be undone.
                                </p>
                                <p class="text-[10px] font-bold text-red-600 dark:text-red-500 mt-4 pt-3 border-t border-red-100 dark:border-white/5 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-shield-alt mr-1.5 opacity-70"></i> Requires Admin Confirmation
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50/80 dark:bg-[#0f172a]/50 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 dark:border-white/5">
                    <button type="button" id="confirm-delete-btn" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-red-600 dark:bg-red-500 text-sm font-bold text-white hover:bg-red-700 dark:hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-[#161e2d] sm:ml-3 sm:w-auto transition-all active:scale-[0.98]">
                        <i class="fas fa-trash-alt mr-2 opacity-80"></i> Yes, Delete
                    </button>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 dark:border-white/10 shadow-sm px-5 py-2.5 bg-white dark:bg-transparent text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 dark:focus:ring-offset-[#161e2d] sm:mt-0 sm:ml-3 sm:w-auto transition-all active:scale-[0.98]">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let _deleteCallback = null;
        let _deleteFormElement = null;
        
        function openDeleteModal(message, target) {
            document.getElementById('delete-modal-message').innerText = message;
            document.getElementById('global-delete-modal').classList.remove('hidden');
            
            if (typeof target === 'function') {
                _deleteCallback = target;
                _deleteFormElement = null;
            } else {
                _deleteFormElement = target;
                _deleteCallback = null;
            }
        }
        
        function closeDeleteModal() {
            document.getElementById('global-delete-modal').classList.add('hidden');
            _deleteCallback = null;
            _deleteFormElement = null;
        }
        
        document.getElementById('confirm-delete-btn').addEventListener('click', function() {
            if (_deleteCallback) {
                _deleteCallback();
            } else if (_deleteFormElement) {
                _deleteFormElement.submit();
            }
            closeDeleteModal();
        });
    
        // Intercept standard confirm-based forms
        $(document).on('submit', 'form.delete-form', function(e) {
            if (!$(this).data('confirmed')) {
                e.preventDefault();
                let form = $(this);
                let message = form.data('confirm-message') || 'Are you sure you want to delete this record?';
                
                openDeleteModal(message, function() {
                    form.data('confirmed', true);
                    form.submit();
                });
            }
        });
    </script>

    @yield('modals')
</body>
</html>
