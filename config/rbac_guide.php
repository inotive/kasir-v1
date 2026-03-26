<?php

return [
    'permissions' => [
        'dashboard.access' => [
            'summary' => 'Memberi akses masuk ke dashboard aplikasi.',
            'grants' => [
                'Melihat halaman Dashboard',
            ],
            'not_grants' => [
                'Akses modul lain (POS, transaksi, inventory) tanpa permission terkait',
            ],
            'affected_areas' => [
                'Dashboard',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => false,
            ],
            'related_permissions' => [],
        ],

        'pos.access' => [
            'summary' => 'Memberi akses ke UI POS (Kasir) untuk membuat dan memproses pesanan.',
            'grants' => [
                'Membuka halaman POS',
                'Menambah item ke cart dan membuat transaksi POS (pending/checkout)',
            ],
            'not_grants' => [
                'Memberi diskon manual tanpa izin akses melakukan Diskon Manual',
                'Melihat detail transaksi/pending via scan atau load tanpa Izin akses melihat detail transaksi',
                'Cetak struk tanpa Izin akses mencetak struk',
                'Void/refund tanpa Izin akses koreksi transaksi',
                'Melihat PII pelanggan tanpa Izin akses melihat Data pelanggan',
            ],
            'affected_areas' => [
                'Kasir (POS)',
                'Checkout POS',
                'Simpan pending POS',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'discounts.manual.apply',
                'transactions.view',
                'transactions.details',
                'transactions.print',
                'transactions.pii.view',
                'members.view',
                'members.pii.view',
            ],
        ],

        'guides.view' => [
            'summary' => 'Memberi akses ke modul Buku Panduan (panduan operasional & penjelasan istilah).',
            'grants' => [
                'Membuka menu Buku Panduan',
                'Membaca artikel panduan',
                'Mencari artikel panduan',
                'Mengunduh panduan dalam bentuk PDF',
            ],
            'not_grants' => [
                'Mengubah data transaksi/produk/laporan tanpa permission modul terkait',
                'Mengakses data sensitif pelanggan',
            ],
            'affected_areas' => [
                'Buku Panduan',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'Akses Dashboard',
            ],
        ],

        'products.view' => [
            'summary' => 'Mengizinkan melihat daftar dan detail produk.',
            'grants' => [
                'Melihat data produk',
            ],
            'not_grants' => [
                'Menambah/mengubah/menghapus produk tanpa permission create/edit/delete',
            ],
            'affected_areas' => [
                'Produk',
                'POS (daftar produk tampil, namun tetap butuh pos.access untuk akses POS)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'products.create',
                'products.edit',
                'products.delete',
            ],
        ],
        'products.create' => [
            'summary' => 'Mengizinkan membuat produk baru.',
            'grants' => [
                'Menambah produk',
            ],
            'not_grants' => [
                'Mengubah/menghapus produk tanpa permission terkait',
            ],
            'affected_areas' => [
                'Produk',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'products.view',
                'products.edit',
            ],
        ],
        'products.edit' => [
            'summary' => 'Mengizinkan mengubah data produk.',
            'grants' => [
                'Mengubah produk (nama, harga/varian, ketersediaan, paket, dll sesuai form yang ada)',
            ],
            'not_grants' => [
                'Menghapus produk tanpa products.delete',
            ],
            'affected_areas' => [
                'Produk',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'products.view',
                'products.create',
                'products.delete',
            ],
        ],
        'products.delete' => [
            'summary' => 'Mengizinkan menghapus produk.',
            'grants' => [
                'Menghapus produk',
            ],
            'not_grants' => [
                'Mengubah produk tanpa products.edit',
            ],
            'affected_areas' => [
                'Produk',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'products.view',
                'products.edit',
            ],
        ],

        'categories.view' => [
            'summary' => 'Mengizinkan melihat daftar kategori produk.',
            'grants' => [
                'Melihat kategori',
            ],
            'not_grants' => [
                'Menambah/mengubah/menghapus kategori tanpa permission terkait',
            ],
            'affected_areas' => [
                'Kategori',
                'POS (filter kategori)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'categories.create',
                'categories.edit',
                'categories.delete',
            ],
        ],
        'categories.create' => [
            'summary' => 'Mengizinkan membuat kategori produk.',
            'grants' => [
                'Menambah kategori',
            ],
            'not_grants' => [
                'Mengubah/menghapus kategori tanpa permission terkait',
            ],
            'affected_areas' => [
                'Kategori',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'categories.view',
                'categories.edit',
            ],
        ],
        'categories.edit' => [
            'summary' => 'Mengizinkan mengubah kategori produk.',
            'grants' => [
                'Mengubah kategori',
            ],
            'not_grants' => [
                'Menghapus kategori tanpa categories.delete',
            ],
            'affected_areas' => [
                'Kategori',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'categories.view',
                'categories.create',
                'categories.delete',
            ],
        ],
        'categories.delete' => [
            'summary' => 'Mengizinkan menghapus kategori produk.',
            'grants' => [
                'Menghapus kategori',
            ],
            'not_grants' => [
                'Mengubah kategori tanpa categories.edit',
            ],
            'affected_areas' => [
                'Kategori',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'categories.view',
                'categories.edit',
            ],
        ],

        'transactions.view' => [
            'summary' => 'Mengizinkan melihat daftar transaksi dan daftar pesanan/pending yang ditampilkan oleh sistem.',
            'grants' => [
                'Melihat daftar transaksi',
                'Melihat daftar pesanan pending pada modul tertentu (mis. inbox POS/self-order) sesuai implementasi',
            ],
            'not_grants' => [
                'Melihat detail transaksi tanpa transactions.details',
                'Melihat PII pelanggan tanpa transactions.pii.view',
                'Melakukan void/refund tanpa permission koreksi',
            ],
            'affected_areas' => [
                'Transaksi (daftar)',
                'POS (pending orders list)',
                'Self Order (inbox list)',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'transactions.details',
                'transactions.pii.view',
                'transactions.print',
                'transactions.void',
                'transactions.refund',
            ],
        ],
        'transactions.details' => [
            'summary' => 'Mengizinkan melihat detail transaksi (item, nominal, status, dan informasi terkait).',
            'grants' => [
                'Membuka halaman detail transaksi',
                'Memuat transaksi pending tertentu pada POS (load/scan) sesuai implementasi',
            ],
            'not_grants' => [
                'Mengubah status transaksi (void/refund) tanpa permission terkait',
                'Melihat PII pelanggan tanpa transactions.pii.view (sebagian data dapat dimasking)',
            ],
            'affected_areas' => [
                'Transaksi (detail)',
                'POS (load pending / scan kode transaksi)',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'transactions.view',
                'transactions.pii.view',
                'transactions.print',
            ],
        ],
        'transactions.pii.view' => [
            'summary' => 'Mengizinkan melihat data sensitif pelanggan pada konteks transaksi (mis. nama pelanggan jika dimasking).',
            'grants' => [
                'Melihat informasi pelanggan pada transaksi sesuai field yang dianggap PII di sistem',
            ],
            'not_grants' => [
                'Memberi akses transaksi tanpa transactions.view/details',
            ],
            'affected_areas' => [
                'Transaksi (detail)',
                'Payload cetak/print transaksi (customer name tidak dimasking)',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'transactions.details',
                'members.pii.view',
            ],
        ],
        'transactions.print' => [
            'summary' => 'Mengizinkan mencetak struk transaksi dan melakukan proses cetak terkait order.',
            'grants' => [
                'Cetak struk transaksi',
                'Menjalankan aksi cetak tertentu dari POS/self-order processing sesuai implementasi',
            ],
            'not_grants' => [
                'Melihat PII pelanggan tanpa transactions.pii.view (PII dapat dimasking pada payload)',
                'Void/refund tanpa permission koreksi transaksi',
            ],
            'affected_areas' => [
                'Transaksi (print)',
                'POS (print setelah checkout/pending)',
                'Self Order (mark processed untuk midtrans/cash sesuai implementasi)',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'transactions.view',
                'transactions.details',
                'transactions.pii.view',
            ],
        ],
        'transactions.void' => [
            'summary' => 'Mengizinkan melakukan void transaksi (pembatalan) sesuai kebijakan sistem.',
            'grants' => [
                'Void transaksi (membatalkan transaksi) pada fitur yang menyediakan aksi void',
                'Menghapus pesanan pending tertentu yang dianggap void dalam POS sesuai implementasi',
            ],
            'not_grants' => [
                'Approval void tanpa transactions.void.approve (jika sistem meminta approval)',
            ],
            'affected_areas' => [
                'Transaksi (void)',
                'POS (hapus pending)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'transactions.void.approve',
                'transactions.view',
                'transactions.details',
            ],
        ],
        'transactions.refund' => [
            'summary' => 'Mengizinkan melakukan refund transaksi sesuai kebijakan sistem.',
            'grants' => [
                'Refund transaksi pada fitur yang menyediakan refund',
            ],
            'not_grants' => [
                'Approval refund tanpa transactions.refund.approve (jika sistem meminta approval)',
            ],
            'affected_areas' => [
                'Transaksi (refund)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'transactions.refund.approve',
                'transactions.view',
                'transactions.details',
            ],
        ],
        'transactions.void.approve' => [
            'summary' => 'Mengizinkan menyetujui (approve) tindakan void transaksi.',
            'grants' => [
                'Menyetujui permintaan void transaksi pada alur approval yang tersedia',
            ],
            'not_grants' => [
                'Melakukan void tanpa transactions.void (jika dipisah per SOP)',
            ],
            'affected_areas' => [
                'Approval koreksi transaksi',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'transactions.void',
                'transactions.view',
                'transactions.details',
            ],
        ],
        'transactions.refund.approve' => [
            'summary' => 'Mengizinkan menyetujui (approve) tindakan refund transaksi.',
            'grants' => [
                'Menyetujui permintaan refund transaksi pada alur approval yang tersedia',
            ],
            'not_grants' => [
                'Melakukan refund tanpa transactions.refund (jika dipisah per SOP)',
            ],
            'affected_areas' => [
                'Approval koreksi transaksi',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'transactions.refund',
                'transactions.view',
                'transactions.details',
            ],
        ],

        'members.view' => [
            'summary' => 'Mengizinkan melihat data member (tanpa membuka detail PII jika dibatasi).',
            'grants' => [
                'Melihat daftar member',
                'Memilih member pada POS (dropdown) jika diizinkan oleh UI',
            ],
            'not_grants' => [
                'Melihat data sensitif member (telepon, dll) tanpa members.pii.view (dapat dimasking)',
                'Tambah/ubah/hapus member tanpa permission terkait',
            ],
            'affected_areas' => [
                'Member',
                'POS (pilih member)',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'members.pii.view',
                'members.create',
                'members.edit',
                'members.delete',
            ],
        ],
        'members.create' => [
            'summary' => 'Mengizinkan menambah member.',
            'grants' => [
                'Membuat member baru',
            ],
            'not_grants' => [
                'Ubah/hapus member tanpa permission terkait',
            ],
            'affected_areas' => [
                'Member',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'members.view',
                'members.pii.view',
            ],
        ],
        'members.edit' => [
            'summary' => 'Mengizinkan mengubah data member.',
            'grants' => [
                'Mengubah member',
            ],
            'not_grants' => [
                'Hapus member tanpa members.delete',
            ],
            'affected_areas' => [
                'Member',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'members.view',
                'members.pii.view',
            ],
        ],
        'members.delete' => [
            'summary' => 'Mengizinkan menghapus member.',
            'grants' => [
                'Menghapus member',
            ],
            'not_grants' => [
                'Mengubah member tanpa members.edit',
            ],
            'affected_areas' => [
                'Member',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'members.view',
                'members.edit',
            ],
        ],
        'members.pii.view' => [
            'summary' => 'Mengizinkan melihat data sensitif member (mis. nomor telepon) pada UI yang memask jika tidak ada izin.',
            'grants' => [
                'Melihat nomor telepon/PII member pada area yang mendukung',
            ],
            'not_grants' => [
                'Akses daftar member tanpa members.view',
            ],
            'affected_areas' => [
                'Member',
                'POS (dropdown member menampilkan telepon tanpa masking)',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'members.view',
                'transactions.pii.view',
            ],
        ],
        'members.regions.view' => [
            'summary' => 'Mengizinkan melihat data wilayah/region member.',
            'grants' => [
                'Melihat wilayah member',
            ],
            'not_grants' => [
                'Mengelola wilayah tanpa members.regions.manage',
            ],
            'affected_areas' => [
                'Wilayah Member',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'members.regions.manage',
            ],
        ],
        'members.regions.manage' => [
            'summary' => 'Mengizinkan mengelola data wilayah/region member.',
            'grants' => [
                'Membuat/mengubah/menghapus wilayah member sesuai fitur',
            ],
            'not_grants' => [
                'Akses wilayah tanpa members.regions.view pada UI tertentu (tergantung implementasi)',
            ],
            'affected_areas' => [
                'Wilayah Member',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'members.regions.view',
            ],
        ],

        'reports.view' => [
            'summary' => 'Mengizinkan masuk ke area laporan.',
            'grants' => [
                'Akses halaman laporan (container)',
            ],
            'not_grants' => [
                'Akses laporan spesifik tanpa permission laporan terkait (sales/performance/expenses)',
            ],
            'affected_areas' => [
                'Laporan',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'reports.sales',
                'reports.performance',
                'reports.expenses.manage',
                'inventory.reports.view',
            ],
        ],
        'reports.sales' => [
            'summary' => 'Mengizinkan melihat laporan penjualan.',
            'grants' => [
                'Melihat laporan penjualan',
            ],
            'not_grants' => [
                'Kelola beban operasional tanpa reports.expenses.manage',
            ],
            'affected_areas' => [
                'Laporan Penjualan',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'reports.view',
            ],
        ],
        'reports.performance' => [
            'summary' => 'Mengizinkan melihat laporan performa.',
            'grants' => [
                'Melihat laporan performa',
            ],
            'not_grants' => [
                'Akses laporan penjualan tanpa reports.sales',
            ],
            'affected_areas' => [
                'Laporan Performa',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'reports.view',
            ],
        ],
        'reports.expenses.manage' => [
            'summary' => 'Mengizinkan mengelola beban operasional (expense) pada modul laporan.',
            'grants' => [
                'Menambah/mengubah/menghapus data beban operasional sesuai fitur',
            ],
            'not_grants' => [
                'Akses laporan tanpa reports.view (tergantung implementasi UI)',
            ],
            'affected_areas' => [
                'Beban Operasional',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'reports.view',
            ],
        ],

        'vouchers.view' => [
            'summary' => 'Mengizinkan melihat data voucher dan kampanye voucher.',
            'grants' => [
                'Melihat voucher',
            ],
            'not_grants' => [
                'Membuat/mengubah voucher tanpa vouchers.manage',
            ],
            'affected_areas' => [
                'Voucher',
                'POS (penggunaan voucher tetap mengikuti validasi sistem)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'vouchers.manage',
            ],
        ],
        'vouchers.manage' => [
            'summary' => 'Mengizinkan mengelola voucher (kampanye, kode, kuota, aturan).',
            'grants' => [
                'Membuat/mengubah/menghapus voucher sesuai fitur',
            ],
            'not_grants' => [
                'Akses voucher tanpa vouchers.view pada UI tertentu (tergantung implementasi)',
            ],
            'affected_areas' => [
                'Voucher',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'vouchers.view',
                'discounts.manual.apply',
            ],
        ],

        'discounts.manual.apply' => [
            'summary' => 'Mengizinkan menerapkan diskon manual pada POS sesuai alur yang disediakan.',
            'grants' => [
                'Memberi diskon manual pada transaksi POS (percent/fixed sesuai UI)',
            ],
            'not_grants' => [
                'Mengubah harga item secara langsung (harga dihitung ulang server-side)',
                'Melewati aturan voucher atau poin',
            ],
            'affected_areas' => [
                'POS (diskon manual)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'pos.access',
                'transactions.view',
            ],
        ],

        'inventory.view' => [
            'summary' => 'Mengizinkan melihat modul inventory secara umum.',
            'grants' => [
                'Mengakses halaman inventory (view)',
            ],
            'not_grants' => [
                'Melakukan perubahan stok tanpa permission manage/create/edit/delete yang relevan',
            ],
            'affected_areas' => [
                'Inventory',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'inventory.manage',
                'inventory.ingredients.view',
                'inventory.movements.view',
                'inventory.purchases.view',
                'inventory.opnames.view',
                'inventory.reports.view',
            ],
        ],
        'inventory.manage' => [
            'summary' => 'Mengizinkan pengelolaan inventory secara umum (tindakan yang mengubah data).',
            'grants' => [
                'Mengelola modul inventory sesuai fitur yang memeriksa inventory.manage',
            ],
            'not_grants' => [
                'Tidak otomatis memberi izin granular (mis. create/delete movement) jika sistem memisahkan',
            ],
            'affected_areas' => [
                'Inventory',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.view',
            ],
        ],
        'inventory.ingredients.view' => [
            'summary' => 'Mengizinkan melihat daftar bahan baku/ingredient.',
            'grants' => [
                'Melihat bahan baku',
            ],
            'not_grants' => [
                'Mengubah bahan baku tanpa inventory.ingredients.manage',
            ],
            'affected_areas' => [
                'Inventory > Bahan Baku',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'inventory.ingredients.manage',
            ],
        ],
        'inventory.ingredients.manage' => [
            'summary' => 'Mengizinkan mengelola bahan baku/ingredient.',
            'grants' => [
                'Membuat/mengubah/menghapus bahan baku sesuai fitur',
            ],
            'not_grants' => [
                'Membuat movement stok tanpa inventory.movements.create (jika dipisah)',
            ],
            'affected_areas' => [
                'Inventory > Bahan Baku',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.ingredients.view',
            ],
        ],
        'inventory.suppliers.view' => [
            'summary' => 'Mengizinkan melihat data supplier inventory.',
            'grants' => [
                'Melihat supplier',
            ],
            'not_grants' => [
                'Mengelola supplier tanpa inventory.suppliers.manage',
            ],
            'affected_areas' => [
                'Inventory > Supplier',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'inventory.suppliers.manage',
            ],
        ],
        'inventory.suppliers.manage' => [
            'summary' => 'Mengizinkan mengelola data supplier inventory.',
            'grants' => [
                'Membuat/mengubah/menghapus supplier',
            ],
            'not_grants' => [
                'Melakukan pembelian tanpa permission pembelian yang relevan',
            ],
            'affected_areas' => [
                'Inventory > Supplier',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.suppliers.view',
            ],
        ],
        'inventory.movements.view' => [
            'summary' => 'Mengizinkan melihat pergerakan stok (inventory movements).',
            'grants' => [
                'Melihat daftar pergerakan stok',
            ],
            'not_grants' => [
                'Membuat/menghapus movement tanpa permission create/delete',
            ],
            'affected_areas' => [
                'Inventory > Pergerakan Stok',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'inventory.movements.manage',
                'inventory.movements.create',
                'inventory.movements.delete',
            ],
        ],
        'inventory.movements.manage' => [
            'summary' => 'Mengizinkan mengelola pergerakan stok.',
            'grants' => [
                'Mengelola pergerakan stok sesuai fitur yang memeriksa inventory.movements.manage',
            ],
            'not_grants' => [
                'Membuat movement tanpa inventory.movements.create jika sistem memisahkan',
            ],
            'affected_areas' => [
                'Inventory > Pergerakan Stok',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.movements.view',
            ],
        ],
        'inventory.movements.create' => [
            'summary' => 'Mengizinkan membuat pergerakan stok baru.',
            'grants' => [
                'Membuat pergerakan stok (mis. penyesuaian/manual movement) sesuai UI',
            ],
            'not_grants' => [
                'Menghapus movement tanpa inventory.movements.delete',
            ],
            'affected_areas' => [
                'Inventory > Pergerakan Stok (buat)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.movements.view',
                'inventory.movements.manage',
                'inventory.movements.delete',
            ],
        ],
        'inventory.movements.delete' => [
            'summary' => 'Mengizinkan menghapus pergerakan stok.',
            'grants' => [
                'Menghapus pergerakan stok sesuai UI',
            ],
            'not_grants' => [
                'Membuat movement tanpa inventory.movements.create',
            ],
            'affected_areas' => [
                'Inventory > Pergerakan Stok (hapus)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.movements.view',
                'inventory.movements.manage',
            ],
        ],

        'inventory.purchases.view' => [
            'summary' => 'Mengizinkan melihat pembelian inventory (purchase).',
            'grants' => [
                'Melihat daftar pembelian',
            ],
            'not_grants' => [
                'Membuat/mengubah/menerima/membatalkan pembelian tanpa permission terkait',
            ],
            'affected_areas' => [
                'Inventory > Pembelian',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'inventory.purchases.manage',
                'inventory.purchases.create',
                'inventory.purchases.edit',
                'inventory.purchases.receive',
                'inventory.purchases.cancel',
            ],
        ],
        'inventory.purchases.manage' => [
            'summary' => 'Mengizinkan mengelola pembelian inventory.',
            'grants' => [
                'Mengelola pembelian sesuai fitur yang memeriksa inventory.purchases.manage',
            ],
            'not_grants' => [
                'Tidak otomatis memberi izin aksi spesifik jika sistem memisahkan (create/receive/cancel)',
            ],
            'affected_areas' => [
                'Inventory > Pembelian',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.purchases.view',
            ],
        ],
        'inventory.purchases.create' => [
            'summary' => 'Mengizinkan membuat pembelian inventory.',
            'grants' => [
                'Membuat purchase order/pembelian',
            ],
            'not_grants' => [
                'Menerima pembelian tanpa inventory.purchases.receive',
            ],
            'affected_areas' => [
                'Inventory > Pembelian (buat)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.purchases.view',
                'inventory.purchases.edit',
                'inventory.purchases.receive',
            ],
        ],
        'inventory.purchases.edit' => [
            'summary' => 'Mengizinkan mengubah pembelian inventory.',
            'grants' => [
                'Mengubah purchase order sesuai UI',
            ],
            'not_grants' => [
                'Membatalkan pembelian tanpa inventory.purchases.cancel',
            ],
            'affected_areas' => [
                'Inventory > Pembelian (edit)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.purchases.view',
                'inventory.purchases.create',
                'inventory.purchases.cancel',
            ],
        ],
        'inventory.purchases.receive' => [
            'summary' => 'Mengizinkan menerima pembelian (receive) sehingga stok bertambah sesuai penerimaan.',
            'grants' => [
                'Menandai pembelian diterima dan mencatat movement masuk sesuai sistem',
            ],
            'not_grants' => [
                'Membuat pembelian tanpa inventory.purchases.create',
            ],
            'affected_areas' => [
                'Inventory > Pembelian (terima)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.purchases.view',
                'inventory.movements.create',
            ],
        ],
        'inventory.purchases.cancel' => [
            'summary' => 'Mengizinkan membatalkan pembelian inventory.',
            'grants' => [
                'Membatalkan purchase order sesuai aturan sistem',
            ],
            'not_grants' => [
                'Menerima pembelian tanpa inventory.purchases.receive',
            ],
            'affected_areas' => [
                'Inventory > Pembelian (batal)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.purchases.view',
                'inventory.purchases.manage',
            ],
        ],

        'inventory.opnames.view' => [
            'summary' => 'Mengizinkan melihat stock opname.',
            'grants' => [
                'Melihat daftar dan detail stock opname',
            ],
            'not_grants' => [
                'Membuat/mengubah/posting/cancel opname tanpa permission terkait',
            ],
            'affected_areas' => [
                'Inventory > Stock Opname',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'inventory.opnames.manage',
                'inventory.opnames.create',
                'inventory.opnames.edit',
                'inventory.opnames.refresh_system_stocks',
                'inventory.opnames.post',
                'inventory.opnames.cancel',
            ],
        ],
        'inventory.opnames.manage' => [
            'summary' => 'Mengizinkan mengelola stock opname.',
            'grants' => [
                'Mengelola proses stock opname sesuai fitur yang memeriksa inventory.opnames.manage',
            ],
            'not_grants' => [
                'Tidak otomatis memberi izin aksi spesifik jika dipisah (create/post/cancel)',
            ],
            'affected_areas' => [
                'Inventory > Stock Opname',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.opnames.view',
            ],
        ],
        'inventory.opnames.create' => [
            'summary' => 'Mengizinkan membuat stock opname.',
            'grants' => [
                'Membuat stock opname baru',
            ],
            'not_grants' => [
                'Posting opname tanpa inventory.opnames.post',
            ],
            'affected_areas' => [
                'Inventory > Stock Opname (buat)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.opnames.view',
                'inventory.opnames.edit',
            ],
        ],
        'inventory.opnames.edit' => [
            'summary' => 'Mengizinkan mengubah stock opname.',
            'grants' => [
                'Mengubah draft stock opname sesuai UI',
            ],
            'not_grants' => [
                'Membatalkan opname tanpa inventory.opnames.cancel',
            ],
            'affected_areas' => [
                'Inventory > Stock Opname (edit)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.opnames.view',
                'inventory.opnames.post',
                'inventory.opnames.cancel',
            ],
        ],
        'inventory.opnames.refresh_system_stocks' => [
            'summary' => 'Mengizinkan refresh stok sistem saat proses stock opname.',
            'grants' => [
                'Mengambil/refresh stok sistem sebagai baseline opname sesuai UI',
            ],
            'not_grants' => [
                'Posting opname tanpa inventory.opnames.post',
            ],
            'affected_areas' => [
                'Inventory > Stock Opname (refresh stok sistem)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.opnames.view',
                'inventory.opnames.manage',
            ],
        ],
        'inventory.opnames.post' => [
            'summary' => 'Mengizinkan posting stock opname sehingga stok sistem disesuaikan.',
            'grants' => [
                'Posting opname (commit) sehingga terjadi penyesuaian stok sesuai selisih',
            ],
            'not_grants' => [
                'Membuat opname tanpa inventory.opnames.create',
            ],
            'affected_areas' => [
                'Inventory > Stock Opname (posting)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.opnames.view',
                'inventory.movements.create',
            ],
        ],
        'inventory.opnames.cancel' => [
            'summary' => 'Mengizinkan membatalkan stock opname.',
            'grants' => [
                'Membatalkan opname sesuai aturan sistem',
            ],
            'not_grants' => [
                'Posting opname tanpa inventory.opnames.post',
            ],
            'affected_areas' => [
                'Inventory > Stock Opname (batal)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'inventory.opnames.view',
                'inventory.opnames.manage',
            ],
        ],
        'inventory.reports.view' => [
            'summary' => 'Mengizinkan melihat laporan inventory.',
            'grants' => [
                'Melihat laporan inventory',
            ],
            'not_grants' => [
                'Mengubah data inventory tanpa permission manage terkait',
            ],
            'affected_areas' => [
                'Inventory > Laporan',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'inventory.view',
                'reports.view',
            ],
        ],

        'users.view' => [
            'summary' => 'Mengizinkan melihat daftar pengguna.',
            'grants' => [
                'Melihat data pengguna',
            ],
            'not_grants' => [
                'Membuat/mengubah/menghapus pengguna tanpa hak akses terkait',
                'Mengelola peran tanpa roles.manage',
            ],
            'affected_areas' => [
                'Pengguna',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'users.create',
                'users.edit',
                'users.delete',
                'roles.view',
                'roles.manage',
            ],
        ],
        'users.create' => [
            'summary' => 'Mengizinkan membuat pengguna baru.',
            'grants' => [
                'Membuat pengguna',
            ],
            'not_grants' => [
                'Mengubah/menghapus pengguna tanpa hak akses terkait',
            ],
            'affected_areas' => [
                'Pengguna',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'users.view',
                'roles.view',
            ],
        ],
        'users.edit' => [
            'summary' => 'Mengizinkan mengubah pengguna (profil, status, atau penetapan peran sesuai UI).',
            'grants' => [
                'Mengubah pengguna',
            ],
            'not_grants' => [
                'Menghapus pengguna tanpa users.delete',
            ],
            'affected_areas' => [
                'Pengguna',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'users.view',
                'users.delete',
            ],
        ],
        'users.delete' => [
            'summary' => 'Mengizinkan menghapus pengguna.',
            'grants' => [
                'Menghapus pengguna',
            ],
            'not_grants' => [
                'Mengubah pengguna tanpa users.edit',
            ],
            'affected_areas' => [
                'Pengguna',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'users.view',
                'users.edit',
            ],
        ],

        'roles.view' => [
            'summary' => 'Mengizinkan melihat daftar peran dan hak akses yang ada.',
            'grants' => [
                'Melihat halaman daftar peran',
                'Melihat panduan peran dan panduan hak akses (read-only)',
            ],
            'not_grants' => [
                'Membuat/mengubah peran tanpa roles.manage',
            ],
            'affected_areas' => [
                'Peran & Hak Akses',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'roles.manage',
                'users.view',
            ],
        ],
        'roles.manage' => [
            'summary' => 'Mengizinkan mengelola peran dan pengaturan hak akses.',
            'grants' => [
                'Membuat peran baru',
                'Mengubah peran dan hak akses',
                'Menghapus peran (dengan batasan tertentu seperti peran owner/pemilik)',
            ],
            'not_grants' => [
                'Mengubah settings tanpa settings.edit',
            ],
            'affected_areas' => [
                'Peran & Hak Akses',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'roles.view',
                'users.edit',
                'settings.edit',
            ],
        ],

        'dining_tables.view' => [
            'summary' => 'Mengizinkan melihat data meja dan QR Code untuk dine-in.',
            'grants' => [
                'Membuka halaman Meja & QR Code',
                'Melihat daftar meja, QR, dan link self-order per meja',
            ],
            'not_grants' => [
                'Menambah/mengubah/menghapus meja tanpa dining_tables.edit',
                'Regenerate QR tanpa dining_tables.edit',
            ],
            'affected_areas' => [
                'Meja & QR Code',
                'Self-order (link meja)',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => false,
            ],
            'related_permissions' => [
                'dining_tables.edit',
            ],
        ],
        'dining_tables.edit' => [
            'summary' => 'Mengizinkan mengelola meja dan QR Code (tambah/ubah/hapus/regenerate/bulk).',
            'grants' => [
                'Menambah meja',
                'Mengubah nomor meja',
                'Menghapus meja (jika belum dipakai transaksi)',
                'Regenerate QR meja',
                'Generate QR yang kosong dan bulk create meja',
            ],
            'not_grants' => [
                'Mengubah pengaturan umum tanpa settings.edit',
            ],
            'affected_areas' => [
                'Meja & QR Code',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => false,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'dining_tables.view',
            ],
        ],

        'settings.view' => [
            'summary' => 'Mengizinkan melihat pengaturan aplikasi.',
            'grants' => [
                'Melihat halaman pengaturan',
            ],
            'not_grants' => [
                'Mengubah pengaturan tanpa settings.edit',
            ],
            'affected_areas' => [
                'Pengaturan',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.edit',
            ],
        ],
        'settings.store.view' => [
            'summary' => 'Mengizinkan melihat Pengaturan Toko (nama, alamat, pajak, status gateway, logo).',
            'grants' => [
                'Melihat section Pengaturan Toko',
            ],
            'not_grants' => [
                'Menyimpan perubahan tanpa settings.store.edit',
            ],
            'affected_areas' => [
                'Pengaturan Toko',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.store.edit',
            ],
        ],
        'settings.store.edit' => [
            'summary' => 'Mengizinkan mengubah Pengaturan Toko (nama toko, pajak, alamat, logo, gateway).',
            'grants' => [
                'Menyimpan perubahan pada section Pengaturan Toko',
                'Mengunggah/mengganti logo toko',
            ],
            'not_grants' => [
                'Mengubah section lain tanpa permission section terkait',
            ],
            'affected_areas' => [
                'Pengaturan Toko',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.store.view',
            ],
        ],
        'settings.printers.view' => [
            'summary' => 'Mengizinkan melihat Pengaturan Printer (sumber printer dan opsi struk kasir).',
            'grants' => [
                'Melihat section Sumber Printer',
                'Melihat daftar sumber printer',
            ],
            'not_grants' => [
                'Menambah/mengubah/menghapus sumber printer tanpa settings.printers.edit',
            ],
            'affected_areas' => [
                'Sumber Printer',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.printers.edit',
            ],
        ],
        'settings.printers.edit' => [
            'summary' => 'Mengizinkan mengubah Pengaturan Printer (sumber printer dan setup perangkat).',
            'grants' => [
                'Mengubah opsi struk kasir',
                'Menambah/mengubah/menghapus sumber printer',
                'Setup/test/forget perangkat printer per sumber',
            ],
            'not_grants' => [
                'Mengubah section lain tanpa permission section terkait',
            ],
            'affected_areas' => [
                'Sumber Printer',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.printers.view',
            ],
        ],
        'settings.system.view' => [
            'summary' => 'Mengizinkan melihat Pengaturan Sistem (operasional POS, koreksi transaksi, diskon).',
            'grants' => [
                'Melihat section Sistem',
            ],
            'not_grants' => [
                'Menyimpan perubahan tanpa settings.system.edit',
            ],
            'affected_areas' => [
                'Pengaturan Sistem',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.system.edit',
            ],
        ],
        'settings.system.edit' => [
            'summary' => 'Mengizinkan mengubah Pengaturan Sistem (pembulatan, default pembayaran, aturan koreksi, diskon).',
            'grants' => [
                'Menyimpan perubahan pada section Sistem',
            ],
            'not_grants' => [
                'Mengubah section lain tanpa permission section terkait',
            ],
            'affected_areas' => [
                'Pengaturan Sistem',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.system.view',
            ],
        ],
        'settings.points.view' => [
            'summary' => 'Mengizinkan melihat Pengaturan Poin & Member (earning, redemption).',
            'grants' => [
                'Melihat section Poin & Member',
            ],
            'not_grants' => [
                'Menyimpan perubahan tanpa settings.points.edit',
            ],
            'affected_areas' => [
                'Pengaturan Poin & Member',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.points.edit',
            ],
        ],
        'settings.points.edit' => [
            'summary' => 'Mengizinkan mengubah Pengaturan Poin & Member (rate earning, nilai tukar, minimum penukaran).',
            'grants' => [
                'Menyimpan perubahan pada section Poin & Member',
            ],
            'not_grants' => [
                'Mengubah section lain tanpa permission section terkait',
            ],
            'affected_areas' => [
                'Pengaturan Poin & Member',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.points.view',
            ],
        ],
        'settings.targets.view' => [
            'summary' => 'Mengizinkan melihat Target Bulanan (daftar target dan nilai).',
            'grants' => [
                'Melihat section Target Bulanan',
                'Melihat daftar target',
            ],
            'not_grants' => [
                'Menyimpan/menghapus target tanpa settings.targets.edit',
            ],
            'affected_areas' => [
                'Target Bulanan',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.targets.edit',
            ],
        ],
        'settings.targets.edit' => [
            'summary' => 'Mengizinkan mengubah Target Bulanan (buat/ubah/hapus target).',
            'grants' => [
                'Menyimpan target bulanan',
                'Menghapus target bulanan',
            ],
            'not_grants' => [
                'Mengubah section lain tanpa permission section terkait',
            ],
            'affected_areas' => [
                'Target Bulanan',
            ],
            'risk' => [
                'sensitive_data' => false,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.targets.view',
            ],
        ],
        'settings.edit' => [
            'summary' => 'Mengizinkan mengubah pengaturan aplikasi (mis. pajak, poin, konfigurasi bisnis).',
            'grants' => [
                'Mengubah pengaturan',
            ],
            'not_grants' => [
                'Mengelola peran tanpa roles.manage',
            ],
            'affected_areas' => [
                'Pengaturan',
            ],
            'risk' => [
                'sensitive_data' => true,
                'financial_risk' => true,
                'system_risk' => true,
            ],
            'related_permissions' => [
                'settings.view',
                'roles.manage',
            ],
        ],
    ],
];
