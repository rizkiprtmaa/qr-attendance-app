@props([
    'active' => false,
    'href' => '',
])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'mt-3 flex items-center px-4 py-2 text-sm font-inter transition-colors duration-200 ' .
            ($active
                ? 'bg-blue-500 rounded-md text-white'
                : 'text-slate-900 hover:bg-blue-500 hover:text-white hover:rounded-md'),
    ]) }}>
    {{ $slot }}
</a>
