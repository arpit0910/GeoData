@extends('layouts.app')

@section('header', 'API Access Keys')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
            <h3 class="text-lg font-semibold text-gray-900"><i class="fas fa-shield-alt text-amber-500 mr-2"></i> Your Authentication Credentials</h3>
            <p class="mt-1 text-sm text-gray-500">Use these keys to authenticate your programmatic API requests. Do not share your secret key with anyone.</p>
        </div>
        
        <div class="p-6 space-y-8">
            <!-- Public Key -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Public Client Key</label>
                <div class="flex items-center">
                    <div class="relative flex-grow">
                        <input type="text" readonly id="publicKey" value="{{ $user->client_key }}" 
                            class="appearance-none block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-l-lg text-gray-600 font-mono text-sm focus:outline-none focus:ring-0 cursor-text">
                    </div>
                    <button type="button" onclick="copyToClipboard('publicKey')" class="inline-flex items-center px-4 py-3 border border-l-0 border-gray-300 rounded-r-lg bg-gray-100 text-gray-700 hover:bg-gray-200 focus:outline-none transition-colors ml-[-1px]">
                        <i class="far fa-copy mr-2"></i> Copy
                    </button>
                </div>
            </div>

            <!-- Secret Key -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Secret Client Key</label>
                <div class="flex items-center">
                    <div class="relative flex-grow">
                        <input type="password" readonly id="secretKey" value="{{ $user->client_secret }}" 
                            class="appearance-none block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-l-lg text-gray-800 font-mono text-sm focus:outline-none focus:ring-0 cursor-text tracking-widest">
                        
                        <button type="button" onclick="toggleVisibility('secretKey')" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                            <i class="fas fa-eye" id="secretKeyIcon"></i>
                        </button>
                    </div>
                    <button type="button" onclick="copyToClipboard('secretKey')" class="inline-flex items-center px-4 py-3 border border-l-0 border-gray-300 rounded-r-lg bg-gray-100 text-gray-700 hover:bg-gray-200 focus:outline-none transition-colors ml-[-1px]">
                        <i class="far fa-copy mr-2"></i> Copy
                    </button>
                </div>
                <p class="mt-2 text-xs text-red-500"><i class="fas fa-exclamation-triangle mr-1"></i> Warning: This is highly sensitive. Keep this secret safe.</p>
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
