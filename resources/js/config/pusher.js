// resources/js/config/pusher.js

// The layouts render the key and cluster from the server's config, so the
// built assets don't hard-code them and one Docker image works with any Pusher
// app. VITE_PUSHER_* remains as a fallback for pages without the meta tags.
const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || undefined;

export const pusherConfig = {
    broadcaster: 'pusher',
    key: meta('pusher-key') ?? import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: meta('pusher-cluster') ?? import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    // authEndpoint: '/broadcasting/auth',
    // withCredentials: true,
    enabledTransports: ['ws', 'wss'],
};
