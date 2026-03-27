@extends('layouts.app')

@section('header', 'My Profile')

@section('content')
<div class="max-w-4xl mx-auto flex flex-col gap-6">
    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-50 border-l-4 border-green-500 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Subscription & Credits Section -->
    <div class="bg-white dark:bg-[#161e2d] rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 overflow-hidden transition-all duration-500">
        <div class="px-8 py-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.02] flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Subscription & Credits</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Monitor your current plan status and remaining API credits.</p>
            </div>
            @if(!$subscription)
            <a href="{{ route('pricing') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm">
                Upgrade Plan
            </a>
            @endif
        </div>
        
        <div class="p-8">
            @if($subscription)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Plan Info -->
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Current Plan</span>
                        <div class="flex items-center">
                            <div class="h-12 w-12 rounded-2xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-500 mr-4 shadow-inner">
                                <i class="fas fa-gem text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-black text-gray-900 dark:text-white uppercase">{{ $subscription->plan->name }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-tighter">{{ $subscription->plan->billing_cycle }} BILLING</p>
                            </div>
                        </div>
                    </div>

                    <!-- Credits Info -->
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Credits Usage</span>
                        <div class="flex flex-col">
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-2xl font-black text-gray-900 dark:text-white leading-none tracking-tight">{{ number_format($subscription->available_credits) }}</span>
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-tighter">/ {{ number_format($subscription->total_credits) }} Remaining</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                                @php
                                    $usagePercent = $subscription->total_credits > 0 ? ($subscription->used_credits / $subscription->total_credits) * 100 : 0;
                                    $availablePercent = 100 - $usagePercent;
                                @endphp
                                <div class="h-full bg-amber-500 transition-all duration-1000" style="width: {{ $availablePercent }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Expiry Info -->
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Next Renewal</span>
                        <div class="flex items-center">
                            <div class="h-12 w-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 mr-4 shadow-inner">
                                <i class="fas fa-calendar-alt text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-gray-900 dark:text-white">{{ $subscription->expires_at->format('M d, Y') }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-tighter">
                                    {{ now()->diffInDays($subscription->expires_at) }} DAYS REMAINING
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-6 text-center">
                    <div class="h-16 w-16 rounded-full bg-gray-50 dark:bg-white/5 flex items-center justify-center text-gray-300 dark:text-gray-600 mb-4">
                        <i class="fas fa-crown text-3xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">No Active Subscription</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mt-1">Upgrade your account to unlock premium API access and high-volume data requests.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-[#161e2d] rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 overflow-hidden transition-all duration-500" x-data="{ isEditing: false }">
        <div class="px-8 py-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.02] flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Account Information</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Manage your personal and company profile details.</p>
            </div>
            <div>
                <button type="button" x-show="!isEditing" @click="isEditing = true" class="inline-flex items-center px-4 py-2.5 border border-amber-600 dark:border-amber-500/30 text-sm font-bold rounded-xl text-amber-600 dark:text-amber-500 bg-white dark:bg-amber-500/5 hover:bg-amber-50 dark:hover:bg-amber-500/10 focus:outline-none transition-all shadow-sm">
                    <i class="fas fa-edit mr-2"></i> Edit Details
                </button>
            </div>
        </div>
        
        <form method="POST" action="{{ route('profile.update') }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-bold text-gray-700 dark:text-gray-400 mb-1.5 ml-1 uppercase tracking-wider text-[10px]">Full Name</label>
                    <div class="mt-1">
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                            placeholder="e.g. Rahul Sharma"
                            :readonly="!isEditing"
                            :class="isEditing ? 'bg-white dark:bg-white/[0.03] border-gray-300 dark:border-white/10 focus:ring-amber-500 focus:border-amber-500 shadow-sm dark:text-white' : 'bg-gray-50/50 dark:bg-white/[0.01] border-transparent text-gray-600 dark:text-gray-500 cursor-not-allowed'"
                            class="appearance-none block w-full px-5 py-3.5 border rounded-xl sm:text-sm font-medium transition-all duration-300">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address <span class="text-gray-400 font-normal">(Non-editable)</span></label>
                    <div class="mt-1">
                        <input id="email" type="email" value="{{ $user->email }}" readonly
                            class="appearance-none block w-full px-4 py-3 bg-gray-50/50 dark:bg-slate-900/50 border-transparent text-gray-500 dark:text-gray-500 cursor-not-allowed rounded-lg sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500" x-show="isEditing" style="display: none;">*</span></label>
                    <div class="mt-1">
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required placeholder="+91 98765 43210" pattern="^(?:\+?91[\-\s]?)?[6-9]\d{9}$" title="Please enter a valid 10-digit Indian mobile number"
                            :readonly="!isEditing"
                            :class="isEditing ? 'bg-white border-gray-300 focus:ring-amber-500 focus:border-amber-500 shadow-sm' : 'bg-gray-50/50 border-transparent text-gray-600 cursor-not-allowed'"
                            class="appearance-none block w-full px-4 py-3 border rounded-lg sm:text-sm transition-all duration-200">
                    </div>
                </div>

                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name <span class="text-red-500" x-show="isEditing" style="display: none;">*</span></label>
                    <div class="mt-1">
                        <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $user->company_name) }}" required
                            placeholder="e.g. GeoData Solutions Pvt Ltd"
                            :readonly="!isEditing"
                            :class="isEditing ? 'bg-white border-gray-300 focus:ring-amber-500 focus:border-amber-500 shadow-sm' : 'bg-gray-50/50 border-transparent text-gray-600 cursor-not-allowed'"
                            class="appearance-none block w-full px-4 py-3 border rounded-lg sm:text-sm transition-all duration-200">
                    </div>
                </div>
                
                <div class="md:col-span-2">
                    <label for="company_website" class="block text-sm font-medium text-gray-700">Company Website</label>
                    <div class="mt-1">
                        <input id="company_website" name="company_website" type="url" value="{{ old('company_website', $user->company_website) }}" placeholder="https://www.example.com"
                            :readonly="!isEditing"
                            :class="isEditing ? 'bg-white border-gray-300 focus:ring-amber-500 focus:border-amber-500 shadow-sm' : 'bg-gray-50 border-transparent text-gray-700 border-gray-100 cursor-not-allowed'"
                            class="appearance-none block w-full px-4 py-3 border rounded-lg sm:text-sm transition-colors">
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label for="gst_number" class="block text-sm font-medium text-gray-700">GSTIN</label>
                    <div class="mt-1">
                        <input id="gst_number" name="gst_number" type="text" value="{{ old('gst_number', $user->gst_number) }}" placeholder="27AAAAA0000A1Z5" pattern="^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$" title="Please enter a valid 15-character Indian GSTIN"
                            :readonly="!isEditing"
                            :class="isEditing ? 'bg-white border-gray-300 focus:ring-amber-500 focus:border-amber-500 shadow-sm' : 'bg-gray-50 border-transparent text-gray-700 border-gray-100 cursor-not-allowed'"
                            class="appearance-none block w-full px-4 py-3 border rounded-lg sm:text-sm transition-colors">
                    </div>
                </div>

                <div class="md:col-span-2 pt-6 mt-2 border-t border-gray-100 dark:border-slate-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-200 tracking-wide uppercase">Location Details</h4>
                </div>
                
                <div class="md:col-span-2">
                    <label for="address_line_1" class="block text-sm font-medium text-gray-700">Address Line 1 <span class="text-red-500" x-show="isEditing" style="display: none;">*</span></label>
                    <div class="mt-1">
                        <input id="address_line_1" name="address_line_1" type="text" value="{{ old('address_line_1', $user->address_line_1) }}" required
                            placeholder="Building Name, Street Area"
                            :readonly="!isEditing"
                            :class="isEditing ? 'bg-white border-gray-300 focus:ring-amber-500 focus:border-amber-500 shadow-sm' : 'bg-gray-50/50 border-transparent text-gray-600 cursor-not-allowed'"
                            class="appearance-none block w-full px-4 py-3 border rounded-lg sm:text-sm transition-all duration-200">
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label for="address_line_2" class="block text-sm font-medium text-gray-700">Address Line 2 <span class="text-gray-400 font-normal" x-show="isEditing" style="display: none;">(Optional)</span></label>
                    <div class="mt-1">
                        <input id="address_line_2" name="address_line_2" type="text" value="{{ old('address_line_2', $user->address_line_2) }}"
                            placeholder="Landmark, Flat No. etc"
                            :readonly="!isEditing"
                            :class="isEditing ? 'bg-white border-gray-300 focus:ring-amber-500 focus:border-amber-500 shadow-sm' : 'bg-gray-50/50 border-transparent text-gray-600 cursor-not-allowed'"
                            class="appearance-none block w-full px-4 py-3 border rounded-lg sm:text-sm transition-all duration-200">
                    </div>
                </div>

                <div>
                    <label for="pincode" class="block text-sm font-medium text-gray-700">Pincode <span class="text-red-500" x-show="isEditing" style="display: none;">*</span></label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input id="pincode" name="pincode" type="text" value="{{ old('pincode', $user->pincode) }}" required pattern="^[1-9][0-9]{5}$" title="Please enter a valid 6-digit Indian PIN code"
                            :readonly="!isEditing"
                            :class="isEditing ? 'bg-white border-gray-300 focus:ring-amber-500 focus:border-amber-500 shadow-sm' : 'bg-gray-50 border-transparent text-gray-700 border-gray-100 cursor-not-allowed'"
                            class="appearance-none block w-full px-4 py-3 border rounded-lg sm:text-sm transition-colors"
                            oninput="if(isEditing) searchPincode(this.value)">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none" id="pincode_loader" style="display: none;">
                            <i class="fas fa-circle-notch fa-spin text-amber-500"></i>
                        </div>
                    </div>
                    <p id="pincode_error" class="mt-1 text-xs text-red-500 hidden">Pincode not found.</p>
                </div>

                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700">Country <span class="text-red-500" x-show="isEditing" style="display: none;">*</span></label>
                    <div class="mt-1">
                        <select id="country_id" name="country_id" required 
                            :class="isEditing ? 'bg-white border-gray-300 focus:ring-amber-500 focus:border-amber-500 shadow-sm' : 'bg-gray-50 border-transparent text-gray-700 border-gray-100 cursor-not-allowed pointer-events-none'"
                            class="appearance-none block w-full px-4 py-3 border rounded-lg sm:text-sm transition-colors">
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ old('country_id', $user->country_id) == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <input type="hidden" id="state_id" name="state_id" value="{{ old('state_id', $user->state_id) }}">
                <input type="hidden" id="city_id" name="city_id" value="{{ old('city_id', $user->city_id) }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700">State <span class="text-red-500" x-show="isEditing" style="display: none;">*</span></label>
                    <div class="mt-1">
                        <input id="state_name" type="text" readonly placeholder="Auto-fetched" value="{{ old('state_name', $user->state ? $user->state->name : '') }}"
                            class="appearance-none block w-full px-4 py-3 border border-transparent bg-gray-50 rounded-lg text-gray-500 focus:outline-none sm:text-sm cursor-not-allowed pointer-events-none transition-colors">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">City <span class="text-red-500" x-show="isEditing" style="display: none;">*</span></label>
                    <div class="mt-1">
                        <input id="city_name" type="text" readonly placeholder="Auto-fetched" value="{{ old('city_name', $user->city ? $user->city->name : '') }}"
                            class="appearance-none block w-full px-4 py-3 border border-transparent bg-gray-50 rounded-lg text-gray-500 focus:outline-none sm:text-sm cursor-not-allowed pointer-events-none transition-colors">
                    </div>
                </div>

            </div>

            <div class="pt-6 flex justify-end space-x-3" x-show="isEditing" style="display: none;">
                <button type="button" @click="isEditing = false; $el.form.reset();" class="inline-flex justify-center py-2.5 px-6 border border-gray-300 dark:border-slate-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 focus:outline-none transition-colors">
                    Cancel
                </button>
                <button type="submit" class="inline-flex justify-center py-2.5 px-6 border border-transparent rounded-lg shadow-md text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-all hover:shadow-lg">
                    <i class="fas fa-save mr-2 mt-0.5"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Password Reset Section -->
    <div class="bg-white dark:bg-[#161e2d] rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 overflow-hidden mt-8 transition-all duration-500" x-data="{ isChangingPassword: false }">
        <div class="px-8 py-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.02] flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Security & Privacy</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Update your password and login credentials.</p>
            </div>
            <div x-show="!isChangingPassword">
                <button type="button" @click="isChangingPassword = true" class="inline-flex items-center px-4 py-2.5 border border-slate-800 dark:border-white/10 text-sm font-bold rounded-xl text-slate-800 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 focus:outline-none transition-all shadow-sm">
                    <i class="fas fa-shield-alt mr-2 opacity-60"></i> Change Password
                </button>
            </div>
        </div>
        
        <div x-show="isChangingPassword" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" style="display: none;">
            <form method="POST" action="{{ route('profile.password.update') }}" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Login Email</label>
                        <div class="mt-1 relative">
                            <input type="text" readonly value="{{ $user->email }}"
                                class="appearance-none block w-full px-4 py-3 bg-gray-50 border-transparent text-gray-500 cursor-not-allowed rounded-lg sm:text-sm">
                        </div>
                    </div>

                    <div x-data="{ show: false }">
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <div class="mt-1 relative">
                            <input id="current_password" name="current_password" :type="show ? 'text' : 'password'" required
                                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors pr-10">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div x-data="{ show: false }">
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <div class="mt-1 relative">
                            <input id="password" name="password" :type="show ? 'text' : 'password'" required pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$" title="Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (min 8 characters)."
                                placeholder="Min 8 characters, 1 Uppercase, 1 Number"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors pr-10">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div x-data="{ show: false }">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <div class="mt-1 relative">
                            <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" required
                                placeholder="Re-enter your new password"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors pr-10">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" @click="isChangingPassword = false; $el.form.reset();" class="inline-flex justify-center py-2.5 px-6 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex justify-center py-2.5 px-6 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-slate-800 hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-colors">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
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
</script>
@endpush
@endsection
