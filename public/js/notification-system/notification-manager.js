class NotificationManager {
    constructor() {
        this.sounds = {};
        this.config = {
            midtransBell: '/assets/sounds/bell_order.mp3',
        };

        this.init();
    }

    init() {
        // Preload sounds
        this.sounds.midtransBell = new Audio(this.config.midtransBell);
        this.sounds.midtransBell.preload = 'auto';

        // Setup listeners
        this.setupListeners();
    }

    setupListeners() {
        // Wait for Livewire to be ready
        const setup = () => {
            Livewire.on('play-midtrans-bell', () => {
                this.play('midtransBell');
            });
        };

        if (window.Livewire) {
            setup();
        } else {
            document.addEventListener('livewire:initialized', setup);
        }
    }

    play(key) {
        const audio = this.sounds[key];
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(e => {
                console.warn('Autoplay prevented:', e);
            });
        }
    }
}

window.NotificationManager = new NotificationManager();
