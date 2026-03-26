<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        @php
            $setting = \App\Models\Setting::current();
            $brandName = (string) ($setting->store_name ?: config('app.name'));
            $brandAddress = $setting->address;
            $brandPhoneRaw = $setting->phone;

            $phoneDigits = $brandPhoneRaw ? preg_replace('/\D+/', '', $brandPhoneRaw) : null;
            $waPhone = null;
            if (is_string($phoneDigits) && $phoneDigits !== '') {
                if (str_starts_with($phoneDigits, '0')) {
                    $waPhone = '62'.substr($phoneDigits, 1);
                } elseif (str_starts_with($phoneDigits, '62')) {
                    $waPhone = $phoneDigits;
                } elseif (str_starts_with($phoneDigits, '8')) {
                    $waPhone = '62'.$phoneDigits;
                } else {
                    $waPhone = $phoneDigits;
                }
            }

            $brandTel = $waPhone ? '+'.$waPhone : null;
            $brandLogoUrl = $setting->store_logo ? \Illuminate\Support\Facades\Storage::disk('public')->url($setting->store_logo) : asset('images/logo/logo.svg');
            $brandMapsUrl = $brandAddress ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($brandAddress) : null;
            $brandWhatsappUrl = $waPhone ? 'https://wa.me/'.$waPhone.'?text='.rawurlencode('Halo '.$brandName.', saya ingin minta demo aplikasi kasir.') : null;

            $appUrlScheme = parse_url(config('app.url'), PHP_URL_SCHEME);
            $scheme = is_string($appUrlScheme) && $appUrlScheme !== '' ? $appUrlScheme : 'https';
            $adminDomain = (string) config('domains.admin', '');
            $adminSignInUrl = $adminDomain !== '' ? $scheme.'://'.$adminDomain.'/signin' : url('/admin/signin');

            $selfOrderDemoUrl = url('/order/scan');
        @endphp

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <title>{{ $brandName }}</title>
    </head>
    <body class="min-h-screen bg-gradient-to-b from-brand-25 via-white to-gray-50 text-gray-900 antialiased">
        <div class="pointer-events-none fixed inset-0 -z-10">
            <div class="absolute -top-24 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full bg-brand-200/30 blur-3xl"></div>
            <div class="absolute -bottom-32 left-[-80px] h-[420px] w-[420px] rounded-full bg-orange-200/25 blur-3xl"></div>
            <div class="absolute -bottom-40 right-[-120px] h-[520px] w-[520px] rounded-full bg-brand-300/20 blur-3xl"></div>
        </div>

        <div class="mx-auto max-w-6xl px-4">
            <header class="flex items-center justify-between py-6">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-9 w-auto" />
                </a>
                <div class="hidden items-center gap-6 text-sm font-semibold text-gray-700 md:flex">
                    <a href="#fitur" class="hover:text-gray-900">Fitur</a>
                    <a href="#preview" class="hover:text-gray-900">Preview</a>
                    <a href="#modul" class="hover:text-gray-900">Modul</a>
                    <a href="#faq" class="hover:text-gray-900">FAQ</a>
                    <a href="{{ $adminSignInUrl }}" class="hover:text-gray-900">Masuk Admin</a>
                    <a
                        href="{{ $brandWhatsappUrl ?: $selfOrderDemoUrl }}"
                        target="{{ $brandWhatsappUrl ? '_blank' : null }}"
                        rel="{{ $brandWhatsappUrl ? 'noopener noreferrer' : null }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-700"
                    >
                        Minta Demo
                    </a>
                </div>
                <a
                    href="{{ $brandWhatsappUrl ?: $selfOrderDemoUrl }}"
                    target="{{ $brandWhatsappUrl ? '_blank' : null }}"
                    rel="{{ $brandWhatsappUrl ? 'noopener noreferrer' : null }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-700 md:hidden"
                >
                    Minta Demo
                </a>
            </header>

            <main class="pb-16">
                <section class="relative overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-theme-lg backdrop-blur">
                    <div class="absolute inset-0">
                        <div class="absolute -right-20 -top-28 h-72 w-72 rounded-full bg-brand-200/30 blur-3xl"></div>
                        <div class="absolute -bottom-28 -left-28 h-80 w-80 rounded-full bg-orange-200/25 blur-3xl"></div>
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gray-200/80 to-transparent"></div>
                    </div>
                    <div class="relative grid gap-12 px-6 py-12 md:grid-cols-2 md:px-10 md:py-14">
                        <div class="flex flex-col justify-center">
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                                <span class="rounded-full bg-brand-50 px-3 py-1 text-brand-700 ring-1 ring-brand-200/70">Aplikasi Kasir</span>
                                <span class="rounded-full bg-orange-50 px-3 py-1 text-orange-800 ring-1 ring-orange-200/70">Self-order QR</span>
                                <span class="rounded-full bg-gray-50 px-3 py-1 text-gray-700 ring-1 ring-gray-200/80">Role & Approval</span>
                            </div>

                            <h1 class="mt-6 text-3xl font-extrabold tracking-tight text-gray-900 md:text-5xl">
                                Aplikasi kasir yang rapi untuk operasional yang cepat.
                            </h1>
                            <p class="mt-4 text-base font-medium text-gray-700 md:text-lg">
                                Kelola penjualan, produk, stok, voucher, member, dan laporan dalam satu sistem. Sertakan self-order untuk mempercepat layanan dan mengurangi antrean.
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a
                                    href="{{ $brandWhatsappUrl ?: $selfOrderDemoUrl }}"
                                    target="{{ $brandWhatsappUrl ? '_blank' : null }}"
                                    rel="{{ $brandWhatsappUrl ? 'noopener noreferrer' : null }}"
                                    class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-700"
                                >
                                    Minta Demo
                                </a>
                                <a
                                    href="{{ $selfOrderDemoUrl }}"
                                    class="inline-flex items-center justify-center rounded-2xl border border-gray-200/80 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-theme-xs hover:bg-gray-50"
                                >
                                    Coba Self-order
                                </a>
                            </div>

                            <div class="mt-7 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                                <div class="rounded-2xl border border-gray-100 bg-white/70 p-4">
                                    <div class="font-semibold text-gray-900">Cepat</div>
                                    <div class="mt-1 font-medium text-gray-600">Kasir & self-order dibuat ringkas.</div>
                                </div>
                                <div class="rounded-2xl border border-gray-100 bg-white/70 p-4">
                                    <div class="font-semibold text-gray-900">Terkontrol</div>
                                    <div class="mt-1 font-medium text-gray-600">Hak akses + approval diskon.</div>
                                </div>
                                <div class="rounded-2xl border border-gray-100 bg-white/70 p-4">
                                    <div class="font-semibold text-gray-900">Terukur</div>
                                    <div class="mt-1 font-medium text-gray-600">Laporan penjualan & profit.</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-center">
                            <div class="w-full max-w-md">
                                <div class="relative">
                                    <div class="absolute -left-10 -top-10 h-24 w-24 rotate-6 rounded-3xl bg-brand-200/40 blur-xl"></div>
                                    <div class="absolute -right-10 -bottom-10 h-28 w-28 -rotate-6 rounded-3xl bg-orange-200/35 blur-xl"></div>

                                    <div class="overflow-hidden rounded-[28px] border border-gray-100 bg-white shadow-theme-lg">
                                        <div class="relative">
                                            <div class="h-56 w-full bg-gradient-to-br from-gray-100 to-gray-200"></div>
                                            <div class="absolute inset-0 p-5">
                                                <div class="inline-flex items-center gap-2 rounded-full bg-white/85 px-3 py-1 text-xs font-semibold text-gray-900 ring-1 ring-white/60">
                                                    <span class="inline-flex h-2 w-2 rounded-full bg-brand-600"></span>
                                                    Admin Dashboard (Preview)
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-6">
                                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="text-xs font-semibold text-gray-700">Ringkasan hari ini</div>
                                                    <div class="text-xs font-semibold text-brand-700">Realtime</div>
                                                </div>
                                                <div class="mt-3 grid grid-cols-3 gap-2">
                                                    <div class="rounded-2xl bg-white p-3 ring-1 ring-gray-200/70">
                                                        <div class="text-[11px] font-semibold text-gray-600">Transaksi</div>
                                                        <div class="mt-1 h-3 w-16 rounded-full bg-gray-200"></div>
                                                    </div>
                                                    <div class="rounded-2xl bg-white p-3 ring-1 ring-gray-200/70">
                                                        <div class="text-[11px] font-semibold text-gray-600">Omzet</div>
                                                        <div class="mt-1 h-3 w-20 rounded-full bg-gray-200"></div>
                                                    </div>
                                                    <div class="rounded-2xl bg-white p-3 ring-1 ring-gray-200/70">
                                                        <div class="text-[11px] font-semibold text-gray-600">Profit</div>
                                                        <div class="mt-1 h-3 w-14 rounded-full bg-gray-200"></div>
                                                    </div>
                                                </div>
                                                <div class="mt-3 rounded-2xl bg-white p-3 ring-1 ring-gray-200/70">
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-[11px] font-semibold text-gray-600">Grafik penjualan</div>
                                                        <div class="text-[11px] font-semibold text-gray-500">7 hari</div>
                                                    </div>
                                                    <div class="mt-2 grid grid-cols-7 items-end gap-1">
                                                        <div class="h-6 rounded bg-gray-200"></div>
                                                        <div class="h-8 rounded bg-gray-200"></div>
                                                        <div class="h-5 rounded bg-gray-200"></div>
                                                        <div class="h-10 rounded bg-gray-200"></div>
                                                        <div class="h-7 rounded bg-gray-200"></div>
                                                        <div class="h-12 rounded bg-gray-200"></div>
                                                        <div class="h-9 rounded bg-gray-200"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                                                <a
                                                    href="{{ $adminSignInUrl }}"
                                                    class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-700"
                                                >
                                                    Masuk Admin
                                                </a>
                                                <a
                                                    href="#preview"
                                                    class="inline-flex w-full items-center justify-center rounded-2xl border border-gray-200/80 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-theme-xs hover:bg-gray-50"
                                                >
                                                    Lihat Preview
                                                </a>
                                            </div>
                                            <div class="mt-4 text-center text-xs font-medium text-gray-600">
                                                Preview bersifat ilustrasi. Foto/aset dapat Anda ganti nanti.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-3 gap-3">
                                        <div class="overflow-hidden rounded-2xl border border-white/50 bg-white/70 p-3 shadow-theme-xs backdrop-blur">
                                            <div class="h-14 w-full rounded-xl bg-gradient-to-br from-gray-100 to-gray-200"></div>
                                        </div>
                                        <div class="overflow-hidden rounded-2xl border border-white/50 bg-white/70 p-3 shadow-theme-xs backdrop-blur">
                                            <div class="h-14 w-full rounded-xl bg-gradient-to-br from-gray-100 to-gray-200"></div>
                                        </div>
                                        <div class="overflow-hidden rounded-2xl border border-white/50 bg-white/70 p-3 shadow-theme-xs backdrop-blur">
                                            <div class="h-14 w-full rounded-xl bg-gradient-to-br from-gray-100 to-gray-200"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="fitur" class="mt-10 grid gap-4 md:grid-cols-3">
                    <div class="rounded-[22px] border border-gray-100 bg-white p-5 shadow-theme-sm">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-200/60">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 7H4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6 7V20C6 20.5523 6.44772 21 7 21H17C17.5523 21 18 20.5523 18 20V7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 11H15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    <path d="M9 15H15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900">Kasir & Transaksi</div>
                                <div class="mt-1 text-sm font-medium leading-relaxed text-gray-600">
                                    Proses penjualan lebih cepat, detail transaksi rapi, siap untuk kebutuhan laporan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[22px] border border-gray-100 bg-white p-5 shadow-theme-sm">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-orange-50 text-orange-800 ring-1 ring-orange-200/70">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 7H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    <path d="M8 11H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    <path d="M10 15H14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                    <path d="M7 3H17C18.1046 3 19 3.89543 19 5V19C19 20.1046 18.1046 21 17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900">Self-order QR</div>
                                <div class="mt-1 text-sm font-medium leading-relaxed text-gray-600">
                                    Pelanggan pesan sendiri dari meja: alur menu, cart, dan checkout lebih jelas.
                                </div>
                                <a
                                    href="{{ $selfOrderDemoUrl }}"
                                    class="mt-4 inline-flex items-center justify-center rounded-2xl border border-gray-200/80 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-theme-xs hover:bg-gray-50"
                                >
                                    Buka Demo Self-order
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[22px] border border-gray-100 bg-white p-5 shadow-theme-sm">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gray-50 text-gray-700 ring-1 ring-gray-200/80">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 3L20 7.5V16.5L12 21L4 16.5V7.5L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 12L20 7.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 12V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 12L4 7.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900">Stok & Produksi</div>
                                <div class="mt-1 text-sm font-medium leading-relaxed text-gray-600">Pantau pergerakan stok, resep, purchase, dan stock opname.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="preview" class="mt-14 rounded-[28px] border border-white/60 bg-white/80 p-6 shadow-theme-lg backdrop-blur md:p-10">
                    <div class="grid gap-10 md:grid-cols-2 md:items-center">
                        <div>
                            <div class="text-xs font-semibold text-brand-700">Preview</div>
                            <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900 md:text-3xl">Visual yang membantu calon klien paham.</h2>
                            <p class="mt-3 text-sm font-medium text-gray-700 md:text-base">
                                Tampilkan gambaran dashboard admin dan self-order tanpa menempel foto asli. Anda bisa mengganti asetnya nanti.
                            </p>

                            <div class="mt-6 grid gap-3">
                                <div class="flex gap-4 rounded-2xl border border-gray-100 bg-white p-5">
                                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-200/60">1</div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Dashboard admin</div>
                                        <div class="mt-1 text-sm font-medium text-gray-600">Ringkasan penjualan, laporan, dan kontrol user.</div>
                                    </div>
                                </div>
                                <div class="flex gap-4 rounded-2xl border border-gray-100 bg-white p-5">
                                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-2xl bg-orange-50 text-orange-800 ring-1 ring-orange-200/70">2</div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Self-order</div>
                                        <div class="mt-1 text-sm font-medium text-gray-600">Menu, cart, dan checkout yang jelas untuk pelanggan.</div>
                                    </div>
                                </div>
                                <div class="flex gap-4 rounded-2xl border border-gray-100 bg-white p-5">
                                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-2xl bg-gray-50 text-gray-700 ring-1 ring-gray-200/80">3</div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Modul lengkap</div>
                                        <div class="mt-1 text-sm font-medium text-gray-600">Produk, stok, voucher, member, dan laporan terhubung.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                                <a
                                    href="{{ $adminSignInUrl }}"
                                    class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-700"
                                >
                                    Masuk Admin
                                </a>
                                <a
                                    href="#faq"
                                    class="inline-flex items-center justify-center rounded-2xl border border-gray-200/80 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-theme-xs hover:bg-gray-50"
                                >
                                    Baca FAQ
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center justify-center">
                            <div class="grid w-full max-w-md gap-4">
                                <div class="overflow-hidden rounded-[28px] border border-gray-100 bg-white shadow-theme-lg">
                                    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                                        <div class="text-sm font-semibold text-gray-900">Admin Dashboard</div>
                                        <div class="flex items-center gap-2">
                                            <div class="h-2.5 w-2.5 rounded-full bg-gray-300"></div>
                                            <div class="h-2.5 w-2.5 rounded-full bg-gray-300"></div>
                                            <div class="h-2.5 w-2.5 rounded-full bg-gray-300"></div>
                                        </div>
                                    </div>
                                    <div class="p-5">
                                        <div class="grid grid-cols-3 gap-3">
                                            <div class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200/70">
                                                <div class="text-xs font-semibold text-gray-700">Transaksi</div>
                                                <div class="mt-2 h-3 w-16 rounded-full bg-gray-200"></div>
                                            </div>
                                            <div class="rounded-2xl bg-brand-50 p-4 ring-1 ring-brand-200/60">
                                                <div class="text-xs font-semibold text-brand-700">Stok</div>
                                                <div class="mt-2 h-3 w-14 rounded-full bg-gray-200"></div>
                                            </div>
                                            <div class="rounded-2xl bg-orange-50 p-4 ring-1 ring-orange-200/70">
                                                <div class="text-xs font-semibold text-orange-800">Laporan</div>
                                                <div class="mt-2 h-3 w-20 rounded-full bg-gray-200"></div>
                                            </div>
                                        </div>
                                        <div class="mt-3 rounded-2xl border border-gray-100 bg-white p-4">
                                            <div class="flex items-center justify-between">
                                                <div class="text-xs font-semibold text-gray-700">Aktivitas terbaru</div>
                                                <div class="text-xs font-semibold text-gray-500">Hari ini</div>
                                            </div>
                                            <div class="mt-3 space-y-2">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="h-3 w-40 rounded-full bg-gray-200"></div>
                                                    <div class="h-3 w-16 rounded-full bg-gray-200"></div>
                                                </div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="h-3 w-44 rounded-full bg-gray-200"></div>
                                                    <div class="h-3 w-14 rounded-full bg-gray-200"></div>
                                                </div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="h-3 w-36 rounded-full bg-gray-200"></div>
                                                    <div class="h-3 w-20 rounded-full bg-gray-200"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-[28px] border border-gray-100 bg-white shadow-theme-lg">
                                    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                                        <div class="text-sm font-semibold text-gray-900">Self-order (Mobile)</div>
                                        <div class="text-xs font-semibold text-gray-500">Preview</div>
                                    </div>
                                    <div class="p-5">
                                        <div class="rounded-2xl border border-gray-100 bg-white p-4">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="h-3 w-32 rounded-full bg-gray-200"></div>
                                                <div class="h-8 w-8 rounded-2xl bg-gray-200"></div>
                                            </div>
                                            <div class="mt-4 grid grid-cols-2 gap-3">
                                                <div class="rounded-2xl bg-gray-50 p-3 ring-1 ring-gray-200/70">
                                                    <div class="h-16 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200"></div>
                                                    <div class="mt-3 h-3 w-24 rounded-full bg-gray-200"></div>
                                                    <div class="mt-2 h-3 w-16 rounded-full bg-gray-200"></div>
                                                </div>
                                                <div class="rounded-2xl bg-gray-50 p-3 ring-1 ring-gray-200/70">
                                                    <div class="h-16 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200"></div>
                                                    <div class="mt-3 h-3 w-20 rounded-full bg-gray-200"></div>
                                                    <div class="mt-2 h-3 w-14 rounded-full bg-gray-200"></div>
                                                </div>
                                            </div>
                                            <div class="mt-4 flex items-center justify-between rounded-2xl bg-brand-50 p-4 ring-1 ring-brand-200/60">
                                                <div>
                                                    <div class="text-xs font-semibold text-gray-900">Keranjang</div>
                                                    <div class="mt-1 h-3 w-24 rounded-full bg-gray-200"></div>
                                                </div>
                                                <div class="h-10 w-28 rounded-2xl bg-brand-600"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="modul" class="mt-14">
                    <div class="flex items-end justify-between gap-6">
                        <div>
                            <div class="text-xs font-semibold text-brand-700">Cakupan sistem</div>
                            <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900 md:text-3xl">Modul yang tersedia di proyek ini.</h2>
                            <p class="mt-3 max-w-2xl text-sm font-medium text-gray-700 md:text-base">
                                Landing page ini merangkum fitur inti dari sistem POS + self-order + backoffice yang sudah ada.
                            </p>
                        </div>
                    </div>

                    <div class="mt-7 grid gap-4 md:grid-cols-3">
                        <div class="rounded-[22px] border border-gray-100 bg-white p-6 shadow-theme-sm">
                            <div class="text-sm font-semibold text-gray-900">Penjualan</div>
                            <div class="mt-2 space-y-2 text-sm font-medium leading-relaxed text-gray-600">
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Kasir POS</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Transaksi & detail</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Approval diskon (permission)</div>
                            </div>
                        </div>
                        <div class="rounded-[22px] border border-gray-100 bg-white p-6 shadow-theme-sm">
                            <div class="text-sm font-semibold text-gray-900">Produk</div>
                            <div class="mt-2 space-y-2 text-sm font-medium leading-relaxed text-gray-600">
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Produk & kategori</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Varian & paket</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Resep & bahan</div>
                            </div>
                        </div>
                        <div class="rounded-[22px] border border-gray-100 bg-white p-6 shadow-theme-sm">
                            <div class="text-sm font-semibold text-gray-900">Stok & Pembelian</div>
                            <div class="mt-2 space-y-2 text-sm font-medium leading-relaxed text-gray-600">
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Pergerakan stok</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Supplier & purchase</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Stock opname</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <div class="rounded-[22px] border border-gray-100 bg-white p-6 shadow-theme-sm">
                            <div class="text-sm font-semibold text-gray-900">Member & Loyalty</div>
                            <div class="mt-2 space-y-2 text-sm font-medium leading-relaxed text-gray-600">
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Member & region</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Point system</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Riwayat transaksi</div>
                            </div>
                        </div>
                        <div class="rounded-[22px] border border-gray-100 bg-white p-6 shadow-theme-sm">
                            <div class="text-sm font-semibold text-gray-900">Voucher</div>
                            <div class="mt-2 space-y-2 text-sm font-medium leading-relaxed text-gray-600">
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Kampanye & kode voucher</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Redemption & performa</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Aturan penerapan voucher</div>
                            </div>
                        </div>
                        <div class="rounded-[22px] border border-gray-100 bg-white p-6 shadow-theme-sm">
                            <div class="text-sm font-semibold text-gray-900">Laporan & Kontrol</div>
                            <div class="mt-2 space-y-2 text-sm font-medium leading-relaxed text-gray-600">
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Sales & profit (Excel)</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Operating expenses</div>
                                <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>Role & permission</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-[28px] border border-gray-100 bg-white p-6 shadow-theme-sm md:p-10">
                        <div class="grid gap-8 md:grid-cols-2 md:items-center">
                            <div>
                                <div class="text-xs font-semibold text-brand-700">Operasional</div>
                                <h3 class="mt-2 text-xl font-extrabold tracking-tight text-gray-900 md:text-2xl">Didesain untuk alur kerja nyata.</h3>
                                <p class="mt-3 text-sm font-medium text-gray-700 md:text-base">
                                    Dari meja pelanggan sampai laporan manajemen, semuanya konsisten dalam satu gaya UI.
                                </p>
                                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200/70">
                                        <div class="text-sm font-semibold text-gray-900">QR meja</div>
                                        <div class="mt-1 text-sm font-medium text-gray-600">Scan untuk mulai self-order.</div>
                                    </div>
                                    <div class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200/70">
                                        <div class="text-sm font-semibold text-gray-900">Printer & struk</div>
                                        <div class="mt-1 text-sm font-medium text-gray-600">Sumber printer terkonfigurasi.</div>
                                    </div>
                                    <div class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200/70">
                                        <div class="text-sm font-semibold text-gray-900">Email receipt</div>
                                        <div class="mt-1 text-sm font-medium text-gray-600">Kirim bukti transaksi.</div>
                                    </div>
                                    <div class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200/70">
                                        <div class="text-sm font-semibold text-gray-900">Export Excel</div>
                                        <div class="mt-1 text-sm font-medium text-gray-600">Laporan siap dicetak.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-[28px] border border-gray-100 bg-gray-50 p-6">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-900">Komponen UI (Preview)</div>
                                    <div class="text-xs font-semibold text-gray-500">Grayscale</div>
                                </div>
                                <div class="mt-4 grid gap-3">
                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-gray-200/70">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="h-3 w-40 rounded-full bg-gray-200"></div>
                                            <div class="h-8 w-8 rounded-2xl bg-gray-200"></div>
                                        </div>
                                        <div class="mt-3 grid grid-cols-4 gap-2">
                                            <div class="h-10 rounded-2xl bg-gray-100"></div>
                                            <div class="h-10 rounded-2xl bg-gray-100"></div>
                                            <div class="h-10 rounded-2xl bg-gray-100"></div>
                                            <div class="h-10 rounded-2xl bg-gray-100"></div>
                                        </div>
                                    </div>
                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-gray-200/70">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="h-3 w-28 rounded-full bg-gray-200"></div>
                                            <div class="h-3 w-16 rounded-full bg-gray-200"></div>
                                        </div>
                                        <div class="mt-3 space-y-2">
                                            <div class="h-3 w-full rounded-full bg-gray-200"></div>
                                            <div class="h-3 w-11/12 rounded-full bg-gray-200"></div>
                                            <div class="h-3 w-10/12 rounded-full bg-gray-200"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="faq" class="mt-14 rounded-[28px] border border-gray-100 bg-white p-6 shadow-theme-sm md:p-10">
                    <div class="text-xs font-semibold text-brand-700">FAQ</div>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900 md:text-3xl">Pertanyaan yang sering muncul.</h2>

                    <div class="mt-6 grid gap-3">
                        <details class="group rounded-2xl border border-gray-100 bg-gray-50 p-5 open:bg-white">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-gray-900">
                                Bisa pakai kasir tanpa self-order?
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-gray-700 ring-1 ring-gray-200/80 group-open:rotate-45">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 5V19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                        <path d="M5 12H19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </span>
                            </summary>
                            <div class="mt-3 text-sm font-medium leading-relaxed text-gray-600">
                                Bisa. Self-order adalah opsi tambahan untuk mempercepat layanan. Kasir POS tetap bisa berjalan normal.
                            </div>
                        </details>

                        <details class="group rounded-2xl border border-gray-100 bg-gray-50 p-5 open:bg-white">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-gray-900">
                                Apakah ada hak akses untuk tiap role?
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-gray-700 ring-1 ring-gray-200/80 group-open:rotate-45">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 5V19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                        <path d="M5 12H19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </span>
                            </summary>
                            <div class="mt-3 text-sm font-medium leading-relaxed text-gray-600">
                                Ya. Sistem menyediakan role & permission untuk mengatur akses menu, laporan, dan proses yang butuh approval.
                            </div>
                        </details>

                        <details class="group rounded-2xl border border-gray-100 bg-gray-50 p-5 open:bg-white">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-gray-900">
                                Apakah bisa lihat laporan dan export?
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-gray-700 ring-1 ring-gray-200/80 group-open:rotate-45">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 5V19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                        <path d="M5 12H19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </span>
                            </summary>
                            <div class="mt-3 text-sm font-medium leading-relaxed text-gray-600">
                                Ya. Tersedia laporan penjualan/profit dan beberapa laporan dapat diunduh dalam format Excel.
                            </div>
                        </details>
                    </div>

                    <div class="mt-8 flex flex-col items-center justify-between gap-3 rounded-2xl bg-brand-50 p-6 ring-1 ring-brand-200/60 sm:flex-row">
                        <div class="text-sm font-semibold text-gray-900">Siap lihat demo untuk kebutuhan bisnis Anda?</div>
                        <a
                            href="{{ $brandWhatsappUrl ?: $selfOrderDemoUrl }}"
                            target="{{ $brandWhatsappUrl ? '_blank' : null }}"
                            rel="{{ $brandWhatsappUrl ? 'noopener noreferrer' : null }}"
                            class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-700"
                        >
                            Hubungi Kami
                        </a>
                    </div>
                </section>

                <footer class="mt-14 pb-6">
                    <div class="flex flex-col gap-4 rounded-[28px] border border-white/60 bg-white/70 p-6 shadow-theme-sm backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-9 w-auto" />
                            <div class="leading-tight">
                                <div class="text-sm font-semibold text-gray-900">{{ $brandName }}</div>
                                <div class="text-xs font-medium text-gray-600">Solusi POS untuk operasional yang lebih rapi.</div>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-semibold text-gray-700">
                            <a href="#fitur" class="hover:text-gray-900">Fitur</a>
                            <a href="#preview" class="hover:text-gray-900">Preview</a>
                            <a href="#modul" class="hover:text-gray-900">Modul</a>
                            <a href="#faq" class="hover:text-gray-900">FAQ</a>
                        </div>
                    </div>
                    <div class="mt-4 text-center text-xs font-medium text-gray-500">
                        © {{ date('Y') }} {{ $brandName }}. All rights reserved.
                    </div>
                </footer>
            </main>
        </div>
    </body>
</html>
