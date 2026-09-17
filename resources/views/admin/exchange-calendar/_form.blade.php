@php
    $editing = isset($event);
    $inputClass = 'appearance-none block w-full px-4 py-3 border border-gray-300 dark:border-white/10 rounded-xl shadow-sm bg-white dark:bg-slate-950 text-gray-900 dark:text-white focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm';
    $labelClass = 'block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="exchange" class="{{ $labelClass }}">Exchange</label>
        <select id="exchange" name="exchange" required class="{{ $inputClass }}">
            @foreach(['NSE_BSE' => 'NSE & BSE', 'NSE' => 'NSE only', 'BSE' => 'BSE only'] as $exchange => $label)
                <option value="{{ $exchange }}" @selected(old('exchange', $event->exchange ?? 'NSE_BSE') === $exchange)>{{ $label }}</option>
            @endforeach
        </select>
        @error('exchange') <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="segment" class="{{ $labelClass }}">Segment</label>
        <select id="segment" name="segment" required class="{{ $inputClass }}">
            <option value="equity" @selected(old('segment', $event->segment ?? 'equity') === 'equity')>Equity</option>
        </select>
        @error('segment') <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="event_date" class="{{ $labelClass }}">Date</label>
        <input id="event_date" name="event_date" type="date" required
            value="{{ old('event_date', isset($event) ? $event->event_date->toDateString() : '') }}" class="{{ $inputClass }}">
        @error('event_date') <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="event_type" class="{{ $labelClass }}">Event type</label>
        <select id="event_type" name="event_type" required class="{{ $inputClass }}">
            <option value="holiday" @selected(old('event_type', $event->event_type ?? 'holiday') === 'holiday')>Full-day holiday</option>
            <option value="muhurat" @selected(old('event_type', $event->event_type ?? '') === 'muhurat')>Muhurat trading</option>
            <option value="weekend" @selected(old('event_type', $event->event_type ?? '') === 'weekend')>Weekend</option>
        </select>
        @error('event_type') <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="name" class="{{ $labelClass }}">Name</label>
        <input id="name" name="name" type="text" maxlength="255" required
            value="{{ old('name', $event->name ?? '') }}" placeholder="e.g. Republic Day" class="{{ $inputClass }}">
        @error('name') <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="session_start" class="{{ $labelClass }}">Session starts <span class="normal-case tracking-normal font-medium">(optional)</span></label>
        <input id="session_start" name="session_start" type="time"
            value="{{ old('session_start', isset($event) && $event->session_start ? substr($event->session_start, 0, 5) : '') }}" class="{{ $inputClass }}">
        @error('session_start') <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="session_end" class="{{ $labelClass }}">Session ends <span class="normal-case tracking-normal font-medium">(optional)</span></label>
        <input id="session_end" name="session_end" type="time"
            value="{{ old('session_end', isset($event) && $event->session_end ? substr($event->session_end, 0, 5) : '') }}" class="{{ $inputClass }}">
        @error('session_end') <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
    </div>
</div>

@if($editing && !$event->is_manually_overridden)
    <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
        Saving changes converts this synchronized record into a manual override, so future exchange syncs will preserve the edit.
    </div>
@endif

<div class="mt-8 flex flex-col-reverse sm:flex-row justify-end gap-3">
    <a href="{{ route('admin.exchange-calendar.index') }}" class="inline-flex justify-center px-6 py-3 rounded-xl border border-gray-300 dark:border-white/10 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">Cancel</a>
    <button type="submit" class="inline-flex justify-center items-center px-7 py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-black shadow-lg transition-colors">
        <i class="fas fa-save mr-2"></i>{{ $editing ? 'Save Changes' : 'Create Event' }}
    </button>
</div>
