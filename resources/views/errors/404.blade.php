@extends('layouts.public')

@section('title', '404 - Page Not Found')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-amber-500/10 text-amber-500 mb-8 border border-amber-500/20 shadow-inner">
            <i class="fas fa-exclamation-triangle text-4xl"></i>
        </div>
        <h1 class="text-6xl md:text-8xl font-black text-white mb-4 tracking-tighter">404</h1>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-300 mb-8">Data Point Not Found</h2>
        <p class="text-gray-500 max-w-md mx-auto mb-10 font-medium">
            The coordinates you're looking for don't exist in our global database. Let's get you back on track.
        </p>
        <a href="{{ route('home') }}" class="inline-flex items-center px-8 py-4 bg-amber-600 hover:bg-amber-700 text-white rounded-2xl font-bold transition-all shadow-xl shadow-amber-600/20 transform hover:-translate-y-1">
            <i class="fas fa-home mr-2"></i> Return to Homepage
        </a>
    </div>
</div>
@endsection
