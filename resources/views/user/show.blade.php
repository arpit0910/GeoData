@extends('layouts.app')

@section('header', 'User Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Profile Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="h-32 bg-gradient-to-r from-amber-600 to-orange-600"></div>
        <div class="px-8 pb-8">
            <div class="relative flex justify-between items-end -mt-12">
                <div class="h-24 w-24 bg-white p-2 rounded-2xl shadow-md">
                    <div class="h-full w-full bg-amber-100 rounded-xl flex items-center justify-center text-amber-700 text-3xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
                <div class="flex space-x-3 pb-2">
                    <a href="{{ route('user.edit', $user->id) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                        <i class="fas fa-edit mr-2 text-amber-500"></i> Edit Profile
                    </a>
                    <a href="{{ route('user.list') }}" class="px-4 py-2 bg-amber-600 text-white font-semibold rounded-lg hover:bg-amber-700 transition-colors shadow-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="mt-6">
                <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
                <p class="text-gray-500 flex items-center mt-1">
                    <i class="fas fa-envelope mr-2"></i> {{ $user->email }}
                    <span class="mx-3 text-gray-300">|</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $user->status }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Main Info -->
        <div class="md:col-span-2 space-y-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-info-circle mr-3 text-amber-500"></i> Account Overview
                </h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-8">
                    <div>
                        <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider">Company Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $user->company_name ?? 'Not Specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider">Account Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-medium capitalize">{{ $user->account_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider">Member Since</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $user->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-gray-400 uppercase tracking-wider">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $user->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </div>

            @if($user->account_type === 'client')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-key mr-3 text-amber-500"></i> API Access Keys
                </h3>
                <div class="space-y-4">
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <label class="text-[10px] uppercase font-bold text-gray-400 block mb-2">Client Key</label>
                        <div class="flex">
                            <code class="flex-1 bg-white border border-gray-200 px-3 py-2 rounded-l-md text-amber-600 font-mono text-sm break-all">
                                {{ $user->client_key }}
                            </code>
                            <button onclick="copyToClipboard('{{ $user->client_key }}')" class="bg-white border border-l-0 border-gray-200 px-4 py-2 rounded-r-md hover:bg-gray-50 text-gray-500 hover:text-amber-600 transition-colors shadow-sm">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 mt-4">
                        <label class="text-[10px] uppercase font-bold text-gray-400 block mb-2">Client Secret</label>
                        <div class="flex">
                            <code class="flex-1 bg-white border border-gray-200 px-3 py-2 rounded-l-md text-slate-800 font-mono text-sm break-all">
                                {{ $user->client_secret }}
                            </code>
                            <button onclick="copyToClipboard('{{ $user->client_secret }}')" class="bg-white border border-l-0 border-gray-200 px-4 py-2 rounded-r-md hover:bg-gray-50 text-gray-500 hover:text-slate-800 transition-colors shadow-sm">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar Widgets -->
        <div class="space-y-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider">Active Token</h3>
                @if($user->active_access_token)
                    <div class="flex items-center text-green-600 mb-2">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="text-xs font-bold uppercase">Token Available</span>
                    </div>
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Expires At</p>
                    <p class="text-sm font-medium text-gray-700 mt-1">{{ $user->token_expires_at->format('M d, Y H:i') }}</p>
                @else
                    <div class="flex items-center text-gray-400">
                        <i class="fas fa-times-circle mr-2"></i>
                        <span class="text-xs font-bold uppercase">No Active Token</span>
                    </div>
                @endif
            </div>

            <div class="bg-amber-900 rounded-xl shadow-md p-6 text-white relative overflow-hidden">
                <i class="fas fa-shield-alt absolute -right-4 -bottom-4 text-7xl text-amber-800 opacity-50 transform -rotate-12"></i>
                <h3 class="text-sm font-bold uppercase tracking-wider mb-2 relative z-10">Quick Action</h3>
                <p class="text-amber-200 text-xs mb-4 relative z-10">Perform security actions on this account.</p>
                <button class="w-full py-2 bg-amber-700 hover:bg-amber-600 rounded-lg text-xs font-bold uppercase tracking-widest transition-colors relative z-10">
                    Regenerate Keys
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Key copied to clipboard!');
    });
}
</script>
@endsection
