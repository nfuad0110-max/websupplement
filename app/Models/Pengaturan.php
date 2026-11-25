<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';
    protected $primaryKey = 'kunci';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['kunci', 'nilai', 'keterangan', 'updated_at'];

    protected function casts(): array
    {
        return ['updated_at' => 'datetime'];
    }
}
