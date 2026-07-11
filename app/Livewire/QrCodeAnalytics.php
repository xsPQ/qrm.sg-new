<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\QrCode;
use App\Models\Scan;
use App\Models\ScanStatsDaily;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Analytics dashboard for a single QR code (Pflichtenheft §12.6, P3-T01).
 * Uses Chart.js (via CDN) — no custom BI engine.
 */
#[Layout('layouts.app')]
class QrCodeAnalytics extends Component
{
    public QrCode $qrCode;

    public function mount(QrCode $qrCode): void
    {
        $this->qrCode = $qrCode;
    }

    public function render()
    {
        $qrId = $this->qrCode->id;

        $totalScans = (int) $this->qrCode->scan_count;
        $scansToday = Scan::where('qr_code_id', $qrId)
            ->whereDate('scanned_at', today())
            ->count();
        $uniqueIps = Scan::where('qr_code_id', $qrId)
            ->distinct('ip_hash')
            ->count('ip_hash');

        // 7-day time series — prefer aggregated, fallback to raw
        $rawDaily = Scan::where('qr_code_id', $qrId)
            ->where('scanned_at', '>=', now()->subDays(6))
            ->selectRaw('DATE(scanned_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day');

        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('M j');
            $data[] = (int) ($rawDaily[$date] ?? 0);
        }

        // Top devices (last 30 days)
        $topDevices = Scan::where('qr_code_id', $qrId)
            ->where('scanned_at', '>=', now()->subDays(30))
            ->selectRaw('device_type, COUNT(*) as count')
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'device_type')
            ->toArray();

        // Top countries
        $topCountries = Scan::where('qr_code_id', $qrId)
            ->where('scanned_at', '>=', now()->subDays(30))
            ->selectRaw('geo_country, COUNT(*) as count')
            ->whereNotNull('geo_country')
            ->groupBy('geo_country')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'geo_country')
            ->toArray();

        $recentScans = Scan::where('qr_code_id', $qrId)
            ->orderByDesc('scanned_at')
            ->limit(10)
            ->get();

        // FEAT-06: Per-variant A/B test metrics
        $variants = $this->qrCode->variants()
            ->orderBy('sort_order')
            ->get();

        $variantStats = [];
        if ($variants->isNotEmpty()) {
            $variantScans = Scan::where('qr_code_id', $qrId)
                ->whereNotNull('qr_code_variant_id')
                ->selectRaw('qr_code_variant_id, COUNT(*) as count')
                ->groupBy('qr_code_variant_id')
                ->pluck('count', 'qr_code_variant_id')
                ->toArray();

            foreach ($variants as $variant) {
                $variantStats[] = [
                    'label' => $variant->label,
                    'url' => $variant->url,
                    'scan_count' => $variant->scan_count,
                    'scan_record_count' => $variantScans[$variant->id] ?? 0,
                    'weight' => $variant->weight,
                    'device_target' => $variant->device_target,
                ];
            }
        }

        return view('livewire.qr-code-analytics', [
            'totalScans' => $totalScans,
            'scansToday' => $scansToday,
            'uniqueIps' => $uniqueIps,
            'chartLabels' => $labels,
            'chartData' => $data,
            'topDevices' => $topDevices,
            'topCountries' => $topCountries,
            'recentScans' => $recentScans,
            'variantStats' => $variantStats,
            'hasVariants' => !empty($variantStats),
        ]);
    }
}