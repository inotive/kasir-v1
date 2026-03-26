@props([
    'title' => '',
    'showClose' => true,
])

<div x-show="open" x-cloak class="fixed inset-0 px-4 z-[70] flex items-center justify-center sm:items-center sm:p-4">
    <div class="absolute inset-0 bg-black/50" @click="{{ $showClose ? 'open = false' : '' }}"></div>
    <div class="relative w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl max-w-sm sm:rounded-2xl">
        <div class="p-5">
            {{ $slot }}
        </div>
    </div>
</div>
