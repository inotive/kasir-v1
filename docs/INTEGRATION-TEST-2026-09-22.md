Pengujian integrasi kasir — 22 September 2026

Pengujian dijalankan pada PHP 8.5.10 menggunakan konfigurasi PHPUnit proyek: SQLite `:memory:`, antrean sinkron, serta mailer dan sesi pengujian. Ini merupakan pengujian aplikasi otomatis; interaksi browser, printer fisik, serta pembayaran Midtrans langsung belum diuji.

Integrasi pengeluaran telah diuji dari form Livewire sampai database, laporan laba/rugi, dan ringkasan ekspor Excel. Sebanyak 21 tes terarah lulus dengan 148 assertion. Skenario tambahan mencakup qty desimal, perubahan total setelah edit/hapus, tanggal pengeluaran yang berbeda dari tanggal pencatatan, penolakan perubahan oleh pengguna yang hanya boleh membaca laporan, pemisahan data tenant, dan memastikan pencatatan beban tidak menghasilkan pergerakan stok.

Ditemukan dan diperbaiki satu bug pada `SalesProfitReportPage::buildDailyTable()`: tanggal tanpa penjualan sebelumnya tidak masuk tabel harian meskipun memiliki pengeluaran. Total periode sudah menghitung pengeluaran itu, sehingga tabel harian tidak dapat direkonsiliasi dengan ringkasan. Daftar tanggal sekarang juga mencakup tanggal pengeluaran dan biaya stok. Tes regresi memverifikasi bahwa pengeluaran Rp20.000 tanpa penjualan menghasilkan beban Rp20.000 dan laba bersih -Rp20.000 pada tanggal tersebut.

Pengulangan akhir seluruh suite: **200 tes, 184 lulus, 16 gagal**. Pemeriksaan format Pint pada kode yang diubah dan `git diff --check` juga lulus.

Temuan pada suite lintas modul yang masih perlu ditindaklanjuti:

| Modul / tes | Jumlah gagal | Temuan |
| --- | ---: | --- |
| `GuidesModuleTest` | 1 | Membuka artikel menghasilkan HTTP 500: variabel `$grouped` dipakai oleh view tetapi tidak dikirim oleh `GuideShowPage`. |
| `PosCheckoutDiscountCheckboxTest` | 1 | Nilai diskon sangat besar (`1.0E+20`) menyebabkan `ErrorException` ketika dikonversi ke integer pada `PosPage::updatedManualDiscountValue()` di runtime pengujian ini. |
| `SelfOrderPaymentTest`, `MenuPackageSystemTest` | 6 | Event `NewMidtransTransaction` / `SelfOrderCashPendingCreated` gagal karena payload job tidak memiliki tenant aktif. Fixture pengujian tidak menyiapkan tenant, sementara antrean dikonfigurasi tenant-aware; dampak pada request produksi dengan tenant yang valid belum dibuktikan. |
| `PosImportSelfOrderVoucherTest` | 1 | Total setelah impor transaksi self-order bernilai 0, sedangkan tes mengharapkan 9.000. Perhitungan impor dan fixture perlu ditelusuri lebih lanjut. |
| `PosCheckoutWizardTest` | 2 | Tes mengharapkan langkah awal 1, sedangkan kode checkout saat ini membuka langkah 3. Ini merupakan ketidaksesuaian perilaku dan ekspektasi tes yang perlu ditetapkan berdasarkan alur produk. |
| `PosMemberAutoFillTest` | 2 | Tes mengharapkan nama awal “Walk-in Customer”, sedangkan kode saat ini menginisialisasinya menjadi teks kosong. Tes berhenti sebelum pemilihan member. |
| `TransactionPrintPayloadPiiTest` | 1 | Tes mengharapkan nama disamarkan tanpa izin PII; implementasi mengecualikan pelanggan walk-in sehingga nama tetap dikirim pada payload cetak. Perlu menyelaraskan aturan privasi dengan tes. |
| `RbacPermissionGuideCompletenessTest` | 1 | Panduan izin belum mencakup `settings.printers.devices`; tes berhenti pada izin pertama yang tidak ditemukan. |
| `RbacRouteProtectionTest` | 1 | Route `tenants.index`, `tenants.create`, dan `tenants.edit` tidak memiliki middleware permission/role yang dipersyaratkan tes. Komponen tetap memeriksa `dashboard.access` dan menolak pengguna yang memiliki tenant, sehingga hasil ini tidak dengan sendirinya membuktikan akses tanpa otorisasi. |

Kegagalan lintas modul di atas belum diperbaiki pada pemeriksaan ini. Prioritas investigasi berikutnya adalah HTTP 500 panduan, konversi diskon besar, dan impor transaksi voucher. Skenario self-order perlu diuji kembali dengan tenant aktif sebelum menentukan perubahan pada kode aplikasi atau fixture.

Perintah untuk mengulang pengujian terarah:

```sh
php artisan test --compact \
  tests/Feature/OperatingExpenseIntegrationTest.php \
  tests/Feature/OperatingExpensesPageTest.php \
  tests/Feature/SalesProfitReportNetProfitWithExpensesTest.php \
  tests/Feature/SalesProfitReportInventoryCogsTest.php \
  tests/Feature/ReportsPermissionEnforcementTest.php \
  tests/Feature/ReportExcelExportTest.php \
  tests/Feature/TenantIsolationTest.php
```

Perintah untuk mengulang seluruh suite:

```sh
php artisan test --compact
```
