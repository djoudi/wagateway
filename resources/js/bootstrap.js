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
const host = wg.reverb_host || import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const port = wg.reverb_port || import.meta.env.VITE_REVERB_PORT || 80;
const scheme = wg.reverb_scheme || import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');

window.Echo = new Echo({
    broadcaster: 'reverb',
    key:         wg.reverb_app_key || import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:      host,
    wsPort:      port,
    wssPort:     port,
    forceTLS:    scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
});
