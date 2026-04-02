@extends('layouts.public')
@section('title', 'Terms of Service - SetuGeo API')

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
                Terms of <span class="text-gradient">Service</span>
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
                            Agreement to Terms
                        </h3>
                        <p>By accessing or using SetuGeo APIs and dashboard services, you agree to be bound by these Terms. If you disagree with any part of the terms, you may not access the Service. These terms apply to all visitors, users, and others who access or use the Service.</p>
                    </section>

                    <section>
                        <h3 class="text-2xl font-bold text-white flex items-center mb-4">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-500 mr-3 text-sm">02</span>
                            API Usage & Fair Play
                        </h3>
                        <p>Our API is designed for high-speed, reliable data retrieval. To maintain service quality for all users, we enforce usage limits based on your subscription plan. You agree to:</p>
                        <ul class="list-disc pl-6 mt-4 space-y-2">
                            <li><strong class="text-gray-300">Limits:</strong> Respect the rate limits and quotas assigned to your API keys.</li>
                            <li><strong class="text-gray-300">Security:</strong> Keep your API keys and authentication credentials secure and non-public.</li>
                            <li><strong class="text-gray-300">Compliance:</strong> Use the API only for lawful purposes and in accordance with geographic data licensing.</li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="text-2xl font-bold text-white flex items-center mb-4">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-500 mr-3 text-sm">03</span>
                            Account Management
                        </h3>
                        <p>You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your account. We reserve the right to suspend or terminate accounts that violate these security protocols or engage in unauthorized automated activity.</p>
                    </section>

                    <section>
                        <h3 class="text-2xl font-bold text-white flex items-center mb-4">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-500 mr-3 text-sm">04</span>
                            Intellectual Property
                        </h3>
                        <p>The Service and its original content (excluding content provided by users), geographic databases, API structure, algorithms, and brand assets are and will remain the exclusive property of SetuGeo and its licensors. Our data is sourced from multiple verified global datasets and provided as a consolidated high-availability service.</p>
                    </section>

                    <section>
                        <h3 class="text-2xl font-bold text-white flex items-center mb-4">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-500 mr-3 text-sm">05</span>
                            Termination
                        </h3>
                        <p>We may terminate or suspend access to our Service immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms. Upon termination, your right to use the Service will immediately cease.</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
