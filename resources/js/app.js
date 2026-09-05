import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { Chart, registerables } from 'chart.js';

window.Chart = Chart;
Chart.register(...registerables);

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
    },
});

// Forward broadcast notifications to Livewire so the NotificationBell can react
document.addEventListener('livewire:initialized', () => {
    const userIdMeta = document.querySelector('meta[name="auth-user-id"]');

    if (userIdMeta) {
        const userId = userIdMeta.content;

        window.Echo.private(`user.${userId}`)
            .notification((notification) => {
                Livewire.dispatch('notification-received', { notification });
            });
    }
});
