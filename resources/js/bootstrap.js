import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Axios = axios;
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

// Reverb config comes from the runtime blade config (window.WaGatewayConfig)
// injected by the app layout, falling back to Vite build-time env vars for
// pages rendered without the layout.
const wg = window.WaGatewayConfig ?? {};

// The browser MUST connect to the same hostname it loaded the page from —
// the WebSocket upgrade goes through nginx (/app/ -> reverb) on the same
// origin. Reverb's internal host/port (e.g. "reverb", 8080) are only valid
// inside the container network and must never reach the browser.
const host = window.location.hostname;
const isSecure = window.location.protocol === 'https:';
const port = isSecure ? 443 : 80;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key:         wg.reverb_app_key || import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:      host,
    wsPort:      port,
    wssPort:     port,
    forceTLS:    isSecure,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
});
