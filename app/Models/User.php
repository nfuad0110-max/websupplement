<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function batchStok(): HasMany
    {
        return $this->hasMany(BatchStok::class, 'dicatat_oleh');
    }

    public function pesananDiproses(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'diproses_oleh');
    }

    public function pembayaranDiverifikasi(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'diverifikasi_oleh');
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPesanan::class, 'diubah_oleh');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
