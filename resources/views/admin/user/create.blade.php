@extends('layouts.app')

@section('header', 'Create User')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-gray-900">User Information</h3>
                <p class="text-sm text-gray-500 mt-1">Complete the details below to register a new user.</p>
            </div>
            <a href="{{ route('user.list') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg shadow-sm transition-colors">
                <i class="fas fa-arrow-left mr-2 text-gray-500"></i> Back
            </a>
        </div>

        <div class="p-8">
            <form method="POST" action="{{ route('user.store') }}" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-semibold text-gray-700">Full Name</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" name="name" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" placeholder="John Doe" required>
                        </div>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-semibold text-gray-700">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" name="email" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" placeholder="john@example.com" required>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="password" id="password" class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" placeholder="••••••••" required>
                            <button type="button" onclick="togglePasswordVisibility('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="toggle-password-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Confirm Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" placeholder="••••••••" required>
                            <button type="button" onclick="togglePasswordVisibility('password_confirmation')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="toggle-password_confirmation-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Company Name</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-building"></i>
                            </span>
                            <input type="text" name="company_name" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" placeholder="Acme Inc.">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Company Website</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-globe"></i>
                            </span>
                            <input type="url" name="company_website" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" placeholder="https://example.com">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">GST Number</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </span>
                            <input type="text" name="gst_number" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" placeholder="22AAAAA0000A1Z5">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all appearance-none bg-white">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                    <a href="{{ route('user.list') }}" class="px-6 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-700 shadow-sm transition-all transform hover:scale-105">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById('toggle-' + inputId + '-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
@endpush
@endsection
