<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pesanan extends Model
{
    public const STATUS_MENUNGGU = 'Menunggu pembayaran';
    public const STATUS_DIBAYAR = 'Dibayar';
    public const STATUS_DIKIRIM = 'Dikirim';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DIBATALKAN = 'Dibatalkan';

    protected $table = 'pesanan';

    protected $fillable = [
        'kode_pesanan',
        'nama_pemesan',
        'no_whatsapp',
        'alamat_pengiriman',
        'catatan_pembeli',
        'total_produk',
        'ongkos_kirim',
        'status',
        'batas_waktu_bayar',
        'dibayar_at',
        'dikirim_at',
        'selesai_at',
        'dibatalkan_at',
        'alasan_pembatalan',
        'diproses_oleh',
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

    public function details(): HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'pesanan_id');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'pesanan_id');
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPesanan::class, 'pesanan_id');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public static function keyFromStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_MENUNGGU => 'menunggu',
            self::STATUS_DIBAYAR => 'dibayar',
            self::STATUS_DIKIRIM => 'dikirim',
            self::STATUS_SELESAI => 'selesai',
            self::STATUS_DIBATALKAN => 'dibatalkan',
            default => 'menunggu',
        };
    }

    public static function statusFromKey(?string $key): ?string
    {
        return match ($key) {
            'menunggu' => self::STATUS_MENUNGGU,
            'dibayar' => self::STATUS_DIBAYAR,
            'dikirim' => self::STATUS_DIKIRIM,
            'selesai' => self::STATUS_SELESAI,
            'dibatalkan' => self::STATUS_DIBATALKAN,
            default => null,
        };
    }

    public function statusKey(): string
    {
        return self::keyFromStatus($this->status);
    }
}
