<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete Profile - GeoData API</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #000000; color: #ffffff; }
        /* Auto Dark Mode Overrides for Inputs */
        input, select, textarea { background-color: rgba(255,255,255,0.05) !important; color: white !important; border-color: rgba(255,255,255,0.1) !important; }
        input:focus, select:focus, textarea:focus { background-color: rgba(0,0,0,0.5) !important; border-color: #f59e0b !important; }
        input:-webkit-autofill, input:-webkit-autofill:hover, input:-webkit-autofill:focus, input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #0a0a0a inset !important;
            -webkit-text-fill-color: white !important;
        }
        label, .text-gray-700 { color: rgba(255,255,255,0.8) !important; }
        .text-gray-500, .text-gray-600 { color: rgba(255,255,255,0.5) !important; }
        .bg-white { background-color: rgba(10,10,10,0.8) !important; backdrop-filter: blur(16px); border-color: rgba(255,255,255,0.1) !important; }
        .bg-gray-50 { background-color: rgba(255,255,255,0.02) !important; border-color: rgba(255,255,255,0.05) !important; } /* For readonly inputs */
        .bg-red-50 { background-color: rgba(239,68,68,0.1) !important; border-color: rgba(239,68,68,0.5) !important; }
        .text-red-700 { color: rgba(252,165,165,1) !important; }
    </style>
