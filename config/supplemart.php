<?php

// Pengaturan toko. Rekening dan WhatsApp diisi pemilik usaha lewat .env atau tabel pengaturan
return [
    'name'    => 'Alrisha Health Care',
    'address' => 'Swan Menganti Park Residence, Gresik',

    // Format internasional tanpa tanda +, contoh: 6281234567801
    'whatsapp' => env('SUPPLEMART_WA', '6281200000000'),

    'bank' => [
        'name'   => env('SUPPLEMART_BANK', 'BCA'),
        'number' => env('SUPPLEMART_ACCOUNT', '1234567890'),
        'holder' => env('SUPPLEMART_HOLDER', 'Alrisha Health Care'),
    ],

    'low_stock'              => 10, // stok <= angka ini ditandai "menipis"
    'near_expiry_days'       => 90, // kurang dari angka ini ditandai "hampir kedaluwarsa"
    'payment_deadline_hours' => 24, // batas waktu bayar pesanan dalam jam
];
