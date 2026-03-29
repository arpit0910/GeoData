@extends('layouts.app')

@section('header', 'API Access Keys')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <div class="bg-white dark:bg-[#161e2d] rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 overflow-hidden transition-all duration-500">
        <div class="px-8 py-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.02]">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight flex items-center">
                <i class="fas fa-shield-alt text-amber-500 mr-3 text-lg"></i> Your Authentication Credentials
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 font-medium leading-relaxed">Use these keys to authenticate your programmatic API requests. These keys provide full access to your account data. Keep the secret key confidential.</p>
        </div>
        
        <div class="p-8 space-y-8 bg-amber-50/10 dark:bg-amber-500/[0.01]">
            <!-- Public Key -->
            <div class="space-y-3">
                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-amber-700/60 dark:text-amber-500/60 ml-1">Client Public Key</label>
                <div class="relative group">
                    <input type="text" readonly value="{{ $user->client_key }}" 
                        class="w-full px-5 py-4 bg-white dark:bg-white/[0.03] border border-amber-200 dark:border-amber-500/20 rounded-2xl text-amber-900 dark:text-amber-200 font-bold font-mono text-sm focus:ring-0 outline-none transition-all pr-12">
                    <button type="button" onclick="copyToClipboard('{{ $user->client_key }}', this)" 
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2.5 text-amber-600 dark:text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-all">
                        <i class="fas fa-copy text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Secret Key -->
            <div class="space-y-3" x-data="{ showSecret: false }">
                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-amber-700/60 dark:text-amber-500/60 ml-1">Client Secret Key</label>
                <div class="relative group">
                    <input :type="showSecret ? 'text' : 'password'" readonly value="{{ $user->client_secret }}" 
                        class="w-full px-5 py-4 bg-white dark:bg-white/[0.03] border border-amber-200 dark:border-amber-500/20 rounded-2xl text-amber-900 dark:text-amber-200 font-bold font-mono text-sm focus:ring-0 outline-none transition-all pr-24"
                        :class="!showSecret && 'tracking-[0.3em]'">
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
                <div class="flex items-start p-4 bg-red-500/5 dark:bg-red-500/10 border border-red-500/10 rounded-2xl">
                    <i class="fas fa-exclamation-triangle text-red-500 mt-1 mr-3 text-xs"></i>
                    <p class="text-[11px] text-red-600/80 dark:text-red-400/80 font-bold leading-relaxed">
                        CRITICAL: This is a highly sensitive credential. Never expose it in client-side code, public repositories, or shared documents. Regenerate it immediately if you suspect it has been compromised.
                    </p>
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
    function copyToClipboard(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const icon = btn.querySelector('i');
            const originalClass = icon.className;
            icon.className = 'fas fa-check text-green-500 scale-125';
            setTimeout(() => {
                icon.className = originalClass;
            }, 2000);
            
            showToast("Copied to clipboard!");
        });
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
