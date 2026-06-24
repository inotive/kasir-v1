# Deployment Troubleshooting — hippikasir.com (CloudPanel + Varnish + Cloudflare)

Catatan masalah deployment yang pernah terjadi di server produksi dan cara
memperbaikinya. Aplikasi ini Laravel 12 + Livewire 3. Di **cloudhost biasa**
app langsung jalan; masalah-masalah di bawah **spesifik ke stack server
hippikasir.com**, bukan bug aplikasi.

## Arsitektur server (penting dipahami dulu)

Request menempuh **4 lapisan**:

```
Cloudflare  →  nginx :443 (publik)  →  Varnish  →  nginx :8080 (backend)  →  php-fpm
```

Ada **dua blok nginx** (port 443 publik & port 8080 backend). Banyak masalah
muncul karena konfigurasi harus benar di **kedua** lapisan, bukan cuma satu.
Ini berbeda total dari cloudhost yang cuma 1 lapisan nginx → PHP.

---

## Masalah 1 — `livewire.js` 404 (paling parah)

### Gejala
- Console browser: `GET .../livewire-75549281/livewire.js 404 (Not Found)`
- Semua interaktivitas Livewire mati (form, tombol, dsb).
- App yang sama jalan normal di cloudhost.

### Akar masalah
Livewire 3 menyajikan `livewire.js` lewat **route Laravel** (di-render PHP),
**bukan** file fisik di `public/`. Buktinya:

```sh
php artisan route:list | grep -i livewire
# GET|HEAD livewire-75549281/livewire.js ... FrontendAssets@returnJavaScriptAsFile
```

(`livewire-75549281` adalah prefix resmi Livewire, bukan bug.)

Tapi config nginx CloudPanel punya blok regex yang menangkap **semua** file
berakhiran `.js` dan menyajikannya sebagai **file statis dari disk**:

```nginx
location ~* ^.+\.(css|js|jpg|...)$ {
    add_header Access-Control-Allow-Origin "*";
    expires max;
    access_log off;
}
```

Di nginx, regex location (`~*`) **menang** atas `location /`. Jadi request
`livewire.js` ditangkap blok ini, dicari sebagai file fisik (tidak ada) →
**404**, dan tidak pernah sampai ke PHP.

> Catatan kunci: blok regex `.js` ini ada di **DUA** server block (port 443
> DAN port 8080). Memperbaiki satu saja tidak cukup — 443 meneruskan ke 8080
> yang juga memotong `.js`.

### Fix
Tambahkan fallback ke app saat file tidak ada secara fisik.

**Blok port 443 (publik)** — tambah `try_files $uri @backend;` + blok `@backend`:

```nginx
location ~* ^.+\.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|woff2|eot|mp4|ogg|ogv|webm|webp|zip|swf|map)$ {
    try_files $uri @backend;          # <-- TAMBAH
    add_header Access-Control-Allow-Origin "*";
    add_header alt-svc 'h3=":443"; ma=86400';
    expires max;
    access_log off;
}

location @backend {                   # <-- TAMBAH blok ini (proxy ke Varnish, sama spt location /)
    {{varnish_proxy_pass}}
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_hide_header X-Varnish;
    proxy_redirect off;
}
```

**Blok port 8080 (backend)** — tambah `try_files $uri /index.php?$args;`:

```nginx
location ~* ^.+\.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|woff2|eot|mp4|ogg|ogv|webm|webp|zip|swf|map)$ {
    try_files $uri /index.php?$args;  # <-- TAMBAH
    add_header Access-Control-Allow-Origin "*";
    expires max;
    access_log off;
}
```

**Logika:** file `.js`/`.css` yang ada fisik (hasil `npm run build` di
`public/build`) tetap disajikan langsung + cache (cepat). File yang tidak ada
fisik (`livewire.js`, css module) jatuh ke PHP → Livewire serve.

Konfigurasi vhost lengkap yang terbukti bekerja ada di akhir dokumen ini.

### Verifikasi
```sh
curl -I https://hippikasir.com/livewire-75549281/livewire.js
# harus: HTTP/2 200  (bukan 404)
```

> ⚠️ Ada Cloudflare di depan. Kalau masih 404 setelah fix, **purge cache
> Cloudflare** (Cloudflare bisa men-cache respons 404 lama).

---

## Masalah 2 — Mixed Content: favicon/asset di-load via `http://`

### Gejala
```
Mixed Content: The page at 'https://hippikasir.com/' was loaded over HTTPS,
but requested an insecure favicon 'http://hippikasir.com/images/...'.
```

