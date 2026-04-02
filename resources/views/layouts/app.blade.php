<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') | {{ config('app.name', 'SetuGeo') }}</title>

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
                        <img src="{{ asset('assets/img/logo.png') }}" alt="SetuGeo Logo" class="h-20 w-auto">
                    </a>
                </div>
                <div class="mt-4 px-4 pb-8 space-y-1.5">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <i class="fas fa-home mr-3 w-5"></i>
                        Dashboard
                    </a>
                    
                    <a href="{{ route('profile.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('profile.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-user-circle mr-3 w-5"></i>
                        Profile
                    </a>
                    
                    @if(auth()->check() && !auth()->user()->is_admin)
                    <a href="{{ route('api-keys.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('api-keys.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-key mr-3 w-5"></i>
                        API Keys
                    </a>

                    <a href="{{ route('api-logs.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('api-logs.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-history mr-3 w-5"></i>
                        API Logs
                    </a>

                    <a href="{{ route('transactions.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('transactions.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-file-invoice mr-3 w-5"></i>
                        Transactions
                    </a>

                    <a href="{{ route('support.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('support.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-headset mr-3 w-5"></i>
                        Help & Support
                    </a>
                    @endif
                    
                    @if(auth()->check() && auth()->user()->is_admin)
                    <div class="pt-6 pb-2">
                        <p class="px-4 text-[10px] font-bold text-amber-200 dark:text-gray-500 uppercase tracking-[0.2em]">Administration</p>
                    </div>
                    <a href="{{ route('user.list') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('user.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <i class="fas fa-users mr-3 w-5"></i>
                        Users
                    </a>
                    <a href="{{ route('countries.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('countries.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-globe mr-3 w-5"></i>
                        Countries
                    </a>
                    <a href="{{ route('states.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('states.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-layer-group mr-3 w-5"></i>
                        States
                    </a>
                    <a href="{{ route('cities.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('cities.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-building mr-3 w-5"></i>
                        Cities
                    </a>
                    <a href="{{ route('regions.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('regions.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-map-marked-alt mr-3 w-5"></i>
                        Regions
                    </a>
                    <a href="{{ route('subregions.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('subregions.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-map-pin mr-3 w-5"></i>
                        Sub Regions
                    </a>
                    <a href="{{ route('timezones.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('timezones.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-clock mr-3 w-5"></i>
                        Timezones
                    </a>
                    <a href="{{ route('pincodes.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('pincodes.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-location-dot mr-3 w-5"></i>
                        Pincodes
                    </a>
                    <a href="{{ route('plans.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('plans.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-credit-card mr-3 w-5"></i>
                        Plans
                    </a>
                    <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('admin.subscriptions.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-file-invoice-dollar mr-3 w-5"></i>
                        Subscriptions
                    </a>
                    <a href="{{ route('admin.coupons.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('admin.coupons.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-ticket-alt mr-3 w-5"></i>
                        Coupons
                    </a>
                    <a href="{{ route('admin.transactions.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('admin.transactions.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-receipt mr-3 w-5"></i>
                        Transactions
                    </a>

                    <div class="pt-6 pb-2">
                        <p class="px-4 text-[10px] font-bold text-amber-200 dark:text-gray-500 uppercase tracking-[0.2em]">Support</p>
                    </div>
                    <a href="{{ route('admin.ticket-categories.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('admin.ticket-categories.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200">
                        <i class="fas fa-tags mr-3 w-5"></i>
                        Categories
                    </a>
                    <a href="{{ route('admin.ticket-sub-categories.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('admin.ticket-sub-categories.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-sitemap mr-3 w-5"></i>
                        Sub-Categories
                    </a>
                    <a href="{{ route('admin.tickets.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('admin.tickets.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-ticket-alt mr-3 w-5"></i>
                        User Tickets
                    </a>
                    <a href="{{ route('faqs.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('faqs.*') ? 'bg-amber-700 dark:bg-amber-600/20 text-white dark:text-amber-500 shadow-sm' : 'text-amber-100 dark:text-gray-400 hover:bg-amber-500 dark:hover:bg-white/5 hover:text-white dark:hover:text-white' }} transition-all duration-200 mt-2">
                        <i class="fas fa-question-circle mr-3 w-5"></i>
                        FAQs
                    </a>
                    <!-- Add more navigation items here -->
                    @endif
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 bg-gray-50 dark:bg-[#0b1120] transition-colors duration-500">
                <!-- Top Header -->
                <header class="bg-white dark:bg-[#0f172a] border-b border-gray-200 dark:border-white/5 h-16 flex items-center justify-between px-8 shadow-sm transition-all duration-300 sticky top-0 z-40">
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
                <main class="flex-1 p-8 overflow-y-auto">
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
