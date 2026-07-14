<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCodeRoute extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'qr_code_routes';

    protected $fillable = [
        'qr_code_id',
        'code',
        'alias',
        'host',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'qr_code_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
