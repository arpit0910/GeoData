<dl class="grid min-w-0 gap-x-5 gap-y-4 sm:grid-cols-2">
    @foreach($fields as $key => $value)
        <div class="min-w-0 {{ is_array($value) ? 'sm:col-span-2' : '' }}">
            <dt class="text-xs font-semibold text-slate-400">{{ is_numeric($key) ? 'Item '.($key + 1) : \Illuminate\Support\Str::headline($key) }}</dt>
            <dd class="mt-1 break-words text-sm leading-6 text-slate-200">
                @if(is_array($value))
                    <div class="mt-2 border-l border-slate-700 pl-4">@include('website.partials.ipo-fields', ['fields' => $value])</div>
                @elseif(is_bool($value))
                    {{ $value ? 'Yes' : 'No' }}
                @elseif(is_string($value) && preg_match('/^https?:\/\//i', $value) && filter_var($value, FILTER_VALIDATE_URL))
                    <a href="{{ $value }}" target="_blank" rel="noopener noreferrer" class="break-all text-amber-300 underline underline-offset-4 hover:text-amber-200">{{ $value }}<span class="sr-only"> (opens in a new tab)</span></a>
                @elseif($value === null || $value === '')
                    Not provided
                @else
                    {{ $value }}
                @endif
            </dd>
        </div>
    @endforeach
</dl>
