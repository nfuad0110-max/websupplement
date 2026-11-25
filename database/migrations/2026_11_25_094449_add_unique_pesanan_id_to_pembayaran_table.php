<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->unique('pesanan_id', 'uq_pembayaran_pesanan');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropUnique('uq_pembayaran_pesanan');
        });
    }
};
