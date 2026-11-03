<dl class="grid min-w-0 gap-5 text-sm sm:grid-cols-2">
    @foreach($fields as $key => $value)
        <div class="min-w-0 {{ is_array($value) ? 'sm:col-span-2' : '' }}">
            <dt class="font-semibold text-gray-600 dark:text-gray-400">{{ is_numeric($key) ? 'Item '.($key + 1) : \Illuminate\Support\Str::headline($key) }}</dt>
            <dd class="mt-1 break-words text-gray-900 dark:text-gray-200">
                @if(is_array($value) && $value !== [])<div class="mt-3 border-l border-gray-200 pl-4 dark:border-gray-700">@include('admin.amcs.fields', ['fields' => $value])</div>
                @elseif($value === null || $value === '' || $value === [])<span class="text-gray-600 dark:text-gray-400">Not available</span>
                @elseif(is_bool($value)){{ $value ? 'Yes' : 'No' }}
                @else{{ $value }}@endif
            </dd>
        </div>
    @endforeach
</dl>
