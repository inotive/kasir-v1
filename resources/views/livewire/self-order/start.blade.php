<div class="min-h-screen bg-gray-50 font-poppins">
    <header class="bg-white border-b border-gray-200">
        <div class="px-4 py-4 flex items-center gap-3">
            <div class="bg-white p-2 rounded-xl border border-gray-200">
                <img src="{{ asset('storage/' . $setting->store_logo ?? 'logo.png') }}" alt="{{ $setting->store_name ?? 'Store' }}" class="w-8 h-8">
            </div>
            <div class="min-w-0">
                <div class="text-sm font-bold text-gray-900 truncate">{{ $setting->store_name ?? 'Self Order' }}</div>
                <div class="text-xs text-gray-500 truncate">Meja #{{ $tableNumber ?? '-' }}</div>
            </div>
        </div>
    </header>

    <main class="px-4 py-4 space-y-4 pb-10">
        <div class="bg-white border border-gray-200 rounded-2xl p-4">
            <div class="text-sm font-bold text-gray-900">Mulai</div>
            <div class="text-xs text-gray-500 mt-1">Pilih cara masuk untuk mulai memesan.</div>

            <div class="grid grid-cols-2 gap-2 mt-4">
                <button
                    type="button"
                    wire:click="setTab('guest')"
                    class="{{ $tab === 'guest' ? 'bg-primary-60 text-white border-primary-60' : 'bg-white text-gray-900 border-gray-200' }} border rounded-xl px-3 py-3 text-sm font-semibold">
                    Sebagai Tamu
                </button>
                <button
                    type="button"
                    wire:click="setTab('member')"
                    class="{{ $tab === 'member' || $tab === 'register' ? 'bg-primary-60 text-white border-primary-60' : 'bg-white text-gray-900 border-gray-200' }} border rounded-xl px-3 py-3 text-sm font-semibold">
                    Sebagai Member
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-4">
            <div class="text-xs font-semibold text-gray-700">Benefit Member</div>
            <div class="mt-2 grid grid-cols-3 gap-2">
                <div class="rounded-xl bg-gray-50 border border-gray-200 p-3">
                    <div class="text-[10px] text-gray-500">Poin</div>
                    <div class="text-sm font-bold text-gray-900 mt-1">Reward</div>
                </div>
                <div class="rounded-xl bg-gray-50 border border-gray-200 p-3">
                    <div class="text-[10px] text-gray-500">Promo</div>
                    <div class="text-sm font-bold text-gray-900 mt-1">Khusus</div>
                </div>
                <div class="rounded-xl bg-gray-50 border border-gray-200 p-3">
                    <div class="text-[10px] text-gray-500">Riwayat</div>
                    <div class="text-sm font-bold text-gray-900 mt-1">Order</div>
                </div>
            </div>
        </div>

        @if (!empty($notice))
            <div class="bg-white border border-gray-200 rounded-2xl p-4">
                <div class="text-sm font-semibold text-gray-900">Info</div>
                <div class="text-xs text-gray-600 mt-1">{{ $notice }}</div>
            </div>
        @endif

        @if ($tab === 'guest')
            <div class="bg-white border border-gray-200 rounded-2xl p-4 space-y-3">
                <div class="text-sm font-bold text-gray-900">Data Pemesan (Tamu)</div>
                <div>
                    <label class="text-xs font-semibold text-gray-600">Nama</label>
                    <input
                        type="text"
                        wire:model.defer="guest_name"
                        aria-invalid="{{ $errors->has('guest_name') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('guest_name') ? 'error-guest_name' : '' }}"
                        class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20"
                        placeholder="Nama pemesan">
                    <x-common.input-error for="guest_name" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600">No. WhatsApp</label>
                    <input
                        type="tel"
                        wire:model.defer="guest_phone"
                        aria-invalid="{{ $errors->has('guest_phone') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('guest_phone') ? 'error-guest_phone' : '' }}"
                        class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20"
                        placeholder="08xxxxxxxxxx">
                    <x-common.input-error for="guest_phone" />
                </div>
                <button
                    type="button"
                    wire:click="proceedGuest"
                    class="w-full rounded-xl bg-primary-60 hover:bg-primary-70 text-white py-3 font-bold text-sm">
                    Lanjut Pilih Menu
                </button>
            </div>
        @endif

        @if ($tab === 'member')
            <div class="bg-white border border-gray-200 rounded-2xl p-4 space-y-3">
                <div class="text-sm font-bold text-gray-900">Masuk Member</div>
                <div class="text-xs text-gray-500">Masukkan email atau nomor WhatsApp member.</div>
                <div>
                    <label class="text-xs font-semibold text-gray-600">Email / WhatsApp</label>
                    <input
                        type="text"
                        wire:model.defer="member_identifier"
                        aria-invalid="{{ $errors->has('member_identifier') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('member_identifier') ? 'error-member_identifier' : '' }}"
                        class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20"
                        placeholder="email@contoh.com / 08xxxxxxxxxx">
                    <x-common.input-error for="member_identifier" />
                </div>
                <button
                    type="button"
                    wire:click="proceedMember"
                    class="w-full rounded-xl bg-primary-60 hover:bg-primary-70 text-white py-3 font-bold text-sm">
                    Masuk & Lanjut Pesan
                </button>
                <button
                    type="button"
                    wire:click="setTab('register')"
                    class="w-full rounded-xl bg-white border border-gray-200 hover:border-primary-40 text-gray-900 py-3 font-bold text-sm">
                    Daftar Member Baru
                </button>
            </div>
        @endif

        @if ($tab === 'register')
            <div class="bg-white border border-gray-200 rounded-2xl p-4 space-y-3">
                <div class="text-sm font-bold text-gray-900">Daftar Member</div>
                <div class="text-xs text-gray-500">Lengkapi data untuk mendapatkan promo dan poin.</div>

                <div>
                    <label class="text-xs font-semibold text-gray-600">Nama</label>
                    <input
                        type="text"
                        wire:model.defer="register_name"
                        aria-invalid="{{ $errors->has('register_name') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('register_name') ? 'error-register_name' : '' }}"
                        class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20"
                        placeholder="Nama lengkap">
                    <x-common.input-error for="register_name" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-600">Email</label>
                    <input
                        type="email"
                        wire:model.defer="register_email"
                        aria-invalid="{{ $errors->has('register_email') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('register_email') ? 'error-register_email' : '' }}"
                        class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20"
                        placeholder="email@contoh.com">
                    <x-common.input-error for="register_email" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-600">No. WhatsApp</label>
                    <input
                        type="tel"
                        wire:model.defer="register_phone"
                        aria-invalid="{{ $errors->has('register_phone') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('register_phone') ? 'error-register_phone' : '' }}"
                        class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20"
                        placeholder="08xxxxxxxxxx">
                    <x-common.input-error for="register_phone" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-600">Provinsi</label>
                    <select
                        wire:model.live="register_province"
                        aria-invalid="{{ $errors->has('register_province') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('register_province') ? 'error-register_province' : '' }}"
                        class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20">
                        <option value="">Pilih provinsi</option>
                        @foreach($provinces as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                    <x-common.input-error for="register_province" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-600">Kabupaten / Kota</label>
                    <select
                        wire:model.live="register_regency"
                        @if (empty($register_province)) disabled @endif
                        aria-invalid="{{ $errors->has('register_regency') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('register_regency') ? 'error-register_regency' : '' }}"
                        class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20 disabled:opacity-60">
                        <option value="">Pilih kabupaten/kota</option>
                        @foreach($regencies as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                    <x-common.input-error for="register_regency" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-600">Kecamatan</label>
                    <select
                        wire:model.defer="register_district"
                        @if (empty($register_province) || empty($register_regency)) disabled @endif
                        aria-invalid="{{ $errors->has('register_district') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('register_district') ? 'error-register_district' : '' }}"
                        class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20 disabled:opacity-60">
                        <option value="">Pilih kecamatan</option>
                        @foreach($districts as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </select>
                    <x-common.input-error for="register_district" />
                </div>

                <button
                    type="button"
                    wire:click="registerMember"
                    class="w-full rounded-xl bg-primary-60 hover:bg-primary-70 text-white py-3 font-bold text-sm">
                    Daftar & Lanjut Pesan
                </button>

                <button
                    type="button"
                    wire:click="setTab('member')"
                    class="w-full rounded-xl bg-white border border-gray-200 hover:border-primary-40 text-gray-900 py-3 font-bold text-sm">
                    Kembali
                </button>
            </div>
        @endif
    </main>
</div>
