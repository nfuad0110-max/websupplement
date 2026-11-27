<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPesanan extends Model
{
    protected $table = 'detail_pesanan';

    protected $fillable = [
        'pesanan_id',
        'produk_id',
        'nama_produk',
        'harga_satuan',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'jumlah' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function alokasiStok(): HasMany
    {
        return $this->hasMany(AlokasiStokPesanan::class, 'detail_pesanan_id');
    }
}
