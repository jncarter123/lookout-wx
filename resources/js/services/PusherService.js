import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { pusherConfig } from '../config/pusher';

export class PusherService {
    constructor() {
        this.initializeEcho();
    }

    initializeEcho() {
        window.Pusher = Pusher;

        // Use the shared configuration
        window.Echo = new Echo(pusherConfig);
        console.log('Echo is initialized and ready!');
    }
}