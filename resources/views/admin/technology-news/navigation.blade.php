<nav class="flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold" aria-label="Technology news management">
    @foreach(['admin.technology-news.index' => 'Articles', 'admin.technology-news.sources.index' => 'Sources', 'admin.technology-news.logs' => 'Sync logs', 'admin.technology-news.duplicates.index' => 'Duplicate review'] as $route => $label)
        <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif class="{{ request()->routeIs($route) ? 'text-amber-700 dark:text-amber-400' : 'text-gray-600 dark:text-gray-300' }} hover:underline">{{ $label }}</a>
    @endforeach
</nav>