### Akar masalah
`asset()` / `url()` Laravel membangun URL dari skema request yang dideteksi.
Di belakang proxy (Varnish/Cloudflare) yang terminate SSL, PHP menerima request
sebagai `http://`, sehingga generate URL `http://` walau user akses via HTTPS.

### Fix
Paksa skema HTTPS di level URL generator — tidak tergantung apakah proxy
mengirim header `X-Forwarded-Proto` dengan benar.

`app/Providers/AppServiceProvider.php` → `boot()`:
```php
use Illuminate\Support\Facades\URL;

if (str_starts_with((string) config('app.url'), 'https://')) {
    URL::forceScheme('https');
}
```

Plus `bootstrap/app.php` (defensif, agar proxy header dipercaya):
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*');
    // ... alias dll
})
```

Pastikan juga `APP_URL=https://hippikasir.com` di `.env`, lalu
`php artisan config:clear && php artisan config:cache`.

---

## Masalah 3 — Pusher: "You must pass your app key when you instantiate Pusher"

### Gejala
```
Uncaught You must pass your app key when you instantiate Pusher.
```

### Akar masalah
1. Kode realtime aktif tapi Pusher **belum dikonfigurasi**
   (`BROADCAST_CONNECTION=log`, tidak ada kredensial `PUSHER_*`).
2. `resources/js/bootstrap.js` meng-init Echo **tanpa guard**:
   `new Echo({ key: import.meta.env.VITE_PUSHER_APP_KEY })` → key `undefined`
   → throw.
3. Penting: `import.meta.env.VITE_*` di-compile saat **`npm run build`**
   (build time), **bukan** runtime. Set env saat runtime tidak berpengaruh —
   harus tersedia saat build.

### Fix
**Guard init** (sudah diterapkan di `resources/js/bootstrap.js`):
```js
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
if (pusherKey) {
    window.Pusher = Pusher;
    window.Echo = new Echo({ /* ... */ });
}
```
Dengan guard ini, tanpa key app tidak crash (fitur realtime nonaktif).

**Untuk mengaktifkan realtime** (notif self-order via channel `cashier.orders`):
gunakan Pusher Channels (pusher.com) atau Laravel Reverb, lalu set kredensial:
- Backend: `BROADCAST_CONNECTION=pusher`, `PUSHER_APP_ID/KEY/SECRET`, `PUSHER_APP_CLUSTER`
- Frontend (build-time): `VITE_PUSHER_APP_KEY`, `VITE_PUSHER_APP_CLUSTER`
- Lalu **rebuild** (`npm run build`) agar VITE_* ikut ter-compile.

---

## Masalah 4 — APP_ENV = local di produksi

`php artisan about` menampilkan `Environment ... local`. Di produksi harus
`production` (debug mode bisa membocorkan stack trace). Set `APP_ENV=production`
dan `APP_DEBUG=false` di `.env`, lalu `php artisan config:cache`.

---

## Masalah 5 (lain-lain, non-blocking)

| Error di console | Penjelasan |
|---|---|
| `bg-login.png 404` | File gambar memang hilang dari `public/images/shape/`. Bukan masalah nginx — file harus ada. |
| `/admin/sw.js 404` | Service worker PWA. Abaikan kalau tidak pakai PWA. |
| `ERR_BLOCKED_BY_CLIENT` | Diblokir ad-blocker / extension privacy di browser. Bukan masalah server. Hilang di incognito. |
| `Failed to get subsystem status` | Noise dari extension browser. Abaikan. |

---

## Catatan: Coolify terminal websocket (kalau pakai Coolify)

Jika mengakses Coolify dan tab Terminal error *"Terminal websocket connection
lost"* / `ws://...:6002/terminal/ws failed`:
- Terminal Coolify pakai container `coolify-realtime` (soketi) di port 6001
  (pusher) & 6002 (terminal). Health check hanya cek 6001, jadi container bisa
  `healthy` walau terminal (6002) bermasalah → intermittent.
- Buka port 6002 di firewall: `sudo ufw allow 6002/tcp`.
- DNS dual-stack (IPv6 NAT64 `64:ff9b::...` unreachable) bisa bikin browser
  intermittent gagal (Happy Eyeballs).
- **Alternatif paling reliable:** akses container langsung via SSH, jangan
  bergantung pada terminal web Coolify:
  ```sh
  ssh inotive@<IP-VPS>
  sudo docker exec -it <nama-container> sh
  ```

