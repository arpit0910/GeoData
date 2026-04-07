@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Edit Branch</h1>
    <p class="mt-1 text-sm text-gray-600">Update branch details for {{ $bankBranch->bank->name }}.</p>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <form action="{{ route('bank-branches.update', $bankBranch) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Bank --}}
                <div>
                    <label for="bank_id" class="block text-sm font-medium text-gray-700">Bank</label>
                    <select name="bank_id" id="bank_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Bank</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_id', $bankBranch->bank_id) == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- IFSC --}}
                <div>
                    <label for="ifsc" class="block text-sm font-medium text-gray-700">IFSC Code</label>
                    <input type="text" name="ifsc" id="ifsc" value="{{ old('ifsc', $bankBranch->ifsc) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                {{-- Branch Name --}}
                <div>
                    <label for="branch" class="block text-sm font-medium text-gray-700">Branch Name</label>
                    <input type="text" name="branch" id="branch" value="{{ old('branch', $bankBranch->branch) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                {{-- MICR --}}
                <div>
                    <label for="micr" class="block text-sm font-medium text-gray-700">MICR Code</label>
                    <input type="text" name="micr" id="micr" value="{{ old('micr', $bankBranch->micr) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                {{-- State --}}
                <div>
                    <label for="state_id" class="block text-sm font-medium text-gray-700">State</label>
                    <select name="state_id" id="state_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ old('state_id', $bankBranch->state_id) == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- City --}}
                <div>
                    <label for="city_id" class="block text-sm font-medium text-gray-700">City</label>
                    <select name="city_id" id="city_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select State First</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ old('city_id', $bankBranch->city_id) == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Contact --}}
                <div>
                    <label for="contact" class="block text-sm font-medium text-gray-700">Contact</label>
                    <input type="text" name="contact" id="contact" value="{{ old('contact', $bankBranch->contact) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                {{-- SWIFT --}}
                <div>
                    <label for="swift" class="block text-sm font-medium text-gray-700">SWIFT Code</label>
                    <input type="text" name="swift" id="swift" value="{{ old('swift', $bankBranch->swift) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                {{-- Address --}}
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea name="address" id="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('address', $bankBranch->address) }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('bank-branches.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    Update Branch
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const citiesByStateUrl = "{{ route('pincodes.cities-by-state', ':id') }}";

    document.getElementById('state_id').addEventListener('change', function () {
        const stateId = this.value;
        const citySelect = document.getElementById('city_id');

        // Only clear if it's a manual change, not initialization? 
        // Actually, always clearing is fine if we re-populate manually for initialization if needed.
        // But here I'm using PHP to populate the initial list.
        
        // Prevent clearing on initial load if possible
        if (this.dataset.init === 'true') {
            delete this.dataset.init;
            return;
        }

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
                citySelect.disabled = false;
            })
            .catch(() => {
                citySelect.innerHTML = '<option value="">— Error Loading Cities —</option>';
                citySelect.disabled = false;
            });
    });

    // Mark as init to prevent change listener from wiping the PHP-rendered cities
    document.getElementById('state_id').dataset.init = 'true';
</script>
@endpush
