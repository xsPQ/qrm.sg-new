<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Statistik-Karten (UX-P1-01) --}}
            @php
                $userId = auth()->id();
                $stats = cache()->remember("dashboard_stats_{$userId}", 60, function () use ($userId) {
                    return [
                        'total' => \App\Models\QrCode::where('user_id', $userId)->count(),
                        'active' => \App\Models\QrCode::where('user_id', $userId)->where('status', 'active')->count(),
                        'scans' => (int) \App\Models\QrCode::where('user_id', $userId)->sum('scan_count'),
                        'expiring' => \App\Models\QrCode::where('user_id', $userId)
                            ->where('status', 'active')
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now()->addDays(7))
                            ->count(),
                    ];
                });
                $statCards = [
                    ['label' => __('Total codes'), 'value' => $stats['total'], 'icon' => 'M4 6h16M4 12h16M4 18h7', 'color' => 'indigo'],
                    ['label' => __('Active'), 'value' => $stats['active'], 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green'],
                    ['label' => __('Total scans'), 'value' => number_format($stats['scans']), 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', 'color' => 'blue'],
                    ['label' => __('Expiring soon'), 'value' => $stats['expiring'], 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'amber'],
                ];
                $colorMap = [
                    'indigo' => 'bg-indigo-50 text-indigo-600',
                    'green' => 'bg-green-50 text-green-600',
                    'blue' => 'bg-blue-50 text-blue-600',
                    'amber' => 'bg-amber-50 text-amber-600',
                ];
            @endphp

            <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($statCards as $card)
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $colorMap[$card['color']] }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
                                <p class="text-xl font-bold text-gray-900">{{ $card['value'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <livewire:qr-code-list />
        </div>
    </div>
</x-app-layout>