---

## Lampiran — Konfigurasi vhost yang TERBUKTI BEKERJA

```nginx
server {
  listen 80;
  listen [::]:80;
  listen 443 quic;
  listen 443 ssl;
  listen [::]:443 quic;
  listen [::]:443 ssl;
  http2 on;
  http3 off;
  {{ssl_certificate_key}}
  {{ssl_certificate}}
  server_name *.hippikasir.com;
  {{root}}
  {{nginx_access_log}}
  {{nginx_error_log}}
  if ($scheme != "https") {
    rewrite ^ https://$host$request_uri permanent;
  }
  location ~ /.well-known {
    auth_basic off;
    allow all;
  }
  {{settings}}
  include /etc/nginx/global_settings;
  location / {
    {{varnish_proxy_pass}}
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_hide_header X-Varnish;
    proxy_redirect off;
    proxy_max_temp_file_size 0;
    proxy_connect_timeout      720;
    proxy_send_timeout         720;
    proxy_read_timeout         720;
    proxy_buffer_size          128k;
    proxy_buffers              4 256k;
    proxy_busy_buffers_size    256k;
    proxy_temp_file_write_size 256k;
  }
}

server {
  listen 80;
  listen [::]:80;
  listen 443 quic;
  listen 443 ssl;
  listen [::]:443 quic;
  listen [::]:443 ssl;
  http2 on;
  http3 off;
  {{ssl_certificate_key}}
  {{ssl_certificate}}
  server_name www.hippikasir.com;
  return 301 https://hippikasir.com$request_uri;
}

server {
  listen 80;
  listen [::]:80;
  listen 443 quic;
  listen 443 ssl;
  listen [::]:443 quic;
  listen [::]:443 ssl;
  http2 on;
  http3 off;
  {{ssl_certificate_key}}
  {{ssl_certificate}}
  server_name hippikasir.com www1.hippikasir.com;
  {{root}}

  {{nginx_access_log}}
  {{nginx_error_log}}

  if ($scheme != "https") {
    rewrite ^ https://$host$request_uri permanent;
  }

  location ~ /.well-known {
    auth_basic off;
    allow all;
  }

  {{settings}}

  include /etc/nginx/global_settings;

  location / {
    {{varnish_proxy_pass}}
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_hide_header X-Varnish;
    proxy_redirect off;
    proxy_max_temp_file_size 0;
    proxy_connect_timeout      720;
    proxy_send_timeout         720;
    proxy_read_timeout         720;
    proxy_buffer_size          128k;
    proxy_buffers              4 256k;
    proxy_busy_buffers_size    256k;
    proxy_temp_file_write_size 256k;
  }

  location ~* ^.+\.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|woff2|eot|mp4|ogg|ogv|webm|webp|zip|swf|map)$ {
    try_files $uri @backend;
    add_header Access-Control-Allow-Origin "*";
    add_header alt-svc 'h3=":443"; ma=86400';
    expires max;
    access_log off;
  }

  location @backend {
    {{varnish_proxy_pass}}
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_hide_header X-Varnish;
    proxy_redirect off;
  }

  if (-f $request_filename) {
    break;
  }
}

server {
  listen 8080;
  listen [::]:8080;
  server_name hippikasir.com www1.hippikasir.com;
  {{root}}

  try_files $uri $uri/ /index.php?$args;
  index index.php index.html;

  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_intercept_errors on;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    try_files $uri =404;
    fastcgi_read_timeout 3600;
    fastcgi_send_timeout 3600;
    fastcgi_param HTTPS "on";
    fastcgi_param SERVER_PORT 443;
    fastcgi_pass 127.0.0.1:{{php_fpm_port}};
    fastcgi_param PHP_VALUE "{{php_settings}}";
  }

  location ~* ^.+\.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|woff2|eot|mp4|ogg|ogv|webm|webp|zip|swf|map)$ {
    try_files $uri /index.php?$args;
    add_header Access-Control-Allow-Origin "*";
    expires max;
    access_log off;
  }

  if (-f $request_filename) {
    break;
  }
}
```

### Ringkasan perubahan vs config default CloudPanel
1. Blok **443**: tambah `try_files $uri @backend;` di location aset statis + blok `location @backend`.
2. Blok **8080**: tambah `try_files $uri /index.php?$args;` di location aset statis.

Keduanya membuat aset yang **tidak ada fisik** (di-serve PHP, mis. `livewire.js`)
diteruskan ke aplikasi alih-alih langsung 404.
