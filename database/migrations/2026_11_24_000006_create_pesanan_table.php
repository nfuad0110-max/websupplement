<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pesanan', 25)->unique('uq_pesanan_kode');
            $table->string('nama_pemesan', 100);
            $table->string('no_whatsapp', 20);
            $table->text('alamat_pengiriman');
            $table->string('catatan_pembeli', 255)->nullable();
            $table->decimal('total_produk', 14, 2)->default(0);
            $table->decimal('ongkos_kirim', 12, 2)->default(0);
            $table->decimal('total', 14, 2)->storedAs('total_produk + ongkos_kirim');
            $table->enum('status', [
                'Menunggu pembayaran', 'Dibayar', 'Dikirim', 'Selesai', 'Dibatalkan'
            ])->default('Menunggu pembayaran');
            $table->dateTime('batas_waktu_bayar')->nullable();
            $table->dateTime('dibayar_at')->nullable();
            $table->dateTime('dikirim_at')->nullable();
            $table->dateTime('selesai_at')->nullable();
            $table->dateTime('dibatalkan_at')->nullable();
            $table->string('alasan_pembatalan', 255)->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')
                ->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_pesanan_status');
            $table->index(['status', 'dibayar_at'], 'idx_pesanan_rekap');
            $table->index(['status', 'batas_waktu_bayar'], 'idx_pesanan_batas_bayar');
            $table->index('no_whatsapp', 'idx_pesanan_wa');
        });

        DB::statement('ALTER TABLE pesanan
            ADD CONSTRAINT chk_pesanan_total
            CHECK (total_produk >= 0 AND ongkos_kirim >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
