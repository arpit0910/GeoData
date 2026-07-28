@extends('layouts.app')

@section('header', 'User Details')

@section('content')
@php
    $isActive = (int) $user->status === 1;
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="mx-auto max-w-7xl space-y-8">
    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
        <div class="bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.28),_transparent_38%),linear-gradient(135deg,_#0f172a,_#1e293b_55%,_#334155)] px-8 py-8 text-white">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="flex items-start gap-5">
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-[26px] border border-white/15 bg-white/10 text-2xl font-black tracking-[0.14em] shadow-2xl backdrop-blur">
                        {{ $initials ?: strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-black uppercase tracking-[0.22em] text-amber-100">
                            {{ ucfirst($user->account_type ?: 'User') }} Account
                        </div>
                        <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ $user->name }}</h1>
                        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-200">
                            <span class="inline-flex items-center">
                                <i class="fas fa-envelope mr-2 text-amber-300"></i>{{ $user->email }}
                            </span>
                            <span class="hidden h-1 w-1 rounded-full bg-white/30 sm:block"></span>
                            <span class="inline-flex items-center">
                                <i class="fas fa-building mr-2 text-amber-300"></i>{{ $user->company_name ?: 'No company name added' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-stretch gap-3">
                    <div class="flex min-h-[56px] min-w-[150px] items-center rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                        <span class="mr-3 inline-flex h-2.5 w-2.5 rounded-full {{ $isActive ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                        <div class="text-left">
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-300">Status</p>
                            <p class="mt-1 text-sm font-bold text-white">{{ $isActive ? 'Active' : 'Inactive' }}</p>
                        </div>
                    </div>
                    <a href="{{ route('user.edit', $user->id) }}" class="inline-flex min-h-[56px] items-center justify-center rounded-2xl border border-white/15 bg-white px-5 py-3 text-sm font-black text-slate-900 transition hover:bg-amber-50">
                        <i class="fas fa-pen-to-square mr-2 text-amber-600"></i>Edit User
                    </a>
                    <a href="{{ route('user.list') }}" class="inline-flex min-h-[56px] items-center justify-center rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/20">
                        <i class="fas fa-arrow-left mr-2"></i>Back To List
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 border-t border-slate-200 bg-slate-50 px-8 py-5 dark:border-white/10 dark:bg-white/[0.03] sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Member Since</p>
                <p class="mt-2 text-base font-black text-slate-900 dark:text-white">{{ optional($user->created_at)->format('d M Y') }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ optional($user->created_at)->diffForHumans() }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Last Updated</p>
                <p class="mt-2 text-base font-black text-slate-900 dark:text-white">{{ optional($user->updated_at)->format('d M Y, h:i A') }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ optional($user->updated_at)->diffForHumans() }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Website</p>
                <p class="mt-2 truncate text-base font-black text-slate-900 dark:text-white">{{ $user->company_website ?: 'Not added' }}</p>
                @if($user->company_website)
                    <a href="{{ $user->company_website }}" target="_blank" rel="noreferrer" class="mt-1 inline-flex items-center text-xs font-bold text-amber-600 transition hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                        Visit site <i class="fas fa-arrow-up-right-from-square ml-2 text-[10px]"></i>
                    </a>
                @endif
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">GST Number</p>
                <p class="mt-2 text-base font-black text-slate-900 dark:text-white">{{ $user->gst_number ?: 'Not added' }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Billing and compliance identifier</p>
            </article>
        </div>
    </section>

    <section class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-slate-950">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Account Overview</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Primary company and account information in one place.</p>
            </div>
            <div class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.2em] text-slate-600 dark:bg-white/10 dark:text-slate-300">
                User Profile
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Full Name</p>
                <p class="mt-2 text-base font-bold text-slate-900 dark:text-white">{{ $user->name }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Email Address</p>
                <p class="mt-2 break-all text-base font-bold text-slate-900 dark:text-white">{{ $user->email }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Company Name</p>
                <p class="mt-2 text-base font-bold text-slate-900 dark:text-white">{{ $user->company_name ?: 'Not specified' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Account Type</p>
                <p class="mt-2 text-base font-bold capitalize text-slate-900 dark:text-white">{{ $user->account_type ?: 'client' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Token Availability</p>
                @if($user->active_access_token)
                    <p class="mt-2 text-sm font-black text-emerald-700 dark:text-emerald-300">Active access token available</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Expires {{ optional($user->token_expires_at)->format('d M Y, h:i A') ?: 'N/A' }}
                    </p>
                @else
                    <p class="mt-2 text-sm font-black text-slate-700 dark:text-slate-200">No active access token</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">A new token will appear here when generated.</p>
                @endif
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">User Status</p>
                <div class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.18em] {{ $isActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' }}">
                    <span class="mr-2 inline-flex h-2 w-2 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    {{ $isActive ? 'Active' : 'Inactive' }}
                </div>
            </article>
        </div>
    </section>

    @if($user->account_type === 'client')
        <section class="rounded-[24px] border border-amber-200 bg-amber-50/60 p-6 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/[0.06]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="inline-flex items-center rounded-full border border-amber-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.22em] text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                        API Credentials
                    </div>
                    <h2 class="mt-4 text-lg font-black text-slate-900 dark:text-white">Production Access Keys</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Keys are displayed in structured cards with quick copy actions so the team can use them safely during demos and onboarding.</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-white/80 px-4 py-3 text-xs font-bold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                    Keep the secret key private
                </div>
            </div>

            <div class="mt-6 grid gap-5">
                <article class="rounded-[22px] border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-500/20 dark:bg-slate-950/80">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Client Key</p>
                            <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 px-4 py-4 dark:border-white/10">
                                <code class="block break-all text-sm leading-7 text-amber-300">{{ $user->client_key }}</code>
                            </div>
                        </div>
                        <button type="button" onclick="copyToClipboard('{{ $user->client_key }}', this)" class="inline-flex shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-white dark:hover:bg-white/[0.08]">
                            <i class="fas fa-copy mr-2"></i>Copy Key
                        </button>
                    </div>
                </article>

                <article class="rounded-[22px] border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-500/20 dark:bg-slate-950/80" x-data="{ showSecret: false }">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Client Secret</p>
                                <button type="button" @click="showSecret = !showSecret" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600 transition hover:bg-slate-200 dark:border-white/10 dark:bg-white/[0.05] dark:text-slate-300 dark:hover:bg-white/[0.08]">
                                    <i class="fas mr-2" :class="showSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    <span x-text="showSecret ? 'Hide Secret' : 'Show Secret'"></span>
                                </button>
                            </div>
                            <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 px-4 py-4 dark:border-white/10">
                                <code class="block break-all text-sm leading-7 text-slate-100" x-text="showSecret ? @js($user->client_secret) : '•'.repeat(Math.max(@js(strlen((string) $user->client_secret)), 24))"></code>
                            </div>
                        </div>
                        <button type="button" onclick="copyToClipboard('{{ $user->client_secret }}', this)" class="inline-flex shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-white dark:hover:bg-white/[0.08]">
                            <i class="fas fa-copy mr-2"></i>Copy Secret
                        </button>
                    </div>
                </article>
            </div>
        </section>
    @endif
</div>

<script>
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(() => {
        const original = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check mr-2 text-emerald-500"></i>Copied';
        button.classList.add('ring-2', 'ring-emerald-200');

        setTimeout(() => {
            button.innerHTML = original;
            button.classList.remove('ring-2', 'ring-emerald-200');
        }, 1800);
    });
}
</script>
@endsection
