@props([
    'title',
    'label' => 'Sync Now',
    'icon' => 'fa-rotate',
    'confirm' => 'Start this synchronization now?',
    'class' => 'bg-amber-600 hover:bg-amber-700 text-white',
])

<button type="button" data-admin-sync data-title="{{ $title }}" data-confirm="{{ $confirm }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-black transition-colors disabled:cursor-wait disabled:opacity-60 {$class}"]) }}>
    <i class="fas {{ $icon }} mr-2"></i><span>{{ $label }}</span>
</button>

@once
    @push('scripts')
        <script>
            document.addEventListener('click', async function (event) {
                const button = event.target.closest('[data-admin-sync]');
                if (!button || button.disabled || !confirm(button.dataset.confirm)) return;

                const icon = button.querySelector('i');
                const label = button.querySelector('span');
                const originalLabel = label.textContent;
                button.disabled = true;
                icon.classList.add('fa-spin');
                label.textContent = 'Syncing...';

                try {
                    const response = await fetch(@json(route('admin.crons.run')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ title: button.dataset.title }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Synchronization failed.');
                    window.toastr ? toastr.success(result.message) : alert(result.message);
                    window.setTimeout(() => window.location.reload(), 800);
                } catch (error) {
                    window.toastr ? toastr.error(error.message) : alert(error.message);
                    button.disabled = false;
                    icon.classList.remove('fa-spin');
                    label.textContent = originalLabel;
                }
            });
        </script>
    @endpush
@endonce
