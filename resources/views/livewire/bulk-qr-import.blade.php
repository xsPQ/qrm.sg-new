<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Bulk QR Import') }}
    </h2>
</x-slot>

<div class="py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('CSV import for QR codes') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Upload a CSV with the columns title,type,content_url,content_text,alias. Only business accounts can use this tool.') }}
                    </p>
                </div>

                <a href="{{ route('account.bulk-import.template') }}"
                   class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    {{ __('Download template CSV') }}
                </a>
            </div>

            <form wire:submit.prevent="importCsv" class="mt-6 space-y-4">
                <div>
                    <label for="csvUpload" class="block text-sm font-medium text-gray-700">{{ __('CSV file') }}</label>
                    <input id="csvUpload"
                           type="file"
                           accept=".csv,text/csv"
                           wire:model="csvUpload"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    @error('csvUpload')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-primary-button>
                        <span wire:loading.remove wire:target="importCsv,csvUpload">{{ __('Import CSV') }}</span>
                        <span wire:loading wire:target="importCsv,csvUpload">{{ __('Processing…') }}</span>
                    </x-primary-button>

                    <p class="text-xs text-gray-500">
                        {{ __('The import runs in a single transaction. If any row fails validation, nothing is created.') }}
                    </p>
                </div>
            </form>
        </div>

        @if ($previewError)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $previewError }}
            </div>
        @endif

        @if (!empty($previewRows))
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 p-4 sm:p-6">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('Preview') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Showing the first :count rows from the uploaded CSV.', ['count' => count($previewRows)]) }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Title') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Content URL') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Content text') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Alias') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($previewRows as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['row'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row['title'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['type'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 break-all">{{ $row['content_url'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 break-all">{{ $row['content_text'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['alias'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($summary['message'])
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                <div class="font-semibold">{{ $summary['message'] }}</div>
                <div class="mt-1 text-green-700">
                    {{ __('Imported: :imported · Failed: :failed', ['imported' => $summary['imported'] ?? 0, 'failed' => $summary['failed'] ?? 0]) }}
                </div>
            </div>
        @endif

        @if (!empty($rowErrors))
            <div class="rounded-lg border border-red-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-red-100 bg-red-50 p-4 sm:p-6">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-red-700">{{ __('Import errors') }}</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-red-100">
                        <thead class="bg-red-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-red-700">{{ __('Row') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-red-700">{{ __('Errors') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-50 bg-white">
                            @foreach ($rowErrors as $error)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $error['row'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-red-700">
                                        <ul class="list-disc space-y-1 pl-5">
                                            @foreach (($error['errors'] ?? []) as $message)
                                                <li>{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (!empty($resultRows))
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 p-4 sm:p-6">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('Imported QR codes') }}</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Title') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Code') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Alias') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('URL') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Scans') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Created at') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($resultRows as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row['title'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['type'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['code'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['alias'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 break-all">{{ $row['url'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['status'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['scan_count'] ?? '' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['created_at'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
