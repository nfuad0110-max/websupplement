<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchStok extends Model
{
    protected $table = 'batch_stok';

    protected $fillable = [
        'produk_id',
        'kode_batch',
        'jumlah_masuk',
        'sisa',
        'harga_beli',
        'supplier',
        'tanggal_masuk',
        'tanggal_kedaluwarsa',
        'dicatat_oleh',
        'catatan',
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

    public function alokasiStok(): HasMany
    {
        return $this->hasMany(AlokasiStokPesanan::class, 'batch_stok_id');
    }

    /**
     * @return array{days:int,state:array{key:string,text:string}}
     */
    public function statusInfo(): array
    {
        $near = (int) config('supplemart.near_expiry_days', 90);
        $today = Carbon::today();
        $expiry = Carbon::parse($this->tanggal_kedaluwarsa)->startOfDay();
        $days = (int) $today->diffInDays($expiry, false);

        $state = match (true) {
            $this->sisa === 0 => ['key' => 'terjual', 'text' => 'Terjual habis'],
            $days < 0 => ['key' => 'kedaluwarsa', 'text' => 'Kedaluwarsa'],
            $days <= 30 => ['key' => 'segera', 'text' => "{$days} hari lagi"],
            $days < $near => ['key' => 'hampir', 'text' => "{$days} hari lagi"],
            default => ['key' => 'aman', 'text' => 'Aman'],
        };

        return [
            'days' => $days,
            'state' => $state,
        ];
    }

    /**
     * @param  array<int, string>  $prioritizedCodes
     * @return array<string, mixed>
     */
    public function toViewArray(array $prioritizedCodes = []): array
    {
        $info = $this->statusInfo();
        $code = $this->kode_batch ?: ('B-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT));

        return [
            'id' => $this->id,
            'code' => $code,
            'product' => [
                'id' => $this->produk?->id,
                'name' => $this->produk?->nama_produk ?? '-',
            ],
            'received' => Carbon::parse($this->tanggal_masuk)->toDateString(),
            'in' => (int) $this->jumlah_masuk,
            'left' => (int) $this->sisa,
            'expiry' => Carbon::parse($this->tanggal_kedaluwarsa)->toDateString(),
            'days' => $info['days'],
            'state' => $info['state'],
            'prioritized' => in_array($code, $prioritizedCodes, true),
        ];
    }
}
