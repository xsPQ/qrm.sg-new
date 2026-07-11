<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Entitlement\EntitlementGate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QrCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $snapshot = $this->entitlementSnapshot();
        $gate = app(EntitlementGate::class);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'content' => $this->content,
            'status' => $this->status,
            'scan_count' => $this->scan_count,
            'max_scans' => $this->max_scans,
            'burn' => $this->burn,
            'expires_at' => $this->expires_at?->toIso8601String(),
            // Ablauf-Prompt/Badge (Pflichtenheft §2.4): whether the code is in
            // the 3-day pre-expiry warning window and how many days remain.
            'is_expiring_soon' => $this->isExpiringSoon(),
            'expires_in_days' => $this->expiresInDays(),
            'is_password_protected' => $this->password_hash !== null,
            'settings' => $this->settings,
            'entitlement_snapshot' => $this->entitlement_snapshot,
            // Feature-gate flags (P2-T07) derived from this code's own
            // (immutable/grandfathered) snapshot, so the Edit UI and API
            // consumers can render locked fields deterministically.
            'features' => $gate->featureFlags($snapshot),
            'route' => $this->whenLoaded('route', fn () => [
                'code' => $this->route->code,
                'alias' => $this->route->alias,
                'host' => $this->route->host,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
