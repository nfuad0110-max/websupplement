<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = null;

    protected $fillable = [
        'kunci',
        'nilai',
        'keterangan',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::query()->find($key);

        return $row?->nilai ?? $default;
    }

    public static function setValue(string $key, string $value, ?string $keterangan = null): self
    {
        return static::query()->updateOrCreate(
            ['kunci' => $key],
            array_filter([
                'nilai' => $value,
                'keterangan' => $keterangan,
            ], fn ($v) => $v !== null)
        );
    }
}
