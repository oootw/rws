<x-filament-panels::page>
    <div class="space-y-6">
        @if ($loadError !== null)
            <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/40 dark:text-red-200">
                <strong>Ошибка обращения к Telegram API:</strong>
                <div class="mt-1 font-mono text-xs">{{ $loadError }}</div>
            </div>
        @endif

        <section>
            <h3 class="mb-2 text-base font-medium">getMe</h3>
            @if (count($me) === 0)
                <div class="text-sm text-gray-500">—</div>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($me as $key => $value)
                                <tr>
                                    <td class="px-3 py-1.5 align-top whitespace-nowrap"><code class="text-xs">{{ $key }}</code></td>
                                    <td class="px-3 py-1.5 font-mono text-xs break-all">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section>
            <h3 class="mb-2 text-base font-medium">getWebhookInfo</h3>
            @if (count($webhook) === 0)
                <div class="text-sm text-gray-500">—</div>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($webhook as $key => $value)
                                <tr>
                                    <td class="px-3 py-1.5 align-top whitespace-nowrap"><code class="text-xs">{{ $key }}</code></td>
                                    <td class="px-3 py-1.5 font-mono text-xs break-all">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
