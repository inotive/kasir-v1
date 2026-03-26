<div class="flex min-h-screen flex-col font-poppins bg-gray-50 pb-10">
    <livewire:self-order.components.page-title-nav :title="'Edit Profil'" :hasBack="true" :hasFilter="false" />

    <div class="container mx-auto px-4 mt-4 space-y-4">
        @if (! empty($notice))
            <div class="bg-white border border-gray-200 rounded-2xl p-4">
                <div class="text-sm font-semibold text-gray-900">Info</div>
                <div class="text-xs text-gray-600 mt-1">{{ $notice }}</div>
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-2xl p-4 space-y-3">
            <div class="text-sm font-bold text-gray-900">Data Member</div>

            <div>
                <label class="text-xs font-semibold text-gray-600">Nama</label>
                <input
                    type="text"
                    wire:model.defer="name"
                    aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}"
                    class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20"
                    placeholder="Nama lengkap">
                <x-common.input-error for="name" />
            </div>

            <div>
                <label class="text-xs font-semibold text-gray-600">Email</label>
                <input
                    type="email"
                    wire:model.defer="email"
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('email') ? 'error-email' : '' }}"
                    class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20"
                    placeholder="email@contoh.com">
                <x-common.input-error for="email" />
            </div>

            <div>
                <label class="text-xs font-semibold text-gray-600">No. WhatsApp</label>
                <input
                    type="tel"
                    wire:model.defer="phone"
                    aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('phone') ? 'error-phone' : '' }}"
                    class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20"
                    placeholder="08xxxxxxxxxx">
                <x-common.input-error for="phone" />
            </div>

            <div>
                <label class="text-xs font-semibold text-gray-600">Provinsi</label>
                <select
                    wire:model.live="province"
                    aria-invalid="{{ $errors->has('province') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('province') ? 'error-province' : '' }}"
                    class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20">
                    <option value="">Pilih provinsi</option>
                    @foreach($provinces as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>
                <x-common.input-error for="province" />
            </div>

            <div>
                <label class="text-xs font-semibold text-gray-600">Kabupaten / Kota</label>
                <select
                    wire:model.live="regency"
                    @if (empty($province)) disabled @endif
                    aria-invalid="{{ $errors->has('regency') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('regency') ? 'error-regency' : '' }}"
                    class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20 disabled:opacity-60">
                    <option value="">Pilih kabupaten/kota</option>
                    @foreach($regencies as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                    @endforeach
                </select>
                <x-common.input-error for="regency" />
            </div>

            <div>
                <label class="text-xs font-semibold text-gray-600">Kecamatan</label>
                <select
                    wire:model.defer="district"
                    @if (empty($province) || empty($regency)) disabled @endif
                    aria-invalid="{{ $errors->has('district') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('district') ? 'error-district' : '' }}"
                    class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:border-primary-60 focus:ring-2 focus:ring-primary-20 disabled:opacity-60">
                    <option value="">Pilih kecamatan</option>
                    @foreach($districts as $d)
                        <option value="{{ $d }}">{{ $d }}</option>
                    @endforeach
                </select>
                <x-common.input-error for="district" />
            </div>

            <button
                type="button"
                wire:click="save"
                class="w-full rounded-xl bg-primary-60 hover:bg-primary-70 text-white py-3 font-bold text-sm">
                Simpan Perubahan
            </button>
        </div>
    </div>
</div>
