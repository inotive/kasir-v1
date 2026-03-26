<?php

return [
    'categories' => [
        'quickstart' => [
            'label' => 'Mulai Cepat (Hari Pertama)',
            'description' => 'Panduan paling penting untuk mulai operasional tanpa bingung.',
            'order' => 10,
        ],
        'pos' => [
            'label' => 'Penjualan di Kasir (POS)',
            'description' => 'Cara transaksi, cetak struk, koreksi, dan praktik aman di kasir.',
            'order' => 20,
        ],
        'corrections' => [
            'label' => 'Refund / Void (Retur & Pembatalan)',
            'description' => 'Standar pembatalan/refund yang aman dan bisa diaudit.',
            'order' => 30,
        ],
        'finance' => [
            'label' => 'Laporan & Analisa Keuangan (Owner)',
            'description' => 'Cara membaca angka untuk ambil keputusan (menu, promo, biaya, stok).',
            'order' => 40,
        ],
        'inventory' => [
            'label' => 'Stok & Inventory',
            'description' => 'Pembelian, stock opname, kartu stok, dan dampaknya ke HPP.',
            'order' => 50,
        ],
        'promo' => [
            'label' => 'Voucher, Diskon, dan Promo',
            'description' => 'Atur promo tanpa mengacaukan omzet dan margin.',
            'order' => 60,
        ],
        'kitchen' => [
            'label' => 'Dapur & Kitchen Queue',
            'description' => 'Alur order masuk dapur dan cara mengurangi salah saji.',
            'order' => 70,
        ],
        'printer' => [
            'label' => 'Printer & Struk',
            'description' => 'Solusi cepat masalah struk tidak keluar dan setting printer.',
            'order' => 80,
        ],
        'security' => [
            'label' => 'Pengguna, Role, dan Hak Akses',
            'description' => 'Kontrol akses untuk mencegah kebocoran data dan fraud.',
            'order' => 90,
        ],
        'faq' => [
            'label' => 'FAQ & Troubleshooting',
            'description' => 'Jawaban singkat untuk masalah paling sering.',
            'order' => 100,
        ],
    ],

    'articles' => [
        [
            'slug' => 'mulai-cepat-hari-pertama',
            'title' => 'Mulai Cepat: Hari Pertama Operasional',
            'category' => 'quickstart',
            'order' => 10,
            'featured' => true,
            'summary' => 'Checklist paling penting untuk mulai jualan tanpa bingung.',
            'file' => 'mulai-cepat-hari-pertama.md',
        ],
        [
            'slug' => 'sop-harian',
            'title' => 'SOP Ringkas: Operasional Harian',
            'category' => 'quickstart',
            'order' => 20,
            'featured' => true,
            'summary' => 'Langkah pagi–operasional–tutup kas agar kas dan stok rapi.',
            'file' => 'sop-harian.md',
        ],
        [
            'slug' => 'refund-void-dan-aturan-aman',
            'title' => 'Refund & Void: Kapan Dipakai dan Aturan Aman',
            'category' => 'corrections',
            'order' => 10,
            'featured' => true,
            'summary' => 'Cara koreksi transaksi yang aman dan bisa diaudit.',
            'file' => 'refund-void-dan-aturan-aman.md',
        ],
        [
            'slug' => 'membaca-dashboard-dan-laporan',
            'title' => 'Cara Membaca Dashboard & Laporan (Owner)',
            'category' => 'finance',
            'order' => 10,
            'featured' => true,
            'summary' => 'Urutan baca angka dan tindakan bisnis yang bisa langsung dilakukan.',
            'file' => 'membaca-dashboard-dan-laporan.md',
        ],
        [
            'slug' => 'glosarium-angka-finansial',
            'title' => 'Glosarium Angka Finansial: Omzet, HPP, Laba',
            'category' => 'finance',
            'order' => 20,
            'featured' => true,
            'summary' => 'Definisi singkat agar semua tim membaca angka dengan bahasa yang sama.',
            'file' => 'glosarium-angka-finansial.md',
        ],
        [
            'slug' => 'printer-struk-troubleshooting',
            'title' => 'Printer Struk: Troubleshooting Cepat',
            'category' => 'printer',
            'order' => 10,
            'featured' => false,
            'file' => 'printer-struk-troubleshooting.md',
        ],
    ],
];
