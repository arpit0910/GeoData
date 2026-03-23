@extends('layouts.public')
@section('title', 'Privacy Policy - GeoData API')

@section('content')
<div class="bg-gray-50 py-16 sm:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 md:p-12 text-left">
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight mb-8">Privacy Policy</h1>
            <div class="prose prose-amber max-w-none text-gray-600 font-medium leading-relaxed">
                <p>Last updated: {{ date('F d, Y') }}</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">1. Introduction</h3>
                <p>At GeoData, we respect your privacy and are committed to protecting it. This Privacy Policy explains our practices regarding the collection, use, and disclosure of your information.</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">2. Information We Collect</h3>
                <p>We may collect personal data such as your name, email, billing information, and IP addresses when you interact with our APIs or Dashboard.</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">3. How We Use Your Information</h3>
                <p>We use the information we collect to provide and maintain our Service, process transactions, notify you about changes, and provide customer support.</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">4. Data Security</h3>
                <p>We use robust security measures to protect your personal information, though no method of transmission over the Internet is 100% secure.</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">5. Contact Us</h3>
                <p>If you have any questions about this Privacy Policy, please contact us via our <a href="{{ route('contact') }}" class="text-amber-600 hover:text-amber-700 underline">Contact Form</a>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
