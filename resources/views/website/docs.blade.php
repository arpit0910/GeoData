@extends('layouts.public')
@section('title', 'Documentation - GeoData API')

@section('content')
<div class="bg-transparent py-24 sm:py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-500/10 text-amber-500 mb-6">
            <i class="fas fa-book-open text-2xl"></i>
        </div>
        <h2 class="text-3xl font-extrabold text-white sm:text-4xl tracking-tight mb-4">Documentation</h2>
        <p class="text-lg text-gray-400 max-w-2xl mx-auto font-medium mb-12">
            Our comprehensive developer documentation, SDKs, and API references are currently being updated. Please check back soon!
        </p>
        <a href="{{ route('home') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 shadow-md transition-colors">
            Return Home
        </a>
    </div>
</div>
@endsection
