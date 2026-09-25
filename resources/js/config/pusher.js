// resources/js/config/pusher.js
export const pusherConfig = {
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    // authEndpoint: '/broadcasting/auth',
    // withCredentials: true,
    enabledTransports: ['ws', 'wss'],
};