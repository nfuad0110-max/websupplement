<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Produk extends Model
{
    use SoftDeletes;

    protected $table = 'produk';

    protected $fillable = [
        'kategori_id',
        'kode_produk',
        'nama_produk',
        'slug',
        'deskripsi',
        'harga',
        'gambar',
        'isi_kemasan',
        'aturan_pakai',
        'batas_minimum_stok',
        'is_active',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithStockSummary(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->with('kategori')
            ->withSum([
                'batchStok as stok_tersedia' => fn (Builder $q) => $q
                    ->where('sisa', '>', 0)
                    ->whereDate('tanggal_kedaluwarsa', '>', $today),
            ], 'sisa')
            ->withMin([
                'batchStok as kedaluwarsa_terdekat' => fn (Builder $q) => $q
                    ->where('sisa', '>', 0)
                    ->whereDate('tanggal_kedaluwarsa', '>', $today),
            ], 'tanggal_kedaluwarsa');
    }

    public function availableStock(): int
    {
        if (array_key_exists('stok_tersedia', $this->attributes)) {
            return (int) ($this->attributes['stok_tersedia'] ?? 0);
        }

        return (int) $this->batchStok()
            ->where('sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '>', now()->toDateString())
            ->sum('sisa');
    }

    public function nearestExpiry(): ?string
    {
        if (array_key_exists('kedaluwarsa_terdekat', $this->attributes)) {
            return $this->attributes['kedaluwarsa_terdekat'];
        }

        return $this->batchStok()
            ->where('sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '>', now()->toDateString())
            ->min('tanggal_kedaluwarsa');
    }

    public function bottleColor(): string
    {
        $palette = ['#F4A340', '#DBB21F', '#DB5A8C', '#6F8799', '#8E6BBF', '#4C9F3A', '#2F7FB5', '#21A79B'];

        return $palette[($this->id ? ($this->id - 1) : 0) % count($palette)];
    }

    public function imageUrl(): ?string
    {
        if (! $this->gambar) {
            return null;
        }

        if (str_starts_with($this->gambar, 'http://') || str_starts_with($this->gambar, 'https://')) {
            return $this->gambar;
        }

        return Storage::disk('public')->exists($this->gambar)
            ? Storage::disk('public')->url($this->gambar)
            : null;
    }

    /**
     * @return array{key:string,text:string}
     */
    public static function stockStatus(int $stock, ?int $threshold = null): array
    {
        $limit = $threshold ?? (int) config('supplemart.low_stock', 10);
        if ($stock <= 0) {
            return ['key' => 'habis', 'text' => 'Stok habis'];
        }
        if ($stock <= $limit) {
            return ['key' => 'menipis', 'text' => "Menipis · sisa {$stock}"];
        }

        return ['key' => 'tersedia', 'text' => "Tersedia · {$stock}"];
    }

    /**
     * Format data untuk tampilan Blade katalog/detail/keranjang/admin.
     *
     * @return array<string, mixed>
     */
    public function toViewArray(): array
    {
        $stock = $this->availableStock();

        return [
            'id' => $this->id,
            'kode_produk' => $this->kode_produk,
            'slug' => $this->slug,
            'name' => $this->nama_produk,
            'category' => $this->kategori?->nama_kategori ?? 'Lainnya',
            'kategori_id' => $this->kategori_id,
            'price' => (float) $this->harga,
            'stock' => $stock,
            'min_stock' => (int) $this->batas_minimum_stok,
            'expiry' => $this->nearestExpiry(),
            'color' => $this->bottleColor(),
            'image_url' => $this->imageUrl(),
            'content' => $this->isi_kemasan ?: '1 kemasan',
            'usage' => $this->aturan_pakai ?: 'Ikuti petunjuk pada kemasan',
            'description' => $this->deskripsi ?: '',
            'is_active' => (bool) $this->is_active,
        ];
    }
}
