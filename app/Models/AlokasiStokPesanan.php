<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlokasiStokPesanan extends Model
{
    protected $table = 'alokasi_stok_pesanan';

    public const UPDATED_AT = null;

    protected $fillable = [
        'detail_pesanan_id',
        'batch_stok_id',
        'jumlah',
        'dikembalikan_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'dikembalikan_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function detailPesanan(): BelongsTo
    {
        return $this->belongsTo(DetailPesanan::class, 'detail_pesanan_id');
    }

    public function batchStok(): BelongsTo
    {
        return $this->belongsTo(BatchStok::class, 'batch_stok_id');
    }
}
