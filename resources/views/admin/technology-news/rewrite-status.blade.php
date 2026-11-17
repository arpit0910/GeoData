<div class="mb-4 rounded-lg border border-gray-300 p-4 dark:border-gray-700">
    <div class="flex flex-wrap items-center justify-between gap-3"><span class="text-sm font-semibold">AI workflow: {{ ucfirst($article->editorial_status) }}</span><span class="text-xs text-gray-600 dark:text-gray-300">{{ $article->rewritten_at ? 'Last rewritten '.$article->rewritten_at.' UTC' : 'Awaiting verified rewrite' }}</span></div>
    @if($article->ai_title)<p class="mt-3 font-bold">{{ $article->ai_title }}</p><p class="mt-2 text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $article->ai_summary }}</p>@endif
    @if($article->rewrite_error)<p class="mt-3 text-sm text-red-700 dark:text-red-300">{{ $article->rewrite_error }} @if($article->rewrite_retry_at)Retry after {{ $article->rewrite_retry_at }} UTC.@endif</p>@endif
    <p class="mt-3 text-xs text-gray-600 dark:text-gray-300">Successful rewrites are fact-checked and automatically approved. Hidden articles remain hidden.</p>
</div>
