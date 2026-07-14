<?php

namespace App\Models;

use App\Jobs\AggregateScanStatsHourly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aggregated per-hour scan statistics (Pflichtenheft §3.3.2, §6.2).
 *
 * Populated by the {@see AggregateScanStatsHourly} scheduled job.
 * The primary key is composite (qr_code_id, hour); there is no auto-incrementing
 * id and no Eloquent-managed timestamps — rows are written via query-builder
 * upserts, so the model is used for reads and the QrCode relation only.
 */
class ScanStatsHourly extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'scan_stats_hourly';

    protected $keyType = 'string';

    protected $fillable = [
        'qr_code_id',
        'hour',
        'scan_count',
        'unique_ips',
    ];

    protected function casts(): array
    {
        return [
            'hour' => 'datetime',
            'scan_count' => 'integer',
            'unique_ips' => 'integer',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
