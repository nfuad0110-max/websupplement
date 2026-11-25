<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('riwayat_status_pesanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('status_lama', [
                'Menunggu pembayaran', 'Dibayar', 'Dikirim', 'Selesai', 'Dibatalkan'
            ])->nullable();
            $table->enum('status_baru', [
                'Menunggu pembayaran', 'Dibayar', 'Dikirim', 'Selesai', 'Dibatalkan'
            ]);
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')
                ->cascadeOnUpdate()->nullOnDelete();
            $table->string('catatan', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['pesanan_id', 'created_at'], 'idx_riwayat_pesanan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_pesanan');
    }
};
