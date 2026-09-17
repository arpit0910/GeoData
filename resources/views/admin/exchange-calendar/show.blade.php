@extends('layouts.app')

@section('header', 'Exchange Calendar Event')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.exchange-calendar.index') }}" class="text-sm font-bold text-amber-600 hover:text-amber-700"><i class="fas fa-arrow-left mr-2"></i>Back to calendar</a>
            <h1 class="mt-4 text-3xl font-black text-gray-900 dark:text-white">{{ $event->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $event->exchange === 'NSE_BSE' ? 'NSE & BSE' : $event->exchange }} &middot; {{ $event->event_date->format('l, d F Y') }}</p>
        </div>
        <a href="{{ route('admin.exchange-calendar.edit', $event) }}" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold"><i class="fas fa-edit mr-2"></i>Edit</a>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-2xl border border-gray-200 dark:border-white/5 overflow-hidden">
        <dl class="grid grid-cols-1 sm:grid-cols-2">
            @foreach([
                'Exchange' => $event->exchange === 'NSE_BSE' ? 'NSE & BSE' : $event->exchange,
                'Segment' => ucfirst($event->segment),
                'Event type' => $event->event_type === 'muhurat' ? 'Muhurat trading' : ($event->event_type === 'weekend' ? 'Weekend' : 'Full-day holiday'),
                'Date' => $event->event_date->toDateString(),
                'Session starts' => $event->session_start ? substr($event->session_start, 0, 5).' IST' : 'Not specified',
                'Session ends' => $event->session_end ? substr($event->session_end, 0, 5).' IST' : 'Not specified',
                'Source' => $event->source,
                'Manual override' => $event->is_manually_overridden ? 'Yes' : 'No',
                'Last synchronized' => optional($event->synced_at)->timezone('Asia/Kolkata')->format('d M Y, H:i T') ?? 'Never',
            ] as $label => $value)
                <div class="px-6 py-5 border-b border-gray-100 dark:border-white/5 sm:odd:border-r">
                    <dt class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ $label }}</dt>
                    <dd class="mt-2 text-sm font-bold text-gray-900 dark:text-white">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
        <div class="px-6 py-5">
            <div class="text-[10px] font-black uppercase tracking-widest text-gray-500">Source URL</div>
            <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 break-all">{{ $event->source_url }}</div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <form method="POST" action="{{ route('admin.exchange-calendar.destroy', $event) }}" class="delete-form" data-confirm-message="Delete '{{ $event->name }}' for {{ $event->exchange }}?">
            @csrf @method('DELETE')
            <button type="submit" class="inline-flex items-center px-5 py-3 rounded-xl border border-red-200 text-red-600 hover:bg-red-600 hover:text-white text-sm font-bold"><i class="fas fa-trash mr-2"></i>Delete Event</button>
        </form>
    </div>
</div>
@endsection
