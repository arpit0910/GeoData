@extends('layouts.app')

@section('header', 'API Access Keys')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <div class="bg-white dark:bg-[#161e2d] rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 overflow-hidden transition-all duration-500">
        <div class="px-8 py-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.02]">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight"><i class="fas fa-shield-alt text-amber-500 mr-2"></i> Your Authentication Credentials</h3>
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 font-medium font-medium">Use these keys to authenticate your programmatic API requests. Do not share your secret key with anyone.</p>
        </div>
        
        <div class="p-8 space-y-10">
            <!-- Public Key -->
            <div>
                <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 mb-2 ml-1 uppercase tracking-[0.2em]">Public Client Key</label>
                <div class="flex items-center group/key">
                    <div class="relative flex-grow">
                        <input type="text" readonly id="publicKey" value="{{ $user->client_key }}" 
                            class="appearance-none block w-full px-5 py-4 bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-l-2xl text-gray-600 dark:text-gray-400 font-mono text-sm focus:outline-none focus:ring-0 cursor-text transition-all group-hover/key:border-amber-500/30">
                    </div>
                    <button type="button" onclick="copyToClipboard('publicKey')" class="inline-flex items-center px-6 py-4 border border-l-0 border-gray-200 dark:border-white/10 rounded-r-2xl bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-300 font-bold hover:bg-amber-600 dark:hover:bg-amber-600 hover:text-white dark:hover:text-white focus:outline-none transition-all transition-all duration-200">
                        <i class="far fa-copy mr-2"></i> Copy
                    </button>
                </div>
            </div>

            <!-- Secret Key -->
            <div>
                <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 mb-2 ml-1 uppercase tracking-[0.2em]">Secret Client Key</label>
                <div class="flex items-center group/key">
                    <div class="relative flex-grow">
                        <input type="password" readonly id="secretKey" value="{{ $user->client_secret }}" 
                            class="appearance-none block w-full px-5 py-4 bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-l-2xl text-gray-800 dark:text-gray-200 font-mono text-sm focus:outline-none focus:ring-0 cursor-text tracking-widest transition-all group-hover/key:border-amber-500/30">
                        
                        <button type="button" onclick="toggleVisibility('secretKey')" class="absolute inset-y-0 right-4 flex items-center cursor-pointer text-gray-400 hover:text-amber-500 focus:outline-none transition-colors">
                            <i class="fas fa-eye" id="secretKeyIcon"></i>
                        </button>
                    </div>
                    <button type="button" onclick="copyToClipboard('secretKey')" class="inline-flex items-center px-6 py-4 border border-l-0 border-gray-200 dark:border-white/10 rounded-r-2xl bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-300 font-bold hover:bg-amber-600 dark:hover:bg-amber-600 hover:text-white dark:hover:text-white focus:outline-none transition-all transition-all duration-200">
                        <i class="far fa-copy mr-2"></i> Copy
                    </button>
                </div>
                <div class="mt-4 flex items-start p-3 bg-red-500/5 dark:bg-red-500/10 border border-red-500/20 rounded-xl">
                    <i class="fas fa-exclamation-triangle text-red-500 mt-0.5 mr-3"></i>
                    <p class="text-xs text-red-600 dark:text-red-400 font-bold">WARNING: This is highly sensitive. Regenerate it immediately if you suspect it has been compromised.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-5 right-5 transform transition-all duration-300 translate-y-12 opacity-0 z-50">
    <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3">
        <i class="fas fa-check-circle text-green-400"></i>
        <span id="toastMessage" class="text-sm font-medium">Copied to clipboard!</span>
    </div>
</div>

@push('scripts')
<script>
    function copyToClipboard(elementId) {
        const copyText = document.getElementById(elementId);
        
        const isPassword = copyText.type === 'password';
        if(isPassword) copyText.type = 'text';
        
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        
        navigator.clipboard.writeText(copyText.value).then(() => {
            showToast("Copied to clipboard!");
        });
        
        if(isPassword) copyText.type = 'password';
    }

    function toggleVisibility(elementId) {
        const input = document.getElementById(elementId);
        const icon = document.getElementById(elementId + 'Icon');
        
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function showToast(message) {
        const toast = document.getElementById('toast');
        document.getElementById('toastMessage').innerText = message;
        
        toast.classList.remove('translate-y-12', 'opacity-0');
        
        setTimeout(() => {
            toast.classList.add('translate-y-12', 'opacity-0');
        }, 3000);
    }
</script>
@endpush
@endsection
