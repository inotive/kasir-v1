<header class="relative mb-6 overflow-hidden bg-gradient-to-br from-primary-60 via-primary-70 to-primary-80 rounded-b-[2rem] shadow-xl font-poppins">
    <div class="relative px-8 py-6">
        <nav class="relative flex items-center justify-center">
            <!-- Back button on the left -->
            <div class="absolute left-0 flex items-center">
                <a
                    wire:navigate
                    class="{{ $hasBack ? 'block' : 'invisible' }} grid aspect-square cursor-pointer place-content-center rounded-full bg-primary-10 p-3 transition-colors hover:bg-primary-20 focus:outline-none focus:ring-2 focus:ring-primary-20"
                    aria-label="Back"
                    href="{{ $backCart ? route('self-order.payment.cart') : ($backTransactions ? route('self-order.member.transactions') : route('self-order.home')) }}">
                    <img
                        src="{{ asset('assets/icons/arrow-left-icon.svg') }}"
                        alt="Back"
                        class="w-6 h-6" />
                </a>
            </div>

            <!-- Page title in the center -->
            <h1 class="text-lg font-bold text-white">{{ $title }}</h1>
        </nav>
    </div>
</header>
