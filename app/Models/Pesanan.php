<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pesanan extends Model
{
    protected $table = 'pesanan';

    protected $fillable = [
        'kode_pesanan', 'nama_pemesan', 'no_whatsapp', 'alamat_pengiriman',
        'catatan_pembeli', 'total_produk', 'ongkos_kirim', 'status',
        'batas_waktu_bayar', 'dibayar_at', 'dikirim_at', 'selesai_at',
        'dibatalkan_at', 'alasan_pembatalan', 'diproses_oleh',
    ];

    protected function casts(): array
    {
        return [
            'total_produk' => 'decimal:2',
            'ongkos_kirim' => 'decimal:2',
            'total' => 'decimal:2',
            'batas_waktu_bayar' => 'datetime',
            'dibayar_at' => 'datetime',
            'dikirim_at' => 'datetime',
            'selesai_at' => 'datetime',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function adminPemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function detailPesanan(): HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'pesanan_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'pesanan_id');
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPesanan::class, 'pesanan_id');
    }
}
