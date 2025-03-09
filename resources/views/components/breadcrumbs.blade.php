@props([
    'items' => [],
])

<nav class="mt-2.5 flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
        @foreach ($items as $index => $item)
            <li class="{{ $index === 0 ? 'inline-flex items-center' : '' }}">
                <div class="flex items-center">
                    @if ($index > 0 && isset($item['svg']))
                        <!-- SVG custom untuk setiap breadcrumb -->
                        <svg class="mx-1 h-3 w-3 text-gray-400 rtl:rotate-180" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            {!! $item['svg'] !!}
                        </svg>
                    @elseif ($index > 0)
                        <svg class="mx-1 h-3 w-3 text-gray-400 rtl:rotate-180" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 9 4-4-4-4" />
                        </svg>
                    @endif

                    @php
                        $isFirst = $index === 0;
                        $isLast = $index === count($items) - 1;
                        $url = is_array($item) ? $item['url'] ?? null : null;
                        $label = is_array($item) ? $item['label'] ?? $item : $item;
                        $wireNavigate = is_array($item) ? $item['wire:navigate'] ?? false : false;
                    @endphp

                    @if ($url && !$isLast)
                        <a href="{{ $url }}" @if ($wireNavigate) wire:navigate @endif
                            class="{{ $isFirst ? 'inline-flex items-center ' : 'ms-1 md:ms-2 ' }} {{ $isFirst ? 'me-2.5' : '' }} text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            @if ($isFirst)
                                <svg class="me-2.5 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z" />
                                </svg>
                            @endif
                            {{ $label }}
                        </a>
                    @else
                        <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                            {{ $label }}
                        </span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</nav>
