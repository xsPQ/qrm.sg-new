<?php

namespace App\Models;

use App\Jobs\AggregateScanStatsDaily;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aggregated per-day scan statistics (Pflichtenheft §3.3.2, §6.2).
 *
 * Populated by the {@see AggregateScanStatsDaily} scheduled job from
 * the raw scans table. The primary key is composite (qr_code_id, day); there is
 * no auto-incrementing id and no Eloquent-managed timestamps — rows are written
 * via query-builder upserts, so the model is used for reads and the QrCode
 * relation only.
 */
class ScanStatsDaily extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'scan_stats_daily';

    protected $keyType = 'string';

    protected $fillable = [
        'qr_code_id',
        'day',
        'scan_count',
        'unique_ips',
        'top_country',
        'top_device',
        'top_os',
        'top_browser',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'date',
            'scan_count' => 'integer',
            'unique_ips' => 'integer',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
