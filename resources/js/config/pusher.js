// resources/js/config/pusher.js

// The layouts render where to connect from the server's config (the
// broadcast-meta component), so the built assets hard-code nothing and one
// Docker image works with any Pusher app or Reverb server. VITE_PUSHER_* remains
// as a fallback for pages without the meta tags.
const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || undefined;

function reverbConfig() {
    const scheme = meta('reverb-scheme') ?? 'https';
    const port = Number(meta('reverb-port')) || (scheme === 'https' ? 443 : 80);

    return {
        broadcaster: 'reverb',
        key: meta('reverb-key'),
        wsHost: meta('reverb-host'),
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    };
}

function pusherConfig() {
    return {
        broadcaster: 'pusher',
        key: meta('pusher-key') ?? import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: meta('pusher-cluster') ?? import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,
        enabledTransports: ['ws', 'wss'],
    };
}

/**
 * Echo's options for the broadcaster the server uses, or null when it broadcasts
 * nowhere a browser can listen (`log`, `null`): dashboards then update on reload
 * instead of Echo retrying a connection that cannot exist.
 */
export function echoConfig() {
    const driver = meta('broadcast-driver');

    if (driver === 'reverb') {
        return reverbConfig();
    }

    if (driver === 'pusher' || driver === undefined) {
        return pusherConfig();
    }

    return null;
}
