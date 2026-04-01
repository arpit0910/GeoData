@extends('layouts.app')

@section('header', 'Manage FAQs')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Frequently Asked Questions</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Manage questions for both Dashboard and Website.</p>
    </div>
    <a href="{{ route('faqs.create') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-amber-600 hover:bg-amber-700 transition-all hover:scale-[1.02] active:scale-[0.98]">
        <i class="fas fa-plus mr-2"></i> Add New FAQ
    </a>
</div>

<div class="bg-white dark:bg-richdark-surface rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <div class="p-0 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Question</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Visibility</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Order</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @forelse($faqs as $faq)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1 truncate max-w-xs">{{ $faq->question }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 truncate max-w-xs mt-0.5">{{ Str::limit($faq->answer, 60) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 text-[10px] font-black tracking-widest rounded-full uppercase 
                            {{ $faq->visibility === 'dashboard' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' }}">
                            {{ $faq->visibility }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600 dark:text-gray-400">
                        {{ $faq->order }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <form action="{{ route('faqs.toggle-status', $faq) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1 text-[10px] font-black tracking-widest rounded-full transition-all border
                                {{ $faq->status ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 hover:bg-emerald-500 hover:text-white' : 'bg-red-500/10 text-red-600 border-red-500/20 hover:bg-red-50 hover:text-white' }}">
                                {{ $faq->status ? 'ACTIVE' : 'INACTIVE' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('faqs.edit', $faq) }}" class="p-2 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500 rounded-lg hover:bg-amber-600 hover:text-white dark:hover:bg-amber-500 transition-all">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('faqs.destroy', $faq) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="p-2 bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 rounded-lg hover:text-red-600 dark:hover:text-red-500 hover:bg-gray-200 dark:hover:bg-white/10 transition-all delete-trigger" data-message="Are you sure you want to delete FAQ '{{ addslashes($faq->question) }}'?">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-white/5 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-question-circle text-2xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">No FAQs Found</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-xs mx-auto">Start by adding your first frequently asked question to help your users.</p>
                            <a href="{{ route('faqs.create') }}" class="mt-4 text-amber-600 dark:text-amber-500 font-bold hover:underline">Create FAQ Now</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($faqs->hasPages())
    <div class="px-6 py-4 bg-gray-50 dark:bg-white/5 border-t border-gray-200 dark:border-white/5">
        {{ $faqs->links() }}
    </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.delete-trigger', function() {
            const btn = $(this);
            const form = btn.closest('form');
            const message = btn.data('message');
            
            openDeleteModal(message, function() {
                form.submit();
            });
        });
    });
</script>
@endsection
