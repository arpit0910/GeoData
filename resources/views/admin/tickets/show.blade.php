@extends('layouts.app')

@section('header', 'Ticket Details')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Ticket #{{ $ticket->id }}</h2>
        <a href="{{ route('admin.tickets.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas fa-arrow-left mr-1"></i> Back to Tickets
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Ticket Content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-richdark-surface rounded-xl shadow-sm border border-gray-200 dark:border-white/5 p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $ticket->title }}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium 
                        @if($ticket->status == 'pending') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-500
                        @elseif($ticket->status == 'resolved') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-500
                        @else bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-500 @endif capitalize">
                        {{ $ticket->status }}
                    </span>
                </div>
                <div class="prose dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                    {{ $ticket->description }}
                </div>
                
                @if($ticket->file_path)
                    <div class="mt-6 pt-6 border-t border-gray-100 dark:border-white/5">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">Attachment</h4>
                        <a href="{{ asset('storage/' . $ticket->file_path) }}" target="_blank" class="inline-flex items-center text-amber-600 hover:text-amber-700 font-medium">
                            <i class="fas fa-paperclip mr-2"></i> View Attachment
                        </a>
                    </div>
                @endif
            </div>

            <!-- Resolve Form -->
            @if($ticket->status == 'pending')
            <div class="bg-white dark:bg-richdark-surface rounded-xl shadow-sm border border-gray-200 dark:border-white/5 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Respond & Resolve</h3>
                <form action="{{ route('admin.tickets.resolve', $ticket->id) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="admin_note" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Internal Note / Response</label>
                            <textarea name="admin_note" id="admin_note" rows="4" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-2 focus:ring-amber-500 outline-none transition-all" required placeholder="Type your response here..."></textarea>
                        </div>
                        <div class="flex space-x-4">
                            <div class="flex-1">
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mark As</label>
                                <select name="status" id="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-6 rounded-lg transition-all shadow-md">
                                    Update Ticket
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            @else
                @if($ticket->admin_note)
                <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-900/20 rounded-xl p-6">
                    <h3 class="text-sm font-bold text-amber-800 dark:text-amber-400 uppercase tracking-wider mb-3">Admin Resolution Note</h3>
                    <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                        {{ $ticket->admin_note }}
                    </p>
                    <p class="text-[10px] text-gray-500 mt-4 italic text-right">Resolved at: {{ $ticket->resolved_at->format('M d, Y h:i A') }}</p>
                </div>
                @endif
            @endif
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-richdark-surface rounded-xl shadow-sm border border-gray-200 dark:border-white/5 p-6">
                <h3 class="text-sm font-bold text-gray-500 uppercase mb-4">Ticket Info</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase">User</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ticket->user->name }}</p>
                        <p class="text-[11px] text-gray-500">{{ $ticket->user->email }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase">Category</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ticket->category->name }}</p>
                        @if($ticket->subCategory)
                            <p class="text-[11px] text-gray-500">{{ $ticket->subCategory->name }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase">Submitted On</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ticket->created_at->format('M d, Y') }}</p>
                        <p class="text-[11px] text-gray-500">{{ $ticket->created_at->format('h:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
