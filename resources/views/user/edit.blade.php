@extends('layouts.app')

@section('header', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.02] flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Update User: {{ $user->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">Modify user details and account settings.</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $user->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-500' : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-500' }}">
                    {{ $user->status }}
                </div>
                <a href="{{ route('user.list') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back
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
                <div class="mt-8 p-8 bg-amber-50/50 dark:bg-amber-500/[0.02] rounded-3xl border border-amber-200/50 dark:border-amber-500/10 space-y-6">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-black text-amber-800 dark:text-amber-500 flex items-center uppercase tracking-widest">
                            <i class="fas fa-shield-alt mr-3 text-lg"></i> API Credentials
                        </h4>
                        <span class="px-2.5 py-1 bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 text-[10px] font-black rounded-lg uppercase tracking-tighter">Production Access</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-amber-700/60 dark:text-amber-500/60 mb-2.5 ml-1">Client Public Key</label>
                            <div class="relative group">
                                <input type="text" readonly value="{{ $user->client_key }}" 
                                    class="w-full px-5 py-4 bg-white dark:bg-white/[0.03] border border-amber-200 dark:border-amber-500/20 rounded-2xl text-amber-900 dark:text-amber-200 font-bold font-mono text-xs focus:ring-0 outline-none transition-all pr-12">
                                <button type="button" onclick="copyToClipboard('{{ $user->client_key }}', this)" 
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-2.5 text-amber-600 dark:text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-all">
                                    <i class="fas fa-copy text-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-amber-700/60 dark:text-amber-500/60 mb-2.5 ml-1">Client Secret Key</label>
                            <div class="relative group" x-data="{ showSecret: false }">
                                <input :type="showSecret ? 'text' : 'password'" readonly value="{{ $user->client_secret }}" 
                                    class="w-full px-5 py-4 bg-white dark:bg-white/[0.03] border border-amber-200 dark:border-amber-500/20 rounded-2xl text-amber-900 dark:text-amber-200 font-bold font-mono text-xs focus:ring-0 outline-none transition-all pr-24">
                                <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center space-x-1">
                                    <button type="button" @click="showSecret = !showSecret" 
                                        class="p-2.5 text-amber-600 dark:text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-all">
                                        <i class="fas" :class="showSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                    <button type="button" onclick="copyToClipboard('{{ $user->client_secret }}', this)" 
                                        class="p-2.5 text-amber-600 dark:text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-all">
                                        <i class="fas fa-copy text-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-amber-700/40 dark:text-amber-500/30 uppercase tracking-tight italic flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> Keep the Secret Key hidden and never share it in public repositories.
                    </p>
                </div>
                @endif

                <div class="mt-10 pt-8 border-t border-gray-100 dark:border-white/5 flex justify-end items-center space-x-6">
                    <a href="{{ route('user.list') }}" class="text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                        Save Changes <i class="fas fa-save ml-3 text-sm"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const icon = btn.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'fas fa-check text-green-500 scale-125';
        setTimeout(() => {
            icon.className = originalClass;
        }, 2000);
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
