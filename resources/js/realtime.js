import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const key = import.meta.env.VITE_PUSHER_APP_KEY;
const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;
const authenticated = window.APP_AUTHENTICATED === true;

if (key && cluster) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key,
        cluster,
        forceTLS: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
        },
    });

    if (window.APP_CAN_CASHIER_ORDERS === true) {
        window.Echo.private('cashier.orders')
            .listen('.midtrans.paid', (e) => {
                if (window.NotificationManager?.play) {
                    window.NotificationManager.play('midtransBell');
                }

                window.dispatchEvent(new CustomEvent('midtrans-paid', { detail: e || {} }));

                if (window.toast) {
                    const title = 'Notifikasi Self Order';
                    const message = 'Ada transaksi online baru (Lunas)!';
                    window.toast({ type: 'info', title, message, timeout: 5000 });
                }
            });

        window.Echo.private('cashier.orders')
            .listen('.self_order.cash_pending', (e) => {
                if (window.toast) {
                    const title = 'Notifikasi Self Order';
                    const message = 'Ada transaksi online baru (Pending)!';
                    window.toast({ type: 'info', title, message, timeout: 5000 });
                }
            });
    }
}
