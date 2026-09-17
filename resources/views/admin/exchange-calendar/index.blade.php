@extends('layouts.app')

@section('header', 'Exchange Calendar')

@section('content')
<div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">NSE &amp; BSE Calendar</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">View and manage exchange holidays and Muhurat sessions.</p>
    </div>
    <a href="{{ route('admin.exchange-calendar.create') }}" class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold rounded-xl shadow-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>Add Event
    </a>
</div>

<form method="GET" action="{{ route('admin.exchange-calendar.index') }}" class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-3 bg-white dark:bg-richdark-surface border border-gray-200 dark:border-white/5 rounded-2xl p-5">
    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search event name"
        class="md:col-span-2 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-white/10 bg-white dark:bg-slate-950 text-sm text-gray-900 dark:text-white">
    <select name="exchange" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-white/10 bg-white dark:bg-slate-950 text-sm text-gray-900 dark:text-white">
        <option value="">All exchanges</option>
        @foreach(['NSE_BSE' => 'NSE & BSE', 'NSE' => 'NSE only', 'BSE' => 'BSE only'] as $exchange => $label)
            <option value="{{ $exchange }}" @selected(($filters['exchange'] ?? '') === $exchange)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="year" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-white/10 bg-white dark:bg-slate-950 text-sm text-gray-900 dark:text-white">
        <option value="">All years</option>
        @foreach($years as $year)
            <option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>
        @endforeach
    </select>
    <select name="type" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-white/10 bg-white dark:bg-slate-950 text-sm text-gray-900 dark:text-white">
        <option value="">All event types</option>
        <option value="holiday" @selected(($filters['type'] ?? '') === 'holiday')>Full-day holidays</option>
        <option value="muhurat" @selected(($filters['type'] ?? '') === 'muhurat')>Muhurat sessions</option>
        <option value="weekend" @selected(($filters['type'] ?? '') === 'weekend')>Weekends</option>
    </select>
    <div class="md:col-span-5 flex justify-end gap-3">
        <a href="{{ route('admin.exchange-calendar.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 dark:border-white/10 text-sm font-bold text-gray-600 dark:text-gray-300">Reset</a>
        <button class="px-5 py-2.5 rounded-xl bg-slate-800 dark:bg-amber-600 text-white text-sm font-bold"><i class="fas fa-filter mr-2"></i>Filter</button>
    </div>
</form>

<div class="bg-white dark:bg-richdark-surface rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Date</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Exchange</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Event</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Type / Session</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Source</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @forelse($events as $event)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-bold text-gray-900 dark:text-white">{{ $event->event_date->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $event->event_date->format('l') }}</div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-lg text-xs font-black {{ $event->exchange === 'NSE' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : ($event->exchange === 'BSE' ? 'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300') }}">{{ $event->exchange === 'NSE_BSE' ? 'NSE & BSE' : $event->exchange }}</span></td>
                        <td class="px-6 py-4"><div class="font-bold text-gray-900 dark:text-white">{{ $event->name }}</div><div class="text-xs text-gray-500 uppercase">{{ $event->segment }}</div></td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-bold {{ $event->event_type === 'muhurat' ? 'text-amber-600' : '' }}">{{ $event->event_type === 'muhurat' ? 'Muhurat trading' : ($event->event_type === 'weekend' ? 'Weekend' : 'Holiday') }}</span>
                            @if($event->session_start || $event->session_end)
                                <div class="text-xs text-gray-500">{{ $event->session_start ? substr($event->session_start, 0, 5) : 'TBA' }} – {{ $event->session_end ? substr($event->session_end, 0, 5) : 'TBA' }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wide {{ $event->is_manually_overridden ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' }}">
                                {{ $event->is_manually_overridden ? 'Manual override' : $event->source }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('admin.exchange-calendar.show', $event) }}" title="View" class="p-2 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 hover:bg-blue-600 hover:text-white"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('admin.exchange-calendar.edit', $event) }}" title="Edit" class="p-2 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-600 hover:bg-amber-600 hover:text-white"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('admin.exchange-calendar.destroy', $event) }}" class="inline delete-form" data-confirm-message="Delete '{{ $event->name }}' for {{ $event->exchange }}?">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete" class="p-2 rounded-lg bg-red-50 dark:bg-red-500/10 text-red-600 hover:bg-red-600 hover:text-white"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-14 text-center text-gray-500">No calendar events match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($events->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-white/5">{{ $events->links() }}</div>
    @endif
</div>
@endsection
