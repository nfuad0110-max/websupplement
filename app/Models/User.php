<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function batchStokDicatat()
    {
        return $this->hasMany(BatchStok::class, 'dicatat_oleh');
    }

    public function pesananDiproses()
    {
        return $this->hasMany(Pesanan::class, 'diproses_oleh');
    }

    public function pembayaranDiverifikasi()
    {
        return $this->hasMany(Pembayaran::class, 'diverifikasi_oleh');
    }

    public function riwayatStatusDiubah()
    {
        return $this->hasMany(RiwayatStatusPesanan::class, 'diubah_oleh');
    }
}