</head>
<body class="bg-[#000000] flex items-center justify-center min-h-screen py-12 selection:bg-amber-500 selection:text-white relative">
    
    <!-- GeoData Background Elements -->
    <div class="absolute inset-0 z-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:40px_40px] pointer-events-none"></div>
    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 -z-10 w-[600px] h-[600px] rounded-full bg-amber-500 opacity-10 blur-[150px] pointer-events-none"></div>
    
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl p-8 md:p-10 border border-white/10 relative z-10">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-amber-500">Complete your profile</h1>
            <p class="text-gray-500 mt-2 text-sm max-w-sm mx-auto">Just a few more details so we can tailor the GeoData dashboard perfectly to your needs.</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-md">
                <div class="flex">
                    <div class="ml-3">
                        <ul class="list-disc list-inside text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.complete.post') }}" class="space-y-5">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name <span class="text-red-500">*</span></label>
                    <div class="mt-1">
                        <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required placeholder="e.g. Acme Pvt. Ltd."
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    </div>
                </div>

                <div>
                    <label for="gst_number" class="block text-sm font-medium text-gray-700">GSTIN <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <div class="mt-1">
                        <input id="gst_number" name="gst_number" type="text" value="{{ old('gst_number') }}" placeholder="e.g. 27AAAAA0000A1Z5" pattern="^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$" title="Please enter a valid 15-character Indian GSTIN"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                    <div class="mt-1">
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required placeholder="+91 98765 43210" pattern="^(?:\+?91[\-\s]?)?[6-9]\d{9}$" title="Please enter a valid 10-digit Indian mobile number (e.g. +91 9876543210)"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    </div>
                </div>
                
                <div class="md:col-span-2">
                    <label for="company_website" class="block text-sm font-medium text-gray-700">Company Website <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <div class="mt-1">
                        <input id="company_website" name="company_website" type="text" value="{{ old('company_website') }}" placeholder="https://www.example.com"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label for="address_line_1" class="block text-sm font-medium text-gray-700">Address Line 1 <span class="text-red-500">*</span></label>
                    <div class="mt-1">
                        <input id="address_line_1" name="address_line_1" type="text" value="{{ old('address_line_1') }}" required placeholder="e.g. Building, Street Name"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label for="address_line_2" class="block text-sm font-medium text-gray-700">Address Line 2 <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <div class="mt-1">
                        <input id="address_line_2" name="address_line_2" type="text" value="{{ old('address_line_2') }}" placeholder="e.g. Landmark, Area"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    </div>
                </div>

                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700">Country <span class="text-red-500">*</span></label>
                    <div class="mt-1">
                        <select id="country_id" name="country_id" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors bg-white">
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id', 101) == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="pincode" class="block text-sm font-medium text-gray-700">Pincode <span class="text-red-500">*</span></label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input id="pincode" name="pincode" type="text" value="{{ old('pincode') }}" required placeholder="e.g. 400001" pattern="^[1-9][0-9]{5}$" title="Please enter a valid 6-digit Indian PIN code"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors"
                            oninput="searchPincode(this.value)">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none" id="pincode_loader" style="display: none;">
                            <i class="fas fa-circle-notch fa-spin text-amber-500"></i>
                        </div>
                    </div>
                    <p id="pincode_error" class="mt-1 text-xs text-red-500 hidden">Pincode not found.</p>
                </div>

                <input type="hidden" id="state_id" name="state_id" value="{{ old('state_id') }}">
                <input type="hidden" id="city_id" name="city_id" value="{{ old('city_id') }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700">State <span class="text-red-500">*</span></label>
                    <div class="mt-1">
                        <input id="state_name" type="text" readonly placeholder="Auto-fetched via Pincode"
                            class="appearance-none block w-full px-4 py-3 border border-gray-100 bg-gray-50 rounded-lg shadow-sm text-gray-500 focus:outline-none sm:text-sm transition-colors cursor-not-allowed pointer-events-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">City <span class="text-red-500">*</span></label>
                    <div class="mt-1">
                        <input id="city_name" type="text" readonly placeholder="Auto-fetched via Pincode"
                            class="appearance-none block w-full px-4 py-3 border border-gray-100 bg-gray-50 rounded-lg shadow-sm text-gray-500 focus:outline-none sm:text-sm transition-colors cursor-not-allowed pointer-events-none">
                    </div>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-between gap-4">
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-all hover:shadow-lg">
                    Complete Profile <i class="fas fa-arrow-right ml-2 mt-1 -mr-1"></i>
                </button>
            </div>
        </form>
    </div>
    <script>
        let debounceTimer;
        function searchPincode(pincode) {
            clearTimeout(debounceTimer);
            document.getElementById('pincode_error').classList.add('hidden');
            
            if (pincode.length >= 5) {
                document.getElementById('pincode_loader').style.display = 'flex';
                
                debounceTimer = setTimeout(() => {
                    fetch(`/api/pincode/${pincode}`)
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('pincode_loader').style.display = 'none';
                            if (data.success) {
                                document.getElementById('state_id').value = data.data.state_id;
                                document.getElementById('city_id').value = data.data.city_id;
                                if(data.data.country_id) {
                                    document.getElementById('country_id').value = data.data.country_id;
                                }
                                document.getElementById('state_name').value = data.data.state_name || '';
                                document.getElementById('city_name').value = data.data.city_name || '';
                                document.getElementById('pincode_error').classList.add('hidden');
                            } else {
                                clearLocationFields();
                                document.getElementById('pincode_error').innerText = data.message || 'Pincode not found.';
                                document.getElementById('pincode_error').classList.remove('hidden');
                            }
                        })
                        .catch(error => {
                            document.getElementById('pincode_loader').style.display = 'none';
                            clearLocationFields();
                            document.getElementById('pincode_error').innerText = 'Error checking pincode.';
                            document.getElementById('pincode_error').classList.remove('hidden');
                        });
                }, 600);
            } else {
                clearLocationFields();
                document.getElementById('pincode_loader').style.display = 'none';
            }
        }

        function clearLocationFields() {
            document.getElementById('state_id').value = '';
            document.getElementById('city_id').value = '';
            document.getElementById('state_name').value = '';
            document.getElementById('city_name').value = '';
        }
        
        // Auto-run if pincode has old value
        window.onload = function() {
            const initialPincode = document.getElementById('pincode').value;
            if(initialPincode) {
                searchPincode(initialPincode);
            }
        };
    </script>
</body>
</html>
