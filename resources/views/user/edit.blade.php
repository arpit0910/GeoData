@extends('layouts.app')

@section('header', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Update User: {{ $user->name }}</h3>
                <p class="text-sm text-gray-500 mt-1">Modify user details and account settings.</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $user->status }}
                </div>
                <a href="{{ route('user.list') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <i class="fas fa-arrow-left mr-2 text-gray-500"></i> Back
                </a>
            </div>
        </div>

        <div class="p-8">
            <form method="POST" action="{{ route('user.update', $user->id) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-semibold text-gray-700">Full Name</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" name="name" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" value="{{ $user->name }}" placeholder="John Doe" required>
                        </div>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-semibold text-gray-700">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" name="email" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" value="{{ $user->email }}" placeholder="john@example.com" required>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Password <span class="text-xs font-normal text-gray-400">(Leave blank to keep current)</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="password" id="password" class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" placeholder="••••••••">
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
                            <input type="password" name="password_confirmation" id="password_confirmation" class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" placeholder="••••••••">
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
                            <input type="text" name="company_name" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" value="{{ $user->company_name }}" placeholder="Acme Inc.">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Company Website</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-globe"></i>
                            </span>
                            <input type="url" name="company_website" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" value="{{ $user->company_website }}" placeholder="https://example.com">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">GST Number</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </span>
                            <input type="text" name="gst_number" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all" value="{{ $user->gst_number }}" placeholder="22AAAAA0000A1Z5">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all appearance-none bg-white">
                            <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $user->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                @if($user->account_type === 'client')
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-3">
                    <h4 class="text-sm font-bold text-gray-700 flex items-center">
                        <i class="fas fa-key mr-2 text-amber-500"></i> API Credentials
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] uppercase font-bold text-gray-400">Client Key</label>
                            <div class="flex mt-1">
                                <input type="text" readonly value="{{ $user->client_key }}" class="bg-gray-100 border border-gray-300 text-gray-600 text-xs rounded-l-md px-3 py-2 w-full font-mono">
                                <button type="button" onclick="copyToClipboard('{{ $user->client_key }}')" class="bg-white border border-l-0 border-gray-300 px-3 py-2 rounded-r-md hover:bg-gray-50 text-amber-600 transition-colors">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-gray-400">Client Secret</label>
                            <div class="flex mt-1">
                                <input type="text" readonly value="{{ $user->client_secret }}" class="bg-gray-100 border border-gray-300 text-gray-600 text-xs rounded-l-md px-3 py-2 w-full font-mono">
                                <button type="button" onclick="copyToClipboard('{{ $user->client_secret }}')" class="bg-white border border-l-0 border-gray-300 px-3 py-2 rounded-r-md hover:bg-gray-50 text-amber-600 transition-colors">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                    <a href="{{ route('user.list') }}" class="px-6 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-700 shadow-sm transition-all transform hover:scale-105">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}

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
@endsection
