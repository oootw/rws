<x-filament-panels::page>
    <div class="space-y-4">
        <div class="text-sm text-gray-600 dark:text-gray-300">
            Всего записей: <strong>{{ $total }}</strong>
        </div>

        @if (count($jobs) === 0)
            <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-gray-500 dark:border-gray-700 dark:text-gray-400">
                Очередь чистая — упавших задач нет.
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">Когда</th>
                            <th class="px-3 py-2 text-left font-medium">Очередь</th>
                            <th class="px-3 py-2 text-left font-medium">Job</th>
                            <th class="px-3 py-2 text-left font-medium">Ошибка</th>
                            <th class="px-3 py-2 text-right font-medium">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($jobs as $job)
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ $job['failedAt'] }}
                                </td>
                                <td class="px-3 py-2">
                                    <code class="text-xs">{{ $job['connection'] }} / {{ $job['queue'] }}</code>
                                </td>
                                <td class="px-3 py-2">
                                    <code class="text-xs">{{ class_basename($job['jobClass']) }}</code>
                                </td>
                                <td class="px-3 py-2 max-w-md break-words text-xs text-gray-600 dark:text-gray-300">
                                    {{ $job['exceptionFirstLine'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-right">
                                    <x-filament::button
                                        size="xs"
                                        color="success"
                                        wire:click="retry('{{ $job['uuid'] }}')"
                                        wire:confirm="Перезапустить эту задачу?"
                                    >
                                        Retry
                                    </x-filament::button>

                                    <x-filament::button
                                        size="xs"
                                        color="danger"
                                        wire:click="deleteJob('{{ $job['uuid'] }}')"
                                        wire:confirm="Удалить запись без перезапуска?"
                                    >
                                        Delete
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
