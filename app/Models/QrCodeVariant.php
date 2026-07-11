<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FEAT-06: A/B Testing variant for a QR code.
 *
 * A variant represents one destination URL in an A/B test. When a scanner
 * hits a QR code that has variants, the VariantSelector picks one variant
 * (random weighted or device-targeted), the handler redirects to its URL,
 * and this variant's scan_count is incremented along with a variant_id
 * stamp on the Scan row for per-variant analytics.
 */
class QrCodeVariant extends Model
{
    protected $fillable = [
        'qr_code_id',
        'label',
        'url',
        'weight',
        'scan_count',
        'device_target',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'scan_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
