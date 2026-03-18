<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GeoData') }} - Admin</title>

    <!-- Tailwind CSS via CDN for instant visual improvement -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js for interactivity -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables with Tailwind -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .dataTables_wrapper .dataTables_length select { padding-right: 2rem; border-radius: 0.375rem; border-color: #d1d5db; }
        .dataTables_wrapper .dataTables_filter input { padding: 0.5rem 0.75rem; border-radius: 0.375rem; border-color: #d1d5db; border-width: 1px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #d97706 !important; color: white !important; border: none; border-radius: 0.375rem; }
        table.dataTable { border-collapse: collapse !important; border-spacing: 0; width: 100% !important; margin-top: 1rem !important; margin-bottom: 1rem !important; }
        table.dataTable thead th { background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #374151; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 0.75rem 1.5rem; }
        table.dataTable tbody td { padding: 1rem 1.5rem; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 0.875rem; }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full flex flex-col">
        <!-- Sidebar and Content wrapper -->
        <div class="flex flex-1">
            <!-- Navigation -->
            <nav class="w-64 bg-amber-600 text-white flex-shrink-0 hidden md:block">
                <div class="p-6">
                    <h1 class="text-2xl font-bold tracking-tight">GeoData Admin</h1>
                </div>
                <div class="mt-4 px-4 space-y-1">
                    <a href="{{ route('user.list') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('user.*') ? 'bg-amber-700 text-white' : 'text-amber-100 hover:bg-amber-500 hover:text-white' }} transition-colors">
                        <i class="fas fa-users mr-3 w-5"></i>
                        Users
                    </a>
                    <a href="{{ route('countries.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('countries.*') ? 'bg-amber-700 text-white' : 'text-amber-100 hover:bg-amber-500 hover:text-white' }} transition-colors mt-2">
                        <i class="fas fa-globe mr-3 w-5"></i>
                        Countries
                    </a>
                    <a href="{{ route('states.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('states.*') ? 'bg-amber-700 text-white' : 'text-amber-100 hover:bg-amber-500 hover:text-white' }} transition-colors mt-2">
                        <i class="fas fa-layer-group mr-3 w-5"></i>
                        States
                    </a>
                    <a href="{{ route('cities.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('cities.*') ? 'bg-amber-700 text-white' : 'text-amber-100 hover:bg-amber-500 hover:text-white' }} transition-colors mt-2">
                        <i class="fas fa-building mr-3 w-5"></i>
                        Cities
                    </a>
                    <a href="{{ route('regions.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('regions.*') ? 'bg-amber-700 text-white' : 'text-amber-100 hover:bg-amber-500 hover:text-white' }} transition-colors mt-2">
                        <i class="fas fa-map-marked-alt mr-3 w-5"></i>
                        Regions
                    </a>
                    <a href="{{ route('subregions.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('subregions.*') ? 'bg-amber-700 text-white' : 'text-amber-100 hover:bg-amber-500 hover:text-white' }} transition-colors mt-2">
                        <i class="fas fa-map-pin mr-3 w-5"></i>
                        Sub Regions
                    </a>
                    <a href="{{ route('timezones.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('timezones.*') ? 'bg-amber-700 text-white' : 'text-amber-100 hover:bg-amber-500 hover:text-white' }} transition-colors mt-2">
                        <i class="fas fa-clock mr-3 w-5"></i>
                        Timezones
                    </a>
                    <!-- Add more navigation items here -->
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 bg-gray-50">
                <!-- Top Header -->
                <header class="bg-white border-bottom border-gray-200 h-16 flex items-center justify-between px-8 shadow-sm">
                    <div class="flex items-center">
                        <button class="md:hidden text-gray-500 hover:text-gray-700 mr-4">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-lg font-semibold text-gray-800">
                            @yield('header')
                        </h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-700 hover:text-amber-600 transition-colors focus:outline-none">
                                <span class="mr-2">{{ auth()->user()->name ?? 'Admin' }}</span>
                                <i class="fas fa-chevron-down text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <!-- Dropdown -->
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 z-50">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <hr class="my-1 border-gray-100">
                                <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
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
    @stack('scripts')
    <script>
        $(document).ready(function() {
            $(document).on('page.dt', '.dataTable', function() {
                $('html, body').animate({
                    scrollTop: 0
                }, 800);
            });
        });
    </script>
</body>
</html>
