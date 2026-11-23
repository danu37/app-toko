<?php

return [
    'name' => env('STORE_NAME', 'TokoKita'),
    'tagline' => env('STORE_TAGLINE', 'Layanan Kasir & Kebutuhan Harian'),
    'address' => env('STORE_ADDRESS'),
    'phone' => env('STORE_PHONE'),
    'hours' => env('STORE_HOURS', 'Buka Setiap Hari 08.00 - 21.00 WIB'),
    'logo_url' => env('STORE_LOGO_URL'),

    'qris' => [
        'enabled' => env('QRIS_ENABLED', true),
        'merchant_name' => env('QRIS_MERCHANT_NAME', 'Danu Anggo, Pakaian'),
        'nmid' => env('QRIS_NMID', 'ID1026645584610'),
        'terminal_code' => env('QRIS_TERMINAL_CODE', 'A01'),
        'image_url' => '/images/qris/qris-danu-anggo.jpg',
        'code_url' => '/images/qris/qris-code-only.png',
        'aspi_url' => 'https://www.aspi-qris.id',
        'printed_by' => '93600915',
        'print_version' => '1.0.23.11.26',
        'supported_apps' => [
            'BCA',
            'Livin by Mandiri',
            'BRImo',
            'BNI Mobile',
            'BSI Mobile',
            'GoPay',
            'OVO',
            'DANA',
            'ShopeePay',
            'LinkAja',
        ],
    ],
];
