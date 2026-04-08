@extends('layouts.app')

@section('header', 'Edit Branch')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('bank-branches.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Branches
        </a>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white mt-4 tracking-tight">Edit Branch</h1>
        <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">Update details for <span class="text-amber-600 dark:text-amber-500 font-bold">{{ $bankBranch->branch }}</span> of {{ $bankBranch->bank->name }}.</p>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <form action="{{ route('bank-branches.update', $bankBranch) }}" method="POST" class="p-8 md:p-12">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                {{-- Bank --}}
                <div class="space-y-2">
                    <label for="bank_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Bank</label>
                    <select name="bank_id" id="bank_id" required 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="">— Select Bank —</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_id', $bankBranch->bank_id) == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                        @endforeach
                    </select>
                    @error('bank_id')
                        <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- IFSC --}}
                <div class="space-y-2">
                    <label for="ifsc" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">IFSC Code</label>
                    <input type="text" name="ifsc" id="ifsc" value="{{ old('ifsc', $bankBranch->ifsc) }}" placeholder="SBIN0001234" required 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('ifsc')
                        <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Branch Name --}}
                <div class="space-y-2">
                    <label for="branch" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Branch Name</label>
                    <input type="text" name="branch" id="branch" value="{{ old('branch', $bankBranch->branch) }}" placeholder="Indira Nagar Branch" required 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('branch')
                        <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- MICR --}}
                <div class="space-y-2">
                    <label for="micr" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">MICR Code</label>
                    <input type="text" name="micr" id="micr" value="{{ old('micr', $bankBranch->micr) }}" placeholder="600002008" 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('micr')
                        <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- State --}}
                <div class="space-y-2">
                    <label for="state_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">State</label>
                    <select name="state_id" id="state_id" required 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="">— Select State —</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ old('state_id', $bankBranch->state_id) == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id')
                        <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- City --}}
                <div class="space-y-2">
                    <label for="city_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">City</label>
                    <select name="city_id" id="city_id" required disabled
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer disabled:opacity-50">
                        <option value="">Loading...</option>
                    </select>
                    @error('city_id')
                        <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contact --}}
                <div class="space-y-2">
                    <label for="contact" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Contact</label>
                    <input type="text" name="contact" id="contact" value="{{ old('contact', $bankBranch->contact) }}" placeholder="+91-1234567890" 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                </div>

                {{-- SWIFT --}}
                <div class="space-y-2">
                    <label for="swift" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">SWIFT Code</label>
                    <input type="text" name="swift" id="swift" value="{{ old('swift', $bankBranch->swift) }}" placeholder="SBININBBXXX" 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                </div>

                {{-- Address --}}
                <div class="md:col-span-2 space-y-2">
                    <label for="address" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Address</label>
                    <textarea name="address" id="address" rows="3" placeholder="Shop No. 5, Ground Floor, Global Trade Center..." 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">{{ old('address', $bankBranch->address) }}</textarea>
                </div>

                {{-- IMPS --}}
                <div class="space-y-2">
                    <label for="imps" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">IMPS</label>
                    <select name="imps" id="imps"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="0" {{ old('imps', $bankBranch->imps) == 0 ? 'selected' : '' }}>Inactive</option>
                        <option value="1" {{ old('imps', $bankBranch->imps) == 1 ? 'selected' : '' }}>Active</option>
                    </select>
                </div>

                {{-- RTGS --}}
                <div class="space-y-2">
                    <label for="rtgs" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">RTGS</label>
                    <select name="rtgs" id="rtgs"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="0" {{ old('rtgs', $bankBranch->rtgs) == 0 ? 'selected' : '' }}>Inactive</option>
                        <option value="1" {{ old('rtgs', $bankBranch->rtgs) == 1 ? 'selected' : '' }}>Active</option>
                    </select>
                </div>

                {{-- NEFT --}}
                <div class="space-y-2">
                    <label for="neft" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">NEFT</label>
                    <select name="neft" id="neft"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="0" {{ old('neft', $bankBranch->neft) == 0 ? 'selected' : '' }}>Inactive</option>
                        <option value="1" {{ old('neft', $bankBranch->neft) == 1 ? 'selected' : '' }}>Active</option>
                    </select>
                </div>

                {{-- UPI --}}
                <div class="space-y-2">
                    <label for="upi" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">UPI</label>
                    <select name="upi" id="upi"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="0" {{ old('upi', $bankBranch->upi) == 0 ? 'selected' : '' }}>Inactive</option>
                        <option value="1" {{ old('upi', $bankBranch->upi) == 1 ? 'selected' : '' }}>Active</option>
                    </select>
                </div>
            </div>

            <div class="mt-12 flex flex-col md:flex-row justify-end items-center gap-4">
                <a href="{{ route('bank-branches.index') }}" class="w-full md:w-auto px-8 py-3.5 text-sm font-black text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    Cancel
                </a>
                <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center px-10 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Update Branch <i class="fas fa-save ml-3 text-sm opacity-80"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const citiesByStateUrl = "{{ route('pincodes.cities-by-state', ':id') }}";
    const currentCityId = "{{ old('city_id', $bankBranch->city_id) }}";

    function loadCities(stateId, preselectCityId) {
        const citySelect = document.getElementById('city_id');

        citySelect.innerHTML = '<option value="">Loading...</option>';
        citySelect.disabled = true;

        if (!stateId) {
            citySelect.innerHTML = '<option value="">— Select State First —</option>';
            return;
        }

        fetch(citiesByStateUrl.replace(':id', stateId))
            .then(r => r.json())
            .then(cities => {
                citySelect.innerHTML = '<option value="">— Select City —</option>';
                cities.forEach(city => {
                    const opt = document.createElement('option');
                    opt.value = city.id;
                    opt.textContent = city.name;
                    citySelect.appendChild(opt);
                });
                if (preselectCityId) {
                    citySelect.value = preselectCityId;
                }
                citySelect.disabled = false;
            })
            .catch(() => {
                citySelect.innerHTML = '<option value="">— Error Loading Cities —</option>';
                citySelect.disabled = false;
            });
    }

    // On state change, reload cities (no preselection since user is changing state)
    document.getElementById('state_id').addEventListener('change', function () {
        loadCities(this.value, null);
    });

    // On page load, fetch cities for the current state and pre-select the branch's city
    document.addEventListener('DOMContentLoaded', function () {
        const stateId = document.getElementById('state_id').value;
        if (stateId) {
            loadCities(stateId, currentCityId);
        }
    });
</script>
@endpush
