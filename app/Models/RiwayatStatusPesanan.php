<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusPesanan extends Model
{
    protected $table = 'riwayat_status_pesanan';
    public $timestamps = false;

    protected $fillable = [
        'pesanan_id', 'status_lama', 'status_baru', 'diubah_oleh',
        'catatan', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
