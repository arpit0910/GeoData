<div class="flex min-w-[150px] flex-col gap-2">
    <a href="{{ route('admin.technology-news.show', $article->id) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-center text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">View Details</a>
    <form method="POST" action="{{ route('admin.technology-news.regenerate', $article->id) }}" x-data="{ busy: false }" @submit="busy = true">@csrf<button class="w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700 disabled:opacity-50" @disabled($article->editorial_status === 'processing') :disabled="busy || {{ $article->editorial_status === 'processing' ? 'true' : 'false' }}"><span x-text="busy ? 'Generating…' : '{{ $article->rewritten_at ? 'Regenerate' : 'Generate' }}'">{{ $article->rewritten_at ? 'Regenerate' : 'Generate' }}</span></button></form>
    @if($article->editorial_status === 'published')
        <form method="POST" action="{{ route('admin.technology-news.articles.update', $article->id) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $article->status === 'hidden' ? 'published' : 'hidden' }}"><button class="w-full rounded-lg bg-gray-700 px-3 py-2 text-xs font-bold text-white hover:bg-gray-800">{{ $article->status === 'hidden' ? 'Publish' : 'Unpublish' }}</button></form>
    @endif
</div>
