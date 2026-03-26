<div
    wire:click="openVariants"
    class="{{ $isGrid
        ? 'col-span-1 flex min-w-[40%] max-w-[180px] flex-1 flex-col rounded-2xl bg-white border border-gray-200 font-poppins'
        : 'w-full flex items-center p-2 bg-white rounded-2xl border border-gray-200 gap-3 cursor-pointer'
    }}">

    <div class="{{ $isGrid ? 'relative aspect-square overflow-hidden rounded-2xl' : 'relative w-16 h-16 flex-shrink-0' }}">
        <img
            src="{{ Storage::url($data->image) }}"
            alt="{{ $data->name }}"
            class="{{ $isGrid ? 'absolute inset-0 w-full h-full rounded-2xl object-cover' : 'w-full h-full rounded-xl object-cover' }}" />
    </div>

    <div class="{{ $isGrid ? 'py-2 px-3 flex-1 min-w-0' : 'flex-1 min-w-0' }}">
        <div class="text-sm font-semibold text-gray-900 line-clamp-2">{{ $data->name }}</div>
        <div class="text-xs text-gray-500 mt-1">Pilih varian</div>
    </div>
</div>
