<?php

namespace App\Observers;

use App\Models\QrCode;
use App\Models\QrCodeRevision;

/**
 * FEAT-07: Automatically captures a revision snapshot BEFORE a QR code is
 * updated. The snapshot contains the previous state of title, content,
 * settings, alias and status — so the user can restore it later.
 *
 * Only fields in the "trackable" set are captured. Type, password_hash,
 * scan_count, burn/max_scans and entitlement_snapshot are excluded because
 * they are either immutable on edit or operational fields not relevant to
 * content versioning.
 */
class QrCodeRevisionObserver
{
    /** Fields that are tracked in revision snapshots. */
    private const TRACKABLE = ['title', 'content', 'settings', 'status'];

    public function updating(QrCode $qrCode): void
    {
        // Only snapshot if at least one trackable field is actually dirty.
        $dirty = array_keys($qrCode->getDirty());
        $tracked = array_intersect($dirty, self::TRACKABLE);

        if (empty($tracked)) {
            return;
        }

        // Build snapshot from the ORIGINAL (pre-update) values.
        $originalSnapshot = [];
        foreach (self::TRACKABLE as $field) {
            $originalSnapshot[$field] = $qrCode->getOriginal($field);
        }

        // Build the "new" state for change summary.
        $newState = [];
        foreach (self::TRACKABLE as $field) {
            $newState[$field] = $qrCode->getAttribute($field);
        }

        // Compute next version number.
        $lastVersion = QrCodeRevision::where('qr_code_id', $qrCode->id)
            ->max('version') ?? 0;

        QrCodeRevision::create([
            'qr_code_id' => $qrCode->id,
            'user_id' => auth()->id(),
            'version' => $lastVersion + 1,
            'snapshot' => $originalSnapshot,
            'change_summary' => QrCodeRevision::summarizeChanges($originalSnapshot, $newState),
        ]);

        // Enforce the 50-revision cap: delete oldest beyond 50.
        $this->trimRevisions($qrCode->id);
    }

    private function trimRevisions(int $qrCodeId): void
    {
        $count = QrCodeRevision::where('qr_code_id', $qrCodeId)->count();

        if ($count <= 50) {
            return;
        }

        $excess = $count - 50;

        QrCodeRevision::where('qr_code_id', $qrCodeId)
            ->orderBy('version')
            ->limit($excess)
            ->delete();
    }
}
