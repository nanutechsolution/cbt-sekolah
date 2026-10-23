@php
    $brandName = \Filament\Facades\Filament::getBrandName() ?: config('app.name');
    $developer = config('app.developer');
    $developerUrl = config('app.developer_url');
    $version = config('app.version');
@endphp

<footer class="border-t border-gray-200 px-4 py-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
    <div class="mx-auto flex max-w-7xl flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <p>
            &copy; {{ now()->year }} <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $brandName }}</span>.
            Hak cipta dilindungi.
        </p>

        <p class="flex flex-wrap items-center gap-x-1.5">
            <span>Dikembangkan oleh</span>
            @if ($developerUrl)
                <a
                    href="{{ $developerUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    {{ $developer }}
                </a>
            @else
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $developer }}</span>
            @endif
            <span aria-hidden="true">&middot;</span>
            <span>v{{ $version }}</span>
        </p>
    </div>
</footer>
