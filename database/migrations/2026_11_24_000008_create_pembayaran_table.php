<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pesanan_id')
                ->constrained('pesanan')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->decimal('jumlah_transfer', 14, 2);

            $table->string('metode', 30)->default('Transfer bank');

            $table->enum('status_verifikasi', ['Menunggu', 'Valid', 'Ditolak'])
                ->default('Menunggu');

            $table->string('catatan_verifikasi', 255)->nullable();

            $table->foreignId('diverifikasi_oleh')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->dateTime('diverifikasi_at')->nullable();

            $table->timestamps();

            $table->index(
                ['pesanan_id', 'status_verifikasi'],
                'idx_pembayaran_pesanan'
            );
        });

        DB::statement('ALTER TABLE pembayaran
            ADD CONSTRAINT chk_pembayaran_jumlah CHECK (jumlah_transfer > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
