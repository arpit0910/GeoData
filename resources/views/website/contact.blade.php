@extends('layouts.public')
@section('title', 'Contact Us - GeoData API')

@section('content')
<div class="relative bg-transparent py-24 sm:py-32 overflow-hidden">
    <!-- Background element -->
    <div class="absolute top-0 center w-full h-80 bg-gradient-to-b from-amber-600/20 to-transparent"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 z-10">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-amber-200 font-bold tracking-widest uppercase text-sm">Get in Touch</h2>
            <p class="mt-2 text-4xl font-extrabold text-white tracking-tight sm:text-5xl">We'd love to hear from you.</p>
            <p class="mt-4 text-xl text-amber-100 max-w-2xl mx-auto font-medium">Have questions about integrations, custom billing models, or enterprise SLAs? Our technical support team is available 24/7.</p>
        </div>

        @if(session('success'))
            <div class="mb-8 rounded-xl bg-green-50 p-4 border border-green-200 justify-center max-w-4xl mx-auto">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-bold text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="glass-card rounded-[2rem] shadow-2xl p-8 md:p-14 max-w-4xl mx-auto">
            <form action="{{ route('contact.post') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-y-8 gap-x-8">
                @csrf
                <div>
                    <label for="first-name" class="block text-sm font-bold text-gray-900">First name</label>
                    <div class="mt-2">
                        <input type="text" name="first-name" id="first-name" autocomplete="given-name" placeholder="John" class="block w-full rounded-xl border-gray-200 px-5 py-3.5 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 border bg-gray-50 focus:bg-white transition-colors outline-none font-medium">
                    </div>
                </div>
                <div>
                    <label for="last-name" class="block text-sm font-bold text-gray-900">Last name</label>
                    <div class="mt-2">
                        <input type="text" name="last-name" id="last-name" autocomplete="family-name" placeholder="Doe" class="block w-full rounded-xl border-gray-200 px-5 py-3.5 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 border bg-gray-50 focus:bg-white transition-colors outline-none font-medium">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label for="email" class="block text-sm font-bold text-gray-900">Email address</label>
                    <div class="mt-2">
                        <input id="email" name="email" type="email" autocomplete="email" placeholder="john.doe@company.com" class="block w-full rounded-xl border-gray-200 px-5 py-3.5 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 border bg-gray-50 focus:bg-white transition-colors outline-none font-medium">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label for="message" class="block text-sm font-bold text-gray-900">How can we help you?</label>
                    <div class="mt-2">
                        <textarea id="message" name="message" rows="5" placeholder="Tell us about your project, integration questions, or support needs..." class="block w-full rounded-xl border-gray-200 px-5 py-3.5 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 border bg-gray-50 focus:bg-white transition-colors outline-none font-medium"></textarea>
                    </div>
                </div>
                <div class="md:col-span-2 pt-2">
                    <button type="submit" class="w-full rounded-xl bg-amber-600 px-8 py-4 text-center text-sm font-extrabold text-white shadow-md hover:bg-amber-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-600 transition-all transform hover:-translate-y-0.5">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
