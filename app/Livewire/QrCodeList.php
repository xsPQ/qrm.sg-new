<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\QrCodeType;
use App\Models\QrCode;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * QR-Code list for the authenticated user's dashboard (Pflichtenheft §3.6.1,
 * P2-T01). Shows only the user's own codes — enforced by scoping the query to
 * the authenticated user's id, mirroring the owner policy (P1-T15).
 *
 * Supports case-insensitive search over code/alias/title, a type filter,
 * optional status filter, 25-per-page pagination and status badges
 * (active / expired / burned). Each row links to the detail page
 * (P2-T02) and exposes Edit / Delete actions that route to the type-specific
 * editor (P2-T03), whose danger zone performs the confirmed soft-delete.
 */
class QrCodeList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    /** @var int Qr codes shown per page (Pflichtenheft §3.6.1). */
    public int $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => '', 'as' => 'type'],
        'statusFilter' => ['except' => '', 'as' => 'status'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * Distinct QR types offered in the type filter.
     * Only shows types that users can actually create (excludes legacy
     * types text/email/phone/sms/vcard that have no creator support).
     *
     * @return array<string,string>
     */
    public function typeOptions(): array
    {
        $creatorTypes = [
            QrCodeType::Url,
            QrCodeType::Message,
            QrCodeType::Redirect,
            QrCodeType::Social,
            QrCodeType::Wifi,
            QrCodeType::Crypto,
            QrCodeType::Event,
            QrCodeType::Vcard,
        ];

        $options = [];
        foreach ($creatorTypes as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }

    /**
     * Statuses offered in the status filter.
     *
     * @return array<string,string>
     */
    public function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'expired' => 'Expired',
            'burned' => 'Burned',
        ];
    }

    /**
     * User-visible effective status for the badge. Burned (consumed) and
     * date-expired codes are surfaced truthfully even before the scheduled
     * cleanup flips the stored `status` column.
     */
    public function effectiveStatus(QrCode $qrCode): string
    {
        if ($qrCode->isBurned()) {
            return 'burned';
        }

        if ($qrCode->isExpired()) {
            return 'expired';
        }

        return $qrCode->status;
    }

    /**
     * Tailwind badge classes for a given effective status.
     */
    public function badgeClasses(string $status): string
    {
        return match ($status) {
            'active' => 'bg-green-100 text-green-800 ring-green-600/20',
            'expired' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
            'burned' => 'bg-red-100 text-red-800 ring-red-600/20',
            'disabled' => 'bg-gray-100 text-gray-600 ring-gray-500/20',
            default => 'bg-gray-100 text-gray-600 ring-gray-500/20',
        };
    }

    public function render()
    {
        $term = $this->escapeLike(mb_strtolower(trim($this->search)));

        $qrCodes = QrCode::query()
            ->where('user_id', auth()->id())
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($q) use ($term): void {
                    $q->whereRaw('LOWER(title) LIKE ? ESCAPE \'\\\'', ["%{$term}%"])
                        ->orWhereHas('route', function ($r) use ($term): void {
                            $r->whereRaw('LOWER(code) LIKE ? ESCAPE \'\\\'', ["%{$term}%"])
                                ->orWhereRaw('LOWER(alias) LIKE ? ESCAPE \'\\\'', ["%{$term}%"]);
                        });
                });
            })
            ->when($this->typeFilter !== '', function ($query): void {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->statusFilter !== '', function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->with('route')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.qr-code-list', ['qrCodes' => $qrCodes]);
    }

    /**
     * Escape LIKE wildcard characters so user input is matched literally.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
