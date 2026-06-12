<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
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
        // Gunakan logo hardcode sesuai permintaan agar tidak menampilkan Sellera
        $brandLogoUrl = asset('images/landing/logo_hippi.png');
        $brandTextUrl = asset('images/landing/Hippi_kasir.png');
        $brandWhatsappUrl = $waPhone ? 'https://wa.me/'.$waPhone.'?text='.rawurlencode('Halo '.$brandName.', saya tertarik dengan HIPPI Kasir.') : null;

        $appUrlScheme = parse_url(config('app.url'), PHP_URL_SCHEME);
        $scheme = is_string($appUrlScheme) && $appUrlScheme !== '' ? $appUrlScheme : 'https';
        $adminDomain = (string) config('domains.admin', '');
        $adminSignInUrl = $adminDomain !== '' ? $scheme.'://'.$adminDomain.'/signin' : url('/admin/signin');
    @endphp

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- AlpineJS for interactive elements like FAQ Accordion -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <title>HIPPI Kasir</title>
    <link rel="icon" type="image/png" href="{{ asset('images/landing/logo_hippi.png') }}">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1E293B;
        }

        .text-hippiblue { color: #5171f2; }
        .bg-hippiblue { background-color: #5171f2; }
        .text-hippidark { color: #313f8c; }
        .bg-hippidark { background-color: #313f8c; }
        
        /* Animations */
        @keyframes float-idle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        @keyframes float-idle-delayed {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float-idle 4s ease-in-out infinite;
        }
        .animate-float-delayed {
            animation: float-idle-delayed 5s ease-in-out infinite;
            animation-delay: 1s;
        }
        @keyframes bounce-x {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(-15px); }
        }
        .animate-bounce-x {
            animation: bounce-x 3s ease-in-out infinite;
        }
        
        .animate-on-scroll {
            opacity: 0;
            transition-property: opacity, transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 700ms;
        }
        .animate-on-scroll.is-visible {
            opacity: 1;
            transform: translate(0, 0) scale(1);
        }

        /* Custom Accordion CSS */
        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out, padding 0.3s ease-in-out;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
        }
        .faq-content.open {
            max-height: 500px;
            opacity: 1;
            padding-bottom: 1.5rem;
        }
    </style>
</head>
<body class="overflow-x-hidden antialiased bg-white selection:bg-[#6B8BFE] selection:text-white">

    <!-- Section 1: Navbar -->
    <nav class="fixed w-full z-50 bg-white/90 backdrop-blur-md border-b border-gray-100 transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex-shrink-0 flex items-center">
                    <a href="#" class="flex items-center gap-3">
                        <img src="{{ $brandLogoUrl }}" alt="Logo" class="h-10 w-auto">
                        <img src="{{ $brandTextUrl }}" alt="HIPPI Kasir" class="h-7 w-auto">
                    </a>
                </div>
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="#tentang" class="text-slate-700 hover:text-hippidark font-semibold transition-colors">Tentang Kami</a>
                    <a href="#fitur" class="text-slate-700 hover:text-hippidark font-semibold transition-colors">Fitur</a>
                    <a href="#harga" class="text-slate-700 hover:text-hippidark font-semibold transition-colors">Harga</a>
                </div>
                <div class="hidden md:flex items-center">
                    <a href="{{ $brandWhatsappUrl ?? '#' }}" target="_blank" class="bg-[#3FA0E4] hover:bg-blue-500 text-white px-5 py-2.5 rounded-full font-bold transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 2.98.97 4.29L1 23l6.91-1.89C9.17 21.68 10.55 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.39 0-2.73-.34-3.92-.95l-.28-.15-2.93.8.78-2.85-.16-.27A7.95 7.95 0 0 1 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/><path d="M16.5 14.5c-.25-.13-1.48-.73-1.71-.82-.23-.08-.4-.13-.56.13-.17.25-.66.82-.81.98-.15.17-.3.19-.55.06-1.57-.79-2.71-1.74-3.51-2.99-.15-.25-.02-.38.11-.5.11-.11.25-.29.37-.44.13-.15.17-.25.25-.42.08-.17.04-.32-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.02 2.61.13.17 1.78 2.72 4.31 3.82.6.26 1.07.41 1.44.53.6.19 1.15.16 1.58.1.48-.06 1.48-.61 1.69-1.2.21-.59.21-1.1.15-1.2-.06-.1-.23-.15-.48-.28z"/></svg>
                        Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Section 1: Hero -->
    <div id="tentang" class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid md:grid-cols-2 gap-12 md:gap-8 items-center">
                <!-- Text Area -->
                <div class="max-w-2xl text-center md:text-left mx-auto md:mx-0">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-50 border border-blue-100 mb-6">
                        <span class="w-2 h-2 rounded-full bg-[#3FA0E4]"></span>
                        <span class="text-sm font-bold text-[#3FA0E4]">Kepercayaan dan Solusi adalah Prioritas Kami</span>
                    </div>
                    
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight text-slate-800 leading-[1.15] mb-6">
                        Kasir Lebih <span class="text-[#6B8BFE]">Cepat</span><br />
                        Bisnis <span class="text-[#6B8BFE]">Lebih Untung</span>
                    </h1>
                    
                    <p class="text-lg text-slate-500 mb-8 max-w-xl leading-relaxed mx-auto md:mx-0">
                        Sistem kasir berbasis cloud yang membantu pemilik usaha mengelola penjualan, stok, laporan keuangan, kapan saja, di semua perangkat.
                    </p>
                    
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-4">
                        <a href="{{ $adminSignInUrl }}" class="bg-[#3FA0E4] hover:bg-blue-500 text-white px-7 py-3 rounded-full font-bold transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                            Mulai Dengan Gratis
                        </a>
                        <a href="{{ $brandWhatsappUrl ?? '#' }}" target="_blank" class="bg-white border-2 border-gray-200 text-slate-700 hover:border-[#6B8BFE] hover:text-[#6B8BFE] px-7 py-3 rounded-full font-bold transition-all flex items-center gap-2">
                            <svg class="w-5 h-5 text-hippidark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Konsultasi Gratis
                        </a>
                    </div>
                </div>
                
                <!-- 3D Assets Area -->
                <div class="relative h-[300px] md:h-[400px] lg:h-[500px] w-full hidden md:flex justify-center items-center mt-10 md:mt-0">
                    <img src="{{ asset('images/landing/Cash_register.png') }}" alt="Mesin Kasir" class="absolute z-20 w-[85%] md:w-[75%] lg:w-[85%] drop-shadow-2xl animate-float">
                    <img src="{{ asset('images/landing/Cursor.png') }}" alt="Kursor" class="absolute z-30 w-16 md:w-20 top-[15%] left-[10%] animate-bounce-x drop-shadow-lg">
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1 (Bottom): Categories -->
    <div class="bg-[#F8F9FA] py-8">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex flex-col sm:flex-row flex-wrap justify-center items-center gap-6 md:gap-20">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/landing/Toko_klontong.png') }}" alt="Toko Klontong" class="w-12 h-12 object-contain drop-shadow-md">
                    <span class="font-bold text-slate-800 text-lg">Toko Klontong</span>
                </div>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/landing/Restoran.png') }}" alt="Restorant" class="w-12 h-12 object-contain drop-shadow-md">
                    <span class="font-bold text-slate-800 text-lg">Restorant</span>
                </div>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/landing/Cafe.png') }}" alt="Cafe" class="w-12 h-12 object-contain drop-shadow-md">
                    <span class="font-bold text-slate-800 text-lg">Cafe</span>
                </div>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/landing/Butik.png') }}" alt="Butik" class="w-12 h-12 object-contain drop-shadow-md">
                    <span class="font-bold text-slate-800 text-lg">Butik</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Yang Kami Tawarkan -->
    <div id="fitur" class="py-24 bg-white relative overflow-hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="bg-[#F8F9FA] rounded-[2rem] p-8 md:p-12 pb-16 md:pb-24 relative shadow-sm">
                <!-- Decorative background shape -->
                <div class="absolute right-0 top-0 w-[45%] h-[80%] bg-[#E8EDFF] rounded-bl-[10rem] rounded-tl-full rounded-br-3xl z-0 opacity-80"></div>
                
                <div class="relative z-10">
                    <!-- Title -->
                    <div class="mb-10 animate-on-scroll translate-y-10 max-w-xl text-center lg:text-left mx-auto lg:mx-0">
                        <h2 class="text-2xl font-bold text-hippidark mb-3">Yang Kami Tawarkan</h2>
                        <p class="text-slate-500 leading-relaxed text-sm">
                            HIPPI hadir sebagai solusi kasir lengkap dan dinamis yang dikembangkan untuk terus tumbuh bersama skala bisnis Anda.
                        </p>
                    </div>

                    <!-- Cards Layout based on the design -->
                    <div class="flex flex-col gap-6">
                        <!-- Top Row: 3 Cards -->
                        <div class="grid md:grid-cols-3 gap-6">
                            <!-- Card 1 (Dark Blue) -->
                            <div class="bg-hippidark text-white rounded-[1.5rem] p-7 shadow-xl hover:-translate-y-2 hover:shadow-2xl !transition-all !duration-300 ease-out animate-on-scroll translate-y-10 group cursor-pointer border border-transparent hover:border-blue-400">
                                <div class="w-12 h-12 bg-[#4A5DCC] rounded-xl flex items-center justify-center mb-6 transition-all duration-300 ease-out group-hover:scale-110 group-hover:bg-[#5A6DDC]">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <h3 class="text-[17px] font-bold mb-3">Kasir Kilat</h3>
                                <p class="text-blue-100 text-[13px] leading-relaxed">Proses ratusan transaksi dalam hitungan detik. Antarmuka intuitif yang langsung bisa dipakai kasir baru tanpa pelatihan panjang.</p>
                            </div>

                            <!-- Card 2 -->
                            <div class="bg-white rounded-[1.5rem] p-7 shadow-md hover:-translate-y-2 hover:shadow-xl !transition-all !duration-300 ease-out animate-on-scroll translate-y-10 group cursor-pointer border border-transparent hover:border-blue-100" style="transition-delay: 100ms">
                                <div class="w-12 h-12 bg-[#E8EDFF] rounded-xl flex items-center justify-center mb-6 transition-all duration-300 ease-out group-hover:scale-110 group-hover:bg-[#D8E1FF]">
                                    <svg class="w-6 h-6 text-[#5171f2]" fill="currentColor" viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
                                </div>
                                <h3 class="text-[17px] font-bold text-hippidark mb-3">Laporan Real Time</h3>
                                <p class="text-slate-500 text-[13px] leading-relaxed">Pantau omset harian, mingguan, dan bulanan secara langsung dari HP. Export laporan ke Excel atau PDF kapan pun dibutuhkan.</p>
                            </div>

                            <!-- Card 3 -->
                            <div class="bg-white rounded-[1.5rem] p-7 shadow-md hover:-translate-y-2 hover:shadow-xl !transition-all !duration-300 ease-out animate-on-scroll translate-y-10 group cursor-pointer border border-transparent hover:border-blue-100" style="transition-delay: 200ms">
                                <div class="w-12 h-12 bg-[#E8EDFF] rounded-xl flex items-center justify-center mb-6 transition-all duration-300 ease-out group-hover:scale-110 group-hover:bg-[#D8E1FF]">
                                    <svg class="w-6 h-6 text-[#5171f2]" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 9h-2V7h-2v5H6v2h2v5h2v-5h2v-2z"/></svg>
                                </div>
                                <h3 class="text-[17px] font-bold text-hippidark mb-3">Manajemen Stok</h3>
                                <p class="text-slate-500 text-[13px] leading-relaxed">Notifikasi otomatis saat stok hampir habis. Kelola ribuan SKU dengan kategori, varian, dan satuan yang fleksibel dengan mudah.</p>
                            </div>
                        </div>

                        <!-- Bottom Row: 2 Cards -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Card 4 -->
                            <div class="bg-white rounded-[1.5rem] p-7 shadow-md hover:-translate-y-2 hover:shadow-xl !transition-all !duration-300 ease-out animate-on-scroll translate-y-10 group cursor-pointer border border-transparent hover:border-blue-100" style="transition-delay: 300ms">
                                <div class="w-12 h-12 bg-[#E8EDFF] rounded-xl flex items-center justify-center mb-6 transition-all duration-300 ease-out group-hover:scale-110 group-hover:bg-[#D8E1FF]">
                                    <svg class="w-6 h-6 text-[#5171f2]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                                <h3 class="text-[17px] font-bold text-hippidark mb-3">Multi-Karyawan</h3>
                                <p class="text-slate-500 text-[13px] leading-relaxed">Kelola hak akses kasir, supervisor, dan manajer. Rekam setiap aktivitas dengan log transaksi yang detail dan akurat.</p>
                            </div>

                            <!-- Card 5 -->
                            <div class="bg-white rounded-[1.5rem] p-7 shadow-md hover:-translate-y-2 hover:shadow-xl !transition-all !duration-300 ease-out animate-on-scroll translate-y-10 group cursor-pointer border border-transparent hover:border-blue-100" style="transition-delay: 400ms">
                                <div class="w-12 h-12 bg-[#E8EDFF] rounded-xl flex items-center justify-center mb-6 transition-all duration-300 ease-out group-hover:scale-110 group-hover:bg-[#D8E1FF]">
                                    <svg class="w-6 h-6 text-[#5171f2]" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8h-1V3H6v5H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zM8 5h8v3H8V5zm8 14H8v-4h8v4zm4-6h-4v-2H8v2H4v-4c0-.55.45-1 1-1h14c.55 0 1 .45 1 1v4z"/></svg>
                                </div>
                                <h3 class="text-[17px] font-bold text-hippidark mb-3">Struk Digital & Cetak</h3>
                                <p class="text-slate-500 text-[13px] leading-relaxed">Kirim struk via WhatsApp atau email otomatis. Mendukung semua printer thermal populer tanpa perlu driver tambahan.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Data & Laptop -->
    <div class="py-24 bg-[#F8F9FA] overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                
                <!-- Laptop Visual -->
                <div class="relative flex justify-center lg:justify-start w-full animate-on-scroll translate-y-10 lg:translate-y-0 lg:-translate-x-10">
                    <div class="relative w-full max-w-[650px] aspect-[16/10]">
                        <!-- Adjusted Screen Size to prevent overflow -->
                        <div class="absolute top-[9.5%] left-[12.2%] right-[12.2%] bottom-[14.5%] z-20 overflow-hidden bg-black rounded-t-[0.5rem]">
                            <img src="{{ asset('images/landing/Laptop_assets/screen.png') }}" class="w-full h-full object-cover" alt="Dashboard Screen">
                        </div>

                        <img src="{{ asset('images/landing/Laptop_assets/Macbook_Pro_16.png') }}" class="absolute inset-0 w-full h-full object-contain z-10" alt="Macbook Frame">
                        
                        <!-- Macbook Logo Text -->
                        <img src="{{ asset('images/landing/Laptop_assets/Macbook_Logo.png') }}" class="absolute bottom-[7%] left-1/2 -translate-x-1/2 h-2 sm:h-2.5 z-30" alt="Macbook Logo">
                        
                        <!-- Fixed shadow position to cover the gap -->
                        <img src="{{ asset('images/landing/Laptop_assets/shadow.png') }}" class="absolute bottom-[2.5%] left-0 w-full z-0 opacity-80" alt="Shadow">
                        
                        <!-- Tooltip Popup -->
                        <img src="{{ asset('images/landing/Laptop_assets/tooltip.png') }}" class="absolute top-[40%] right-[0%] w-48 z-40 drop-shadow-xl animate-float-delayed" alt="Sales Tooltip">
                    </div>
                </div>

                <!-- Text & Cards -->
                <div class="animate-on-scroll translate-y-10 lg:translate-y-0 lg:translate-x-10 pl-0 lg:pl-6 text-center lg:text-left max-w-md lg:max-w-none mx-auto lg:mx-0 w-full">
                    <h2 class="text-3xl font-bold text-hippidark mb-2">Keputusan Berdasarkan Data</h2>
                    <p class="text-slate-500 mb-10 text-sm">
                        HIPPI mengolah setiap transaksi menjadi mudah dibaca
                    </p>

                    <div class="space-y-4">
                        <!-- Card 1 -->
                        <div class="bg-[#3B4C96] border border-[#4356A5] rounded-[1.2rem] p-4 sm:p-6 flex items-center gap-4 sm:gap-6 shadow-xl hover:-translate-y-1 transition-all duration-700 ease-in-out cursor-pointer text-left">
                            <div class="flex-shrink-0 w-12 h-12 sm:w-14 sm:h-14 bg-[#5B80E6] rounded-[1rem] flex items-center justify-center shadow-inner">
                                <!-- Desktop & Phone Icon -->
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                  <path d="M4 5h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2v2h2v2H6v-2h2v-2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm0 8h12V7H4v6z"/>
                                  <path d="M16 11h4a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2zm2 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-[14px] sm:text-[17px] md:text-[18px] font-medium text-white leading-relaxed tracking-wide">
                                Dashboard real-time 24/7<br class="hidden sm:block">pantau omset dari mana saja
                            </h3>
                        </div>

                        <!-- Card 2 -->
                        <div class="bg-[#3B4C96] border border-[#4356A5] rounded-[1.2rem] p-4 sm:p-6 flex items-center gap-4 sm:gap-6 shadow-xl hover:-translate-y-1 transition-all duration-700 ease-in-out cursor-pointer text-left">
                            <div class="flex-shrink-0 w-12 h-12 sm:w-14 sm:h-14 bg-[#5B80E6] rounded-[1rem] flex items-center justify-center shadow-inner">
                                <!-- Chart Icon -->
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 19h14v2H5v-2zm2-5h2v4H7v-4zm4-5h2v9h-2V9zm4 3h2v6h-2v-6z"/>
                                    <path d="M6 10l4-4 4 4 5-5" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <h3 class="text-[14px] sm:text-[17px] md:text-[18px] font-medium text-white leading-relaxed tracking-wide">
                                Analisis produk & kategori<br class="hidden sm:block">terlaris untuk strategi fokus
                            </h3>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Section 4: Harga -->
    <div id="harga" class="py-24 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 animate-on-scroll translate-y-10">
                <h2 class="text-3xl font-bold text-hippidark mb-4">Pilihan Paket Sesuai <span class="text-[#6B8BFE]">Kebutuhan Bisnis Anda</span></h2>
                <p class="text-slate-500">
                    Harga transparan tanpa biaya tersembunyi.<br>
                    Bebas pilih paket tanpa kontrak jangka panjang.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 lg:gap-8 max-w-4xl mx-auto items-stretch">
                
                <!-- Paket UMUM -->
                <div class="border border-[#6B8BFE] bg-white rounded-xl p-6 md:p-8 flex flex-col shadow-sm animate-on-scroll translate-y-10 md:translate-y-0 md:-translate-x-10 hover:-translate-y-2 hover:shadow-2xl !transition-all !duration-700 ease-in-out cursor-pointer w-full max-w-md mx-auto">
                    <div class="bg-[#F4F7FF] text-hippidark text-xs font-extrabold tracking-wider px-4 py-2 rounded inline-block self-start mb-6">
                        UMUM
                    </div>
                    <p class="text-slate-600 text-sm mb-8 h-10">
                        Untuk semua individu dan pemula yang ingin memulai dengan pengelolaan Kasir.
                    </p>
                    <div class="border-t border-gray-100 my-4"></div>
                    <div class="mb-6">
                        <span class="text-4xl md:text-5xl font-extrabold text-[#6B8BFE]">Rp. 1,5 JT</span>
                        <div class="text-xs font-bold text-slate-800 mt-3">Per UMKM / Kasir</div>
                    </div>
                    <div class="border-t border-gray-100 my-4"></div>
                    
                    <ul class="space-y-4 text-sm font-medium text-hippidark flex-grow mb-8">
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-[#313f8c] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> 1 Perangkat Lunak Kasir</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-[#313f8c] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Laporan Lengkap + Ekspor</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-[#313f8c] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Manajemen Stok Lanjut</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-[#313f8c] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Manajemen Karyawan</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-[#313f8c] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Program Loyalitas</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-[#313f8c] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Support Prioritas WhatsApp</li>
                    </ul>
                    <a href="{{ $brandWhatsappUrl ?? '#' }}" target="_blank" class="block w-full bg-[#2E364A] hover:bg-slate-800 text-white text-center py-3.5 rounded font-bold transition-colors mb-2">Hubungi Admin</a>
                    <div class="text-center text-[11px] text-slate-500">Batalkan Kapan Saja</div>
                </div>

                <!-- Paket PROFESSIONAL -->
                <div class="bg-hippidark text-white rounded-xl p-6 md:p-8 flex flex-col shadow-2xl relative lg:scale-[1.03] transform origin-bottom animate-on-scroll translate-y-10 md:translate-y-0 md:translate-x-10 hover:-translate-y-2 hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] !transition-all !duration-700 ease-in-out cursor-pointer w-full max-w-md mx-auto">
                    <div class="bg-white text-hippidark text-xs font-extrabold tracking-wider px-4 py-2 rounded inline-block self-start mb-6">
                        MEMBER HIPPI
                    </div>
                    <p class="text-blue-100 text-sm mb-8 h-10">
                        Untuk semua member HIPPI profesional yang ingin pengelolaan Kasir lebih efisien
                    </p>
                    <div class="border-t border-[#4A5DCC] my-4"></div>
                    <div class="mb-6">
                        <div class="flex flex-wrap items-center gap-3 md:gap-5">
                            <div class="relative inline-block">
                                <span class="text-4xl md:text-5xl font-extrabold text-white">
                                    Rp. 1,5 JT
                                </span>
                                <!-- Strikethrough line -->
                                <div class="absolute left-0 right-0 top-1/2 h-[2px] bg-white opacity-90 -rotate-3"></div>
                            </div>
                            <div class="bg-[#22C55E] text-white text-sm md:text-lg font-extrabold px-3 py-1 md:px-4 md:py-1.5 rounded-lg shadow-lg">
                                Rp. 750.000
                            </div>
                        </div>
                        <div class="text-xs font-bold text-white mt-3">Per UMKM / Kasir</div>
                    </div>
                    <div class="border-t border-[#4A5DCC] my-4"></div>
                    
                    <ul class="space-y-4 text-sm font-medium text-white flex-grow mb-8">
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-white flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> 1 Perangkat Lunak Kasir</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-white flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Laporan Lengkap + Ekspor</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-white flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Manajemen Stok Lanjut</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-white flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Manajemen Karyawan</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-white flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Support Prioritas WhatsApp</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-white flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Program Loyalitas</li>
                    </ul>
                    <a href="{{ $adminSignInUrl }}" class="block w-full bg-[#FCD34D] hover:bg-yellow-400 text-hippidark text-center py-3.5 rounded font-bold transition-colors mb-2">Klaim Diskon Member</a>
                    <div class="text-center text-[11px] text-blue-200">Validasi Anggota via Admin</div>
                </div>

            </div>
        </div>
    </div>

    <!-- Section 5: FAQ -->
    <div class="py-24 bg-white animate-on-scroll translate-y-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-hippidark mb-4">Pertanyaan Tentang <span class="text-[#6B8BFE]">HIPPI Kasir?</span></h2>
                <p class="text-slate-500 text-sm">Pertanyaan yang paling sering diajukan untuk membantu Anda memahami</p>
            </div>

            <!-- Removed x-collapse since the plugin is not installed. Replaced with pure CSS class "open" toggling via AlpineJS. -->
            <div x-data="{ active: 1 }" class="space-y-4">
                @php
                $faqs = [
                    [
                        'q' => 'Apakah data transaksi saya aman jika perangkat atau HP saya hilang?',
                        'a' => 'Sangat aman. Karena HIPPI berbasis Cloud, seluruh data transaksi, stok, dan laporan Anda tersimpan secara real-time di server kami yang terenkripsi, bukan di memori perangkat.'
                    ],
                    [
                        'q' => 'Printer thermal jenis apa saja yang mendukung cetak struk di HIPPI?',
                        'a' => 'HIPPI Kasir mendukung hampir semua jenis printer thermal bluetooth/USB standar ukuran 58mm maupun 80mm yang umum dijual di pasaran.'
                    ],
                    [
                        'q' => 'Apakah HIPPI bisa digunakan tanpa koneksi internet?',
                        'a' => 'Saat ini HIPPI Kasir memerlukan koneksi internet aktif agar dapat mensinkronisasikan data transaksi dan stok barang secara real-time antar perangkat.'
                    ],
                    [
                        'q' => 'Apakah ada biaya tambahan untuk instalasi atau pelatihan karyawan?',
                        'a' => 'Tidak ada biaya tambahan. Instalasi bisa dilakukan sendiri dengan mengunduh aplikasi, dan materi pelatihan sudah tersedia secara gratis.'
                    ],
                    [
                        'q' => 'Apakah HIPPI bisa digunakan tanpa koneksi internet? (Copy)',
                        'a' => 'Pertanyaan berulang pada gambar (kolom kanan), asumsikan konten yang sama.'
                    ],
                    [
                        'q' => 'Bagaimana cara memindahkan data stok barang saya dari Excel ke HIPPI?',
                        'a' => 'Kami menyediakan fitur Import Excel di menu manajemen stok. Anda cukup mengunduh format template yang disediakan, mengisinya, dan mengunggahnya kembali.'
                    ],
                    [
                        'q' => 'Apakah saya bisa memantau banyak cabang sekaligus dalam satu akun?',
                        'a' => 'Ya, fitur multi-cabang memungkinkan Anda memantau seluruh aktivitas transaksi dari semua outlet Anda dalam satu dashboard terpusat.'
                    ],
                    [
                        'q' => 'Bagaimana sistem pembayaran langganannya, apakah otomatis memotong saldo?',
                        'a' => 'Pembayaran dilakukan secara manual sesuai tagihan (transfer bank atau e-wallet). Kami tidak akan otomatis memotong saldo rekening Anda.'
                    ]
                ];
                @endphp

                <div class="grid md:grid-cols-2 gap-4 items-start">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        @foreach([0, 1, 2, 3] as $idx)
                        <div class="border border-gray-100 rounded-[1rem] shadow-sm transition-colors duration-300"
                             :class="active === {{ $idx + 1 }} ? 'bg-[#F4F7FF]' : 'bg-white'">
                            <button @click="active = active === {{ $idx + 1 }} ? null : {{ $idx + 1 }}" class="w-full text-left p-5 flex items-start justify-between focus:outline-none gap-4">
                                <span class="font-bold text-[14px] leading-snug" :class="active === {{ $idx + 1 }} ? 'text-[#6B8BFE]' : 'text-hippidark'">{{ $faqs[$idx]['q'] }}</span>
                                <div class="flex-shrink-0 w-6 h-6 rounded flex items-center justify-center transition-colors mt-0.5"
                                     :class="active === {{ $idx + 1 }} ? 'bg-[#3FA0E4] text-white' : 'bg-[#F4F7FF] text-gray-500'">
                                    <span x-show="active !== {{ $idx + 1 }}" class="text-xl leading-none font-light">+</span>
                                    <span x-show="active === {{ $idx + 1 }}" class="text-xl leading-none font-light">-</span>
                                </div>
                            </button>
                            <div class="faq-content px-5 text-slate-500 leading-relaxed text-[13px]" :class="active === {{ $idx + 1 }} ? 'open' : ''">
                                {{ $faqs[$idx]['a'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        @foreach([4, 5, 6, 7] as $idx)
                        <div class="border border-gray-100 rounded-[1rem] shadow-sm transition-colors duration-300"
                             :class="active === {{ $idx + 1 }} ? 'bg-[#F4F7FF]' : 'bg-white'">
                            <button @click="active = active === {{ $idx + 1 }} ? null : {{ $idx + 1 }}" class="w-full text-left p-5 flex items-start justify-between focus:outline-none gap-4">
                                <span class="font-bold text-[14px] leading-snug" :class="active === {{ $idx + 1 }} ? 'text-[#6B8BFE]' : 'text-hippidark'">{{ $faqs[$idx]['q'] }}</span>
                                <div class="flex-shrink-0 w-6 h-6 rounded flex items-center justify-center transition-colors mt-0.5"
                                     :class="active === {{ $idx + 1 }} ? 'bg-[#3FA0E4] text-white' : 'bg-[#F4F7FF] text-gray-500'">
                                    <span x-show="active !== {{ $idx + 1 }}" class="text-xl leading-none font-light">+</span>
                                    <span x-show="active === {{ $idx + 1 }}" class="text-xl leading-none font-light">-</span>
                                </div>
                            </button>
                            <div class="faq-content px-5 text-slate-500 leading-relaxed text-[13px]" :class="active === {{ $idx + 1 }} ? 'open' : ''">
                                {{ $faqs[$idx]['a'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Section 6: CTA Siap Bawa Bisnismu -->
    <div class="relative bg-hippidark overflow-hidden py-24 md:py-32">
        <div class="absolute -left-32 top-1/2 -translate-y-1/2 w-96 h-96 bg-[#4A5DCC] rounded-full blur-none opacity-40"></div>
        <div class="absolute -right-40 -bottom-20 w-[600px] h-[600px] bg-[#4A5DCC] rounded-full blur-none opacity-40"></div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center animate-on-scroll translate-y-10">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Siap Bawa Bisnismu ke Level Berikutnya?</h2>
            <p class="text-blue-100 text-sm md:text-base mb-12 font-medium">
                Bergabunglah dengan ribuan pemilik bisnis yang sudah<br>merasakan efisiensi operasional harian bersama HIPPI.
            </p>

            <div class="relative h-[250px] md:h-[300px] flex justify-center items-center mb-12">
                <img src="{{ asset('images/landing/statistic_chart.png') }}" alt="Statistic Chart" class="absolute z-20 w-48 md:w-56 drop-shadow-2xl animate-float">
                <img src="{{ asset('images/landing/rocket.png') }}" alt="Rocket" class="absolute z-10 w-24 md:w-32 top-10 left-[20%] md:left-[25%] -rotate-12 animate-float">
                <img src="{{ asset('images/landing/money.png') }}" alt="Money" class="absolute z-30 w-16 md:w-20 bottom-10 right-[25%] md:right-[30%] animate-float-delayed">
            </div>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="{{ $adminSignInUrl }}" class="w-full sm:w-auto bg-[#3FA0E4] hover:bg-blue-500 text-white px-8 py-3.5 rounded-full font-bold transition-all shadow-lg flex items-center justify-center gap-2 text-sm">
                    <svg class="w-[20px] h-[20px] flex-none order-first" fill="currentColor" viewBox="0 0 24 24"><path d="M11 21.883l-6.235-7.527-.765.144 5.621 9.5 1.379-2.117zm11.144-20.941c-2.316-1.032-8.259-1.343-13.125 3.523-2.919 2.92-4.514 6.272-4.874 8.926L2.49 14.82l2.457 3.151 5.539-5.742c.712.75 1.816 1.409 3.246 1.921l-5.69 5.86 3.14 2.44 1.464-1.605c2.721-.315 6.136-1.921 9.077-4.862 5.093-5.092 4.743-11.527 3.421-14.041zm-6.52 6.257c-1.218 1.219-3.196 1.219-4.414 0-1.218-1.218-1.218-3.196 0-4.414 1.218-1.218 3.196-1.218 4.414 0 1.218 1.218 1.218 3.196 0 4.414z"/></svg>
                    Mulai 14 Hari Gratis
                </a>
                <a href="{{ $brandWhatsappUrl ?? '#' }}" target="_blank" class="w-full sm:w-auto bg-white text-hippidark hover:bg-gray-50 px-8 py-3.5 rounded-full font-bold transition-all shadow-lg flex items-center justify-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 2.98.97 4.29L1 23l6.91-1.89C9.17 21.68 10.55 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.39 0-2.73-.34-3.92-.95l-.28-.15-2.93.8.78-2.85-.16-.27A7.95 7.95 0 0 1 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/><path d="M16.5 14.5c-.25-.13-1.48-.73-1.71-.82-.23-.08-.4-.13-.56.13-.17.25-.66.82-.81.98-.15.17-.3.19-.55.06-1.57-.79-2.71-1.74-3.51-2.99-.15-.25-.02-.38.11-.5.11-.11.25-.29.37-.44.13-.15.17-.25.25-.42.08-.17.04-.32-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.02 2.61.13.17 1.78 2.72 4.31 3.82.6.26 1.07.41 1.44.53.6.19 1.15.16 1.58.1.48-.06 1.48-.61 1.69-1.2.21-.59.21-1.1.15-1.2-.06-.1-.23-.15-.48-.28z"/></svg>
                    Konsultasi Gratis Via WA
                </a>
            </div>
        </div>
    </div>

    <!-- Section 7: Footer -->
    <footer class="bg-white relative overflow-hidden pt-20 pb-10 border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-hippidark mb-4">Kepercayaan & Solusi</h2>
                <p class="text-slate-500 max-w-xl mx-auto text-sm">
                    Sistem kasir berbasis cloud yang membantu pemilik usaha mengelola penjualan, stok, laporan keuangan, kapan saja, di semua perangkat.
                </p>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-center gap-6 mt-16 relative">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <img src="{{ $brandLogoUrl }}" alt="Logo" class="h-8 md:h-10 w-auto">
                    <img src="{{ $brandTextUrl }}" alt="HIPPI Kasir" class="h-6 md:h-7 w-auto">
                </div>
                
                <!-- Copyright -->
                <div class="text-sm font-bold text-hippidark text-center md:absolute md:left-1/2 md:-translate-x-1/2">
                    © 2026 HIPPI Kasir. All Rights Reserved.
                </div>
                
                <!-- Social Icons -->
                <div class="flex items-center gap-4">
                    <a href="#" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-hippidark hover:border-[#3FA0E4] hover:text-[#3FA0E4] transition-colors bg-white shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-hippidark hover:border-[#3FA0E4] hover:text-[#3FA0E4] transition-colors bg-white shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-hippidark hover:border-[#3FA0E4] hover:text-[#3FA0E4] transition-colors bg-white shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723 10.054 10.054 0 01-3.127 1.195 4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Giant Watermark HIPPI -->
        <div class="absolute bottom-[-10%] w-full flex justify-center items-end pointer-events-none select-none z-0">
            <h1 class="text-[clamp(10rem,25vw,30rem)] font-bold text-gray-100 leading-none tracking-tighter opacity-70">
                HIPPI
            </h1>
        </div>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.animate-on-scroll').forEach((elem) => {
                observer.observe(elem);
            });
            
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 10) {
                    navbar.classList.add('shadow-sm');
                } else {
                    navbar.classList.remove('shadow-sm');
                }
            });
        });
    </script>
</body>
</html>
