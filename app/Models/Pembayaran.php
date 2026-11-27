<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_VALID = 'Valid';
    public const STATUS_DITOLAK = 'Ditolak';

    protected $table = 'pembayaran';

    protected $fillable = [
        'pesanan_id',
        'jumlah_transfer',
        'metode',
        'status_verifikasi',
        'catatan_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_transfer' => 'decimal:2',
            'diverifikasi_at' => 'datetime',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
