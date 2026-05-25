@props(['token', 'expiresAt'])

<div class="space-y-2 text-sm">
    <p>
        Действителен до:
        <strong>{{ $expiresAt->format('d.m.Y H:i') }}</strong>
    </p>

    <p class="text-xs opacity-75">Скопируйте сейчас — повторно показать значение нельзя.</p>

    <pre class="overflow-x-auto rounded-md bg-gray-900 p-2 font-mono text-xs text-amber-300 select-all">{{ $token }}</pre>
</div>
