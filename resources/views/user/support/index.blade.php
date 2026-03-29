@extends('layouts.app')

@section('header', 'Help & Support')

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ activeTab: 'new' }">
    <!-- Header Section -->
    <div class="mb-10 text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl">How can we help you?</h2>
        <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">Submit a ticket or browse your previous requests.</p>
    </div>

    <!-- Tab Switcher -->
    <div class="flex justify-center mb-8">
        <div class="inline-flex p-1 bg-gray-100 dark:bg-white/5 rounded-xl border border-gray-200 dark:border-white/10 shadow-inner">
            <button @click="activeTab = 'new'" :class="activeTab === 'new' ? 'bg-white dark:bg-amber-600 text-gray-900 dark:text-white shadow-md' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all duration-300">
                <i class="fas fa-plus-circle mr-2"></i> Submit New Ticket
            </button>
            <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'bg-white dark:bg-amber-600 text-gray-900 dark:text-white shadow-md' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all duration-300">
                <i class="fas fa-history mr-2"></i> My Ticket History
            </button>
            <button @click="activeTab = 'faqs'" :class="activeTab === 'faqs' ? 'bg-white dark:bg-amber-600 text-gray-900 dark:text-white shadow-md' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all duration-300">
                <i class="fas fa-question-circle mr-2"></i> FAQs
            </button>
        </div>
    </div>

    <!-- New Ticket Form -->
    <div x-show="activeTab === 'new'" x-transition:enter="transition ease-out duration-300 transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" class="space-y-6">
        <div class="bg-white dark:bg-richdark-surface rounded-2xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden">
            <div class="p-8 lg:p-12">
                <form action="{{ route('support.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <label for="category_id" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Issue Category</label>
                            <select name="category_id" id="category_select" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all appearance-none cursor-pointer" required>
                                <option value="">Select a Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="sub_category_id" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Sub-Category <span class="text-xs font-normal text-gray-400">(Optional)</span></label>
                            <select name="sub_category_id" id="sub_category_select" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all appearance-none cursor-pointer disabled:opacity-50" disabled>
                                <option value="">First select a category</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-8">
                        <label for="title" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Subject / Title</label>
                        <input type="text" name="title" id="title" placeholder="Briefly describe your issue" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all" required>
                    </div>

                    <div class="mb-8">
                        <label for="description" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Detailed Description</label>
                        <textarea name="description" id="description" rows="5" placeholder="Tell us more about the issue you are facing..." class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all" required></textarea>
                    </div>

                    <div class="mb-10">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Attachment <span class="text-xs font-normal text-gray-400">(Optional - Image/PDF max 5MB)</span></label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-700 border-dashed rounded-xl hover:border-amber-500 dark:hover:border-amber-600 transition-colors cursor-pointer group bg-gray-50/50 dark:bg-gray-900/50" onclick="document.getElementById('attachment').click()">
                            <div class="space-y-2 text-center">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 group-hover:text-amber-500 transition-colors"></i>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                    <span class="relative font-semibold text-amber-600 group-hover:text-amber-500 transition-colors">Upload a file</span>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, PDF up to 5MB</p>
                            </div>
                            <input id="attachment" name="attachment" type="file" class="sr-only">
                        </div>
                        <div id="file-name-preview" class="mt-2 text-sm text-amber-600 font-medium hidden"></div>
                    </div>

                    <div class="mt-10 flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                            Submit Ticket <i class="fas fa-paper-plane ml-3 text-sm"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Ticket History -->
    <div x-show="activeTab === 'history'" x-transition:enter="transition ease-out duration-300 transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" class="space-y-6">
        @forelse($tickets as $ticket)
            <div class="bg-white dark:bg-richdark-surface rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                        <div class="flex items-center space-x-3 mb-2 md:mb-0">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider 
                                @if($ticket->status == 'pending') bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-400
                                @elseif($ticket->status == 'resolved') bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-400
                                @else bg-gray-100 text-gray-800 dark:bg-gray-500/20 dark:text-gray-400 @endif">
                                {{ $ticket->status }}
                            </span>
                            <span class="text-xs text-gray-500">#{{ $ticket->id }} &bull; {{ $ticket->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-widest">
                            {{ $ticket->category->name }} @if($ticket->subCategory) / {{ $ticket->subCategory->name }} @endif
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $ticket->title }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2">{{ $ticket->description }}</p>
                    
                    @if($ticket->admin_note)
                        <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-500/5 rounded-xl border border-amber-100 dark:border-amber-500/10">
                            <h4 class="text-[10px] font-black text-amber-700 dark:text-amber-500 uppercase tracking-widest mb-2">Resolution Note</h4>
                            <p class="text-xs text-gray-700 dark:text-gray-300 italic">"{{ $ticket->admin_note }}"</p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-20 bg-white dark:bg-richdark-surface rounded-2xl border-2 border-dashed border-gray-200 dark:border-white/5">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-4 block"></i>
                <h3 class="text-lg font-bold text-gray-400">No tickets found</h3>
                <p class="text-gray-400 text-sm">Submit your first support request using the "New Ticket" tab.</p>
            </div>
        @endforelse
    </div>

    <!-- FAQs Tab -->
    <div x-show="activeTab === 'faqs'" x-transition:enter="transition ease-out duration-300 transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" class="space-y-4">
        <div class="grid grid-cols-1 gap-4" x-data="{ selectedFaq: null }">
            @forelse($faqs as $faq)
                <div class="bg-white dark:bg-richdark-surface rounded-2xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
                    <button @click="selectedFaq = selectedFaq === {{ $faq->id }} ? null : {{ $faq->id }}" 
                        class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none group">
                        <span class="text-base font-bold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-500 transition-colors">{{ $faq->question }}</span>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300" :class="selectedFaq === {{ $faq->id }} ? 'rotate-180 text-amber-500' : ''"></i>
                    </button>
                    <div x-show="selectedFaq === {{ $faq->id }}" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="px-6 pb-6 text-sm text-gray-600 dark:text-gray-400 leading-relaxed border-t border-gray-50 dark:border-white/5 pt-4">
                        {!! nl2br(e($faq->answer)) !!}
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-white dark:bg-richdark-surface rounded-2xl border-2 border-dashed border-gray-200 dark:border-white/5">
                    <i class="fas fa-question-circle text-5xl text-gray-300 mb-4 block"></i>
                    <h3 class="text-lg font-bold text-gray-400">No FAQs available yet</h3>
                    <p class="text-gray-400 text-sm">We're working on adding more helpful information here.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle Sub-categories dynamic loading
        $('#category_select').on('change', function() {
            const categoryId = $(this).val();
            const subSelect = $('#sub_category_select');
            
            if (!categoryId) {
                subSelect.html('<option value="">First select a category</option>').prop('disabled', true);
                return;
            }

            subSelect.prop('disabled', true).html('<option value="">Loading...</option>');

            $.get(`/help-support/sub-categories/${categoryId}`, function(data) {
                let html = '<option value="">Select a Sub-Category</option>';
                data.forEach(sub => {
                    html += `<option value="${sub.id}">${sub.name}</option>`;
                });
                subSelect.html(html).prop('disabled', false);
            });
        });

        // File name preview
        $('#attachment').on('change', function() {
            if (this.files && this.files[0]) {
                $('#file-name-preview').text('Selected: ' + this.files[0].name).removeClass('hidden');
            }
        });
    });
</script>
@endpush
