@props([
    'url',
    'png',
])

<div class="space-y-3 text-center">
    <img
        src="data:image/png;base64,{{ $png }}"
        alt="QR-код точки"
        class="mx-auto h-64 w-64"
    />
    <div class="text-sm break-all text-gray-600 dark:text-gray-300">
        {{ $url }}
    </div>
</div>
