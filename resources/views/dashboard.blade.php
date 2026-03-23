@extends('layouts.app')

@section('header', 'Dashboard')

@section('content')
@if(auth()->user()->is_admin)
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-900">Welcome to the Admin Dashboard!</h3>
        <p class="text-sm text-gray-500 mt-1">Here is a quick overview of your system.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Quick Stat Card 1 -->
        <div class="bg-amber-50 rounded-lg p-5 border border-amber-100 flex items-center">
            <div class="h-12 w-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 mr-4">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Users</p>
                <h4 class="text-2xl font-bold text-gray-900">{{ \App\Models\User::count() }}</h4>
            </div>
        </div>
        
        <!-- Quick Action Card -->
        <div class="bg-green-50 rounded-lg p-5 border border-green-100 flex items-center">
            <div class="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 mr-4">
                <i class="fas fa-user-plus text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Quick Action</p>
                <a href="{{ route('user.create') }}" class="text-green-700 font-bold hover:underline mt-1 inline-block">Add New User <i class="fas fa-arrow-right text-xs ml-1"></i></a>
            </div>
        </div>

        <!-- System Info Card -->
        <div class="bg-blue-50 rounded-lg p-5 border border-blue-100 flex items-center">
            <div class="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 mr-4">
                <i class="fas fa-server text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">System Status</p>
                <h4 class="text-lg font-bold text-blue-800 mt-1">Operational</h4>
            </div>
        </div>
    </div>
</div>
@else
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center max-w-4xl mx-auto mt-10">
    <div class="inline-flex justify-center items-center w-20 h-20 rounded-full bg-amber-50 text-amber-600 mb-6">
        <i class="fas fa-rocket text-3xl"></i>
    </div>
    <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">Welcome, {{ auth()->user()->first_name ?? auth()->user()->name }}!</h3>
    <p class="text-lg text-gray-500 mt-4 max-w-2xl mx-auto font-medium">Your profile is completely set up. To get started with integrating our global location data APIs into your application, choose a plan below.</p>
    
    <div class="mt-10 p-8 bg-gray-50 border border-gray-200 rounded-2xl flex flex-col md:flex-row items-center justify-between text-left gap-6">
        <div>
            <h4 class="text-xl font-bold text-gray-900">Current Subscription</h4>
            <p class="text-gray-500 mt-1 font-medium">You are currently on the Free Tier.</p>
        </div>
        <a href="{{ route('pricing') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 shadow-md transition-colors whitespace-nowrap">
            Upgrade Plan <i class="fas fa-arrow-up ml-2 mt-0.5"></i>
        </a>
    </div>
</div>
@endif
@endsection
