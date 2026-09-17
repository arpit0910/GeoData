@extends('layouts.app')

@section('header', 'Create Exchange Calendar Event')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('admin.exchange-calendar.index') }}" class="text-sm font-bold text-amber-600 hover:text-amber-700"><i class="fas fa-arrow-left mr-2"></i>Back to calendar</a>
        <h1 class="mt-4 text-3xl font-black text-gray-900 dark:text-white">Create Calendar Event</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a manually managed NSE or BSE holiday or Muhurat session.</p>
    </div>
    <div class="bg-white dark:bg-richdark-surface rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 p-8">
        <form method="POST" action="{{ route('admin.exchange-calendar.store') }}">
            @csrf
            @include('admin.exchange-calendar._form')
        </form>
    </div>
</div>
@endsection
