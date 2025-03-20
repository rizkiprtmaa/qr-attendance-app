{{-- resources/views/components/primary-button.blade.php --}}
@props([
    'type' => 'button',
    'color' => 'blue',
    'icon' => null,
    'action' => null,
])

@php
    $colorClasses = match ($color) {
        'blue' => 'text-white border-[#1863B5] bg-gradient-to-b from-[#2196F3] to-[#167BDF]',
        'green' => 'text-white border-green-700 bg-gradient-to-b from-green-500 to-green-600',
        'gray' => 'text-white border-gray-700 bg-gradient-to-b from-gray-500 to-gray-600',
        default => 'text-white border-[#1863B5] bg-gradient-to-b from-[#2196F3] to-[#167BDF]',
    };
@endphp

<button type="{{ $type }}" @if ($action) @click="{{ $action }}" @endif
    class="{{ $colorClasses }} flex flex-col items-center gap-1 rounded-xl border px-3 py-1 text-center shadow-[inset_0_2px_0px_0px_rgba(255,255,255,0.25),0_2px_4px_0px_rgba(22,123,223,0.5)] transition duration-300 ease-in-out hover:opacity-90 active:opacity-80 disabled:cursor-not-allowed disabled:opacity-50 md:flex-row md:px-6 md:py-2"
    {{ $attributes }}>
    {{ $slot }}

    @if ($icon)
        <span hidden md:block>
            {{ $icon }}
        </span>
    @endif
</button>
