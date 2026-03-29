@extends('layouts.app')

@section('header', 'Edit FAQ')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('faqs.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white mt-4 tracking-tight">Edit FAQ</h1>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <form action="{{ route('faqs.update', $faq) }}" method="POST" class="p-8 md:p-12">
            @csrf
            @method('PUT')
            <div class="space-y-8">
                <!-- Question -->
                <div>
                    <label for="question" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Question</label>
                    <input type="text" name="question" id="question" value="{{ old('question', $faq->question) }}" placeholder="What is the most common concern?" 
                        class="w-full px-5 py-4 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-2xl text-gray-900 dark:text-white font-bold focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all placeholder:text-gray-400 dark:placeholder:text-gray-600" required>
                    @error('question')
                        <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Answer -->
                <div>
                    <label for="answer" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Answer</label>
                    <textarea name="answer" id="answer" rows="6" placeholder="Provide a detailed and helpful response..." 
                        class="w-full px-5 py-4 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-2xl text-gray-900 dark:text-white font-bold focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all placeholder:text-gray-400 dark:placeholder:text-gray-600" required>{{ old('answer', $faq->answer) }}</textarea>
                    @error('answer')
                        <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Visibility -->
                    <div>
                        <label for="visibility" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Visibility Category</label>
                        <select name="visibility" id="visibility" class="w-full px-5 py-4 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-2xl text-gray-900 dark:text-white font-bold focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all appearance-none cursor-pointer">
                            <option value="dashboard" {{ old('visibility', $faq->visibility) === 'dashboard' ? 'selected' : '' }}>Visible on Dashboard</option>
                            <option value="website" {{ old('visibility', $faq->visibility) === 'website' ? 'selected' : '' }}>Visible on Website</option>
                        </select>
                        @error('visibility')
                            <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Order -->
                    <div>
                        <label for="order" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Display Order</label>
                        <input type="number" name="order" id="order" value="{{ old('order', $faq->order) }}" 
                            class="w-full px-5 py-4 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-2xl text-gray-900 dark:text-white font-bold focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all placeholder:text-gray-400 dark:placeholder:text-gray-600">
                        @error('order')
                            <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-10 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                        Update FAQ <i class="fas fa-save ml-3 text-sm"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
