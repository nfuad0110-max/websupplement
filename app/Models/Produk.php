<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    use SoftDeletes;

    protected $table = 'produk';

    protected $fillable = [
        'kategori_id', 'kode_produk', 'nama_produk', 'slug', 'deskripsi',
        'harga', 'gambar', 'isi_kemasan', 'aturan_pakai',
        'batas_minimum_stok', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'batas_minimum_stok' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function batchStok(): HasMany
    {
        return $this->hasMany(BatchStok::class, 'produk_id');
    }

    public function detailPesanan(): HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'produk_id');
    }
}
