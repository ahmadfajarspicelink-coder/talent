<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-200 leading-tight">
            {{ __('Laporan Margin per Client') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-slate-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-slate-400">Client</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-slate-400">Total Margin OTC</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-slate-400">Total Margin MRC</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @forelse ($Total_Margin_Per_Client as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['client']->name }}</td>
                                        <td class="px-4 py-3 text-right">
                                            @if (is_null($row['total']['otc']))
                                                <span class="text-gray-400 dark:text-slate-500 italic">tidak tersedia</span>
                                            @else
                                                <span class="text-gray-900 dark:text-slate-100 tabular-nums"><x-rupiah :value="$row['total']['otc']" /></span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if (is_null($row['total']['mrc']))
                                                <span class="text-gray-400 dark:text-slate-500 italic">tidak tersedia</span>
                                            @else
                                                <span class="text-gray-900 dark:text-slate-100 tabular-nums"><x-rupiah :value="$row['total']['mrc']" /></span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-gray-400 dark:text-slate-500">
                                            Belum ada Client untuk dilaporkan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
