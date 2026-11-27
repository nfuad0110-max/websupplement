<?php

namespace App\Providers;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            if (! Schema::hasTable('pengaturan')) {
                return;
            }

            $settings = Pengaturan::query()->pluck('nilai', 'kunci');

            $map = [
                'nama_toko' => 'supplemart.name',
                'alamat_toko' => 'supplemart.address',
                'no_whatsapp' => 'supplemart.whatsapp',
                'bank_nama' => 'supplemart.bank.name',
                'bank_rekening' => 'supplemart.bank.number',
                'bank_atas_nama' => 'supplemart.bank.holder',
                'batas_stok_menipis' => 'supplemart.low_stock',
                'batas_hampir_kedaluwarsa' => 'supplemart.near_expiry_days',
                'batas_waktu_bayar_jam' => 'supplemart.payment_deadline_hours',
            ];

            foreach ($map as $kunci => $configKey) {
                if (isset($settings[$kunci]) && $settings[$kunci] !== '') {
                    config([$configKey => is_numeric($settings[$kunci]) && str_starts_with($kunci, 'batas_')
                        ? (int) $settings[$kunci]
                        : $settings[$kunci]]);
                }
            }
        } catch (Throwable) {
            // Biarkan fallback ke config/supplemart.php jika DB belum siap
        }
    }
}
