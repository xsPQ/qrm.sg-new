<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FEAT-07: Immutable snapshot of a QR code's state at the moment it was changed.
 *
 * Each time a QR code's content, title, or settings are updated, the PREVIOUS
 * state is captured here. Users can browse the history and restore a prior
 * version from the editor.
 */
class QrCodeRevision extends Model
{
    protected $fillable = [
        'qr_code_id',
        'user_id',
        'version',
        'snapshot',
        'change_summary',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'version' => 'integer',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a short human-readable summary of what changed.
     */
    public static function summarizeChanges(array $old, array $new): string
    {
        $changes = [];

        if (($old['title'] ?? null) !== ($new['title'] ?? null)) {
            $changes[] = 'title';
        }

        $oldContent = $old['content'] ?? [];
        $newContent = $new['content'] ?? [];
        if ($oldContent !== $newContent) {
            $diffKeys = array_keys(array_diff_assoc($oldContent, $newContent) + array_diff_assoc($newContent, $oldContent));
            if (!empty($diffKeys)) {
                $changes[] = 'content (' . implode(', ', array_slice($diffKeys, 0, 3)) . ')';
            }
        }

        $oldSettings = $old['settings'] ?? [];
        $newSettings = $new['settings'] ?? [];
        if ($oldSettings !== $newSettings) {
            $changes[] = 'settings';
        }

        if (($old['status'] ?? null) !== ($new['status'] ?? null)) {
            $changes[] = 'status: ' . ($old['status'] ?? '?') . ' → ' . ($new['status'] ?? '?');
        }

        return empty($changes) ? 'updated' : implode('; ', $changes);
    }
}
