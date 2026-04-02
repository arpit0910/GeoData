@extends('layouts.public')
@section('title', 'Privacy Policy - SetuGeo API')

@section('content')
<div class="relative overflow-hidden pt-32 pb-20">
    <!-- Background Accents -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] pointer-events-none z-0">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[300px] bg-amber-500/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Header -->
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight mb-4">
                Privacy <span class="text-gradient">Policy</span>
            </h1>
            <p class="text-gray-400 font-medium">Last updated: {{ date('F d, Y') }}</p>
        </div>

        <!-- Content Card -->
        <div class="glass-card rounded-3xl shadow-2xl p-8 md:p-16 text-left border border-white/10">
            <div class="prose prose-invert prose-amber max-w-none text-gray-400 font-medium leading-relaxed">
                <div class="space-y-12">
                    <section>
                        <h3 class="text-2xl font-bold text-white flex items-center mb-4">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-500 mr-3 text-sm">01</span>
                            Introduction
                        </h3>
                        <p>At SetuGeo, your privacy is our priority. This Privacy Policy details how we handle the personal information you provide when using our high-speed geographic data APIs and dashboard services. By using SetuGeo, you agree to the collection and use of information in accordance with this policy.</p>
                    </section>

                    <section>
                        <h3 class="text-2xl font-bold text-white flex items-center mb-4">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-500 mr-3 text-sm">02</span>
                            Information Collection
                        </h3>
                        <p>We collect information to provide better services to all our users. This includes:</p>
                        <ul class="list-disc pl-6 mt-4 space-y-2">
                            <li><strong class="text-gray-300">Account Details:</strong> Name, email address, and authentication credentials.</li>
                            <li><strong class="text-gray-300">Usage Data:</strong> API call logs, IP addresses, and response metrics for optimization.</li>
                            <li><strong class="text-gray-300">Billing Information:</strong> Payment processing details provided through our secure payment partners.</li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="text-2xl font-bold text-white flex items-center mb-4">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-500 mr-3 text-sm">03</span>
                            How We Use Data
                        </h3>
                        <p>The information we collect is used to maintain, protect, and improve our services, to develop new ones, and to protect SetuGeo and our users. We use this data to:</p>
                        <ul class="list-disc pl-6 mt-4 space-y-2">
                            <li>Provide personalized API responses and dashboard analytics.</li>
                            <li>Ensure the security of our global infrastructure.</li>
                            <li>Process transactions and send billing notifications.</li>
                            <li>Communicate technical updates and security alerts.</li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="text-2xl font-bold text-white flex items-center mb-4">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-500 mr-3 text-sm">04</span>
                            Data Security
                        </h3>
                        <p>We implement industry-standard encryption and security protocols to safeguard your data. While we strive to use commercially acceptable means to protect your personal information, remember that no method of transmission over the internet, or method of electronic storage is 100% secure.</p>
                    </section>

                    <section>
                        <h3 class="text-2xl font-bold text-white flex items-center mb-4">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-500 mr-3 text-sm">05</span>
                            Contact Us
                        </h3>
                        <p>If you have any questions about this Privacy Policy, please reach out to our dedicated support team via our <a href="{{ route('contact') }}" class="text-amber-500 hover:text-amber-400 underline decoration-amber-500/30 underline-offset-4 font-bold transition-all">Support Center</a>.</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
