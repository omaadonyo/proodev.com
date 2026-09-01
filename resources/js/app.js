import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

if (window.Echo) {
    window.Echo.channel('feed').listen('.feed-event', (event) => {
        window.dispatchEvent(new CustomEvent('feed-event', { detail: event }));
    });
}

window.addEventListener('download', (event) => {
    const { content, filename, mime = 'text/markdown;charset=utf-8', base64 = false } = event.detail;
    if (!content || !filename) return;

    let blob;

    if (base64) {
        const binary = atob(content);
        const bytes = Uint8Array.from(binary, (c) => c.charCodeAt(0));
        blob = new Blob([bytes], { type: mime });
    } else {
        blob = new Blob([content], { type: mime });
    }

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
});
