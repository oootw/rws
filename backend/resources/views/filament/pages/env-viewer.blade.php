<x-filament-panels::page>
    <div class="space-y-4">
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Источник: <code>{{ $filePath }}</code>
            @if (! $fileExists)
                <span class="text-red-600">(файл не найден)</span>
            @endif
        </div>

        @if (count($env) === 0)
            <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-gray-500 dark:border-gray-700 dark:text-gray-400">
                .env пустой или нечитаем.
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">Переменная</th>
                            <th class="px-3 py-2 text-left font-medium">Значение</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($env as $key => $value)
                            <tr>
                                <td class="px-3 py-2 align-top whitespace-nowrap">
                                    <code class="text-xs">{{ $key }}</code>
                                </td>
                                <td class="px-3 py-2 font-mono text-xs break-all">
                                    {{ $value }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
