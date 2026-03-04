@extends('layouts.app')

@section('header', 'Dashboard')

@section('content')
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
@endsection
