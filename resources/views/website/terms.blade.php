@extends('layouts.public')
@section('title', 'Terms of Service - GeoData API')

@section('content')
<div class="bg-gray-50 py-16 sm:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 md:p-12 text-left">
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight mb-8">Terms of Service</h1>
            <div class="prose prose-amber max-w-none text-gray-600 font-medium leading-relaxed">
                <p>Last updated: {{ date('F d, Y') }}</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">1. Agreement to Terms</h3>
                <p>By accessing or using GeoData APIs, you agree to be bound by these Terms. If you disagree, you may not access the Service.</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">2. API Usage and Fair Use</h3>
                <p>You agree to use the API within the limits of your subscription plan. We reserve the right to throttle or suspend accounts that abuse the Service or generate excessive automated load.</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">3. Account Responsibilities</h3>
                <p>You are responsible for safeguarding your API keys and the password that you use to access the Service.</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">4. Intellectual Property</h3>
                <p>The Service and its original content, features, and databases are and will remain the exclusive property of GeoData.</p>

                <h3 class="text-2xl font-bold text-gray-900 mt-10 mb-4">5. Changes to Terms</h3>
                <p>We may modify these terms at any time. We will provide notice of any material changes via the email address associated with your account.</p>
            </div>
        </div>
    </div>
</div>
@endsection
