import './auth';
import 'trix';
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

// Re-apply the Flux appearance (dark/light/system) after every wire:navigate
// so the html.dark class is not lost when Livewire morphs the <html> element.
document.addEventListener('livewire:navigated', () => {
    const appearance = window.localStorage.getItem('flux.appearance') || 'system';

    if (appearance === 'dark') {
        document.documentElement.classList.add('dark');
    } else if (appearance === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        // system: follow OS preference
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
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
