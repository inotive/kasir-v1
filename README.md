PANDUAN INSTALASI / SETUP APLIKASI
=================================

Teknologi:
- Laravel 12 (PHP)
- Livewire v4
- Vite + Tailwind (frontend)
- Database: MySQL/MariaDB


A. PRASYARAT
------------
1) PHP 8.2+ (disarankan 8.3) + ekstensi umum Laravel (pdo_mysql, openssl, mbstring, tokenizer, ctype, json, xml, curl, fileinfo, gd)
2) Composer 2.x
3) Node.js 18+ (disarankan 20+) + npm
4) MySQL/MariaDB


B. SETUP PROJECT (PERTAMA KALI)
-------------------------------
1) Salin project ke komputer/server Anda, lalu masuk ke folder project.

2) Buat file environment:
   - Copy file:
     cp .env.example .env

3) Edit file .env (WAJIB diisi minimal):
   - APP_NAME=Nama aplikasi
   - APP_URL=Alamat aplikasi (contoh: http://localhost:8000 atau https://domain-anda)
   - DB_CONNECTION=mysql
   - DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

4) Variable .env yang KHUSUS (opsional, isi jika Anda memakai fiturnya):

   4.1) Pemisahan domain Admin (opsional)
   - ADMIN_DOMAIN=
     Kosongkan jika admin dan landing berada di domain yang sama.
     Jika diisi (mis. admin.domain.com), maka akses /admin hanya valid dari host tersebut.
   - ADMIN_BLOCK_RESPONSE=redirect atau 404
   - LANDING_URL=/

   4.2) Midtrans (opsional, isi jika memakai pembayaran Midtrans)
   - MIDTRANS_MERCHANT_ID=
   - MIDTRANS_SERVER_KEY=
   - MIDTRANS_CLIENT_KEY=
   - MIDTRANS_IS_PRODUCTION=false/true
   - MIDTRANS_IS_SANITIZED=true
   - MIDTRANS_IS_3DS=true

   4.3) Pusher / Broadcasting (wajib untuk self order, isi jika memakai realtime / event broadcast)
   - PUSHER_APP_ID=
   - PUSHER_APP_KEY=
   - PUSHER_APP_SECRET=
   - PUSHER_APP_CLUSTER=ap1
   Catatan: Vite akan membaca otomatis dari:
   - VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
   - VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

   4.4) QR Secret (opsional)
   - QR_SECRET=
     Jika kosong, sistem otomatis memakai APP_KEY.


C. INSTALL DEPENDENCY
---------------------
1) Install dependency backend:
   composer install

2) Generate APP_KEY:
   php artisan key:generate

3) Install dependency frontend:
   npm install


D. DATABASE (MIGRATE + SEED)
----------------------------
1) Jalankan migrasi:
   php artisan migrate

2) Pastikan tabel session tersedia (karena default env memakai SESSION_DRIVER=database).
   Jika tabel sessions belum ada:
   - php artisan session:table
   - php artisan migrate
   Jika tidak ingin session database, Anda boleh ubah:
   - SESSION_DRIVER=file

3) Jalankan seeder role & permission (wajib untuk akses admin):
   php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder


E. STORAGE LINK (WAJIB)
-----------------------
Agar file publik seperti logo toko, QR Code, dll dapat diakses:
   php artisan storage:link


F. BUILD ASSET FRONTEND
-----------------------
1) Untuk production (build ke public/build):
   npm run build

2) Untuk development (hot reload):
   npm run dev


G. MENJALANKAN APLIKASI
-----------------------
1) Jalankan server lokal:
   php artisan serve

2) Buka halaman admin:
   http://localhost:8000/admin


H. SETUP AWAL AKUN OWNER (PERTAMA KALI)
---------------------------------------
Jika tabel users masih kosong, buka:
   http://localhost:8000/admin/setup

Ini membuat user pertama dengan role owner.

Setelah itu, jalankan lagi seeder role/permission agar role Spatie tersinkron:
   php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder


I. OPSIONAL (UNTUK PRODUKSI)
----------------------------
1) Queue worker (default QUEUE_CONNECTION=database):
   php artisan queue:work

2) Scheduler/cron (jika ingin job terjadwal berjalan, mis. vouchers:alert):
   Tambahkan cron di server:
   * * * * * php /path/to/project/artisan schedule:run


SETUP CEPAT (OPSIONAL)
----------------------
Project memiliki perintah:
   composer run setup

Perintah ini akan menjalankan install composer, copy .env (jika belum ada), generate key, migrate, npm install, dan build asset.
Setelah itu tetap jalankan seeder role/permission dan lakukan setup user owner jika belum ada user.
# kasir-v1
