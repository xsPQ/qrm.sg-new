<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Analytics') }} — {{ $qrCode->title }}
        </h2>
        <a href="{{ route('qr-codes.detail', $qrCode) }}" class="text-sm text-blue-600 hover:underline">{{ __('Back to QR Code') }}</a>
    </div>
</x-slot>

<div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <p class="text-sm font-medium text-gray-500">{{ __('Total Scans') }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalScans) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <p class="text-sm font-medium text-gray-500">{{ __('Today') }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($scansToday) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <p class="text-sm font-medium text-gray-500">{{ __('Unique Visitors') }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($uniqueIps) }}</p>
                </div>
            </div>

            <!-- 7-Day Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Scans (Last 7 Days)') }}</h3>
                @if($totalScans === 0 && $scansToday === 0)
                    <p class="text-center text-gray-400 py-8">{{ __('No scans yet. Your QR code analytics will appear here once someone scans it.') }}</p>
                @else
                    <div style="position: relative; height: 250px;">
                        <canvas id="scansChart"></canvas>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Top Devices -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Top Devices') }}</h3>
                    @if(empty($topDevices))
                        <p class="text-gray-400 text-sm py-4">{{ __('No device data available.') }}</p>
                    @else
                        <ul class="space-y-2">
                            @foreach($topDevices as $device => $count)
                                <li class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">{{ ucfirst($device ?: 'Unknown') }}</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ number_format($count) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Top Countries -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Top Countries') }}</h3>
                    @if(empty($topCountries))
                        <p class="text-gray-400 text-sm py-4">{{ __('No country data available.') }}</p>
                    @else
                        <ul class="space-y-2">
                            @foreach($topCountries as $country => $count)
                                <li class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">{{ $country ?: __('Unknown') }}</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ number_format($count) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- A/B Test Variants (FEAT-06) -->
            @if($hasVariants)
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('A/B Test Variants') }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ __('Per-variant scan distribution and performance.') }}</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Variant') }}</th>
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Destination URL') }}</th>
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Device') }}</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">{{ __('Weight') }}</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">{{ __('Scans') }}</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">{{ __('Share') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalVariantScans = array_sum(array_column($variantStats, 'scan_count')); @endphp
                            @foreach($variantStats as $stat)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-2 px-3">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 text-blue-700 font-bold text-xs">{{ $stat['label'] }}</span>
                                    </td>
                                    <td class="py-2 px-3 text-gray-700 max-w-xs truncate" title="{{ $stat['url'] }}">{{ $stat['url'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $stat['device_target'] ? ucfirst($stat['device_target']) : '—' }}</td>
                                    <td class="py-2 px-3 text-right text-gray-700">{{ $stat['weight'] }}</td>
                                    <td class="py-2 px-3 text-right font-semibold text-gray-900">{{ number_format($stat['scan_count']) }}</td>
                                    <td class="py-2 px-3 text-right text-gray-600">
                                        {{ $totalVariantScans > 0 ? number_format($stat['scan_count'] / $totalVariantScans * 100, 1) . '%' : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Recent Scans Table -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Recent Scans') }}</h3>
                @if($recentScans->isEmpty())
                    <p class="text-gray-400 text-sm py-4">{{ __('No recent scans.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Time') }}</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Device') }}</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('OS') }}</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Country') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentScans as $scan)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-2 px-3 text-gray-700">{{ $scan->scanned_at?->format('M j, Y g:i A') }}</td>
                                        <td class="py-2 px-3 text-gray-700">{{ ucfirst($scan->device_type ?? '—') }}</td>
                                        <td class="py-2 px-3 text-gray-700">{{ $scan->os_family ?? '—' }}</td>
                                        <td class="py-2 px-3 text-gray-700">{{ $scan->geo_country ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($totalScans > 0 || $scansToday > 0)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            const el = document.getElementById('scansChart');
            if (!el) return;
            new Chart(el, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: '{{ __('Scans') }}',
                        data: @json($chartData),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        })();
    </script>
    @endif
</div>