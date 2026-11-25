<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchStok extends Model
{
    protected $table = 'batch_stok';

    protected $fillable = [
        'produk_id', 'kode_batch', 'jumlah_masuk', 'sisa', 'harga_beli',
        'supplier', 'tanggal_masuk', 'tanggal_kedaluwarsa',
        'dicatat_oleh', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_masuk' => 'integer',
            'sisa' => 'integer',
            'harga_beli' => 'decimal:2',
            'tanggal_masuk' => 'date',
            'tanggal_kedaluwarsa' => 'date',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function alokasiStokPesanan(): HasMany
    {
        return $this->hasMany(AlokasiStokPesanan::class, 'batch_stok_id');
    }
}
