import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { echoConfig } from '../config/pusher';

export class PusherService {
    constructor() {
        this.initializeEcho();
    }

    initializeEcho() {
        const config = echoConfig();

        if (config === null) {
            console.log('Live updates are off: the server is not broadcasting to browsers.');
            return;
        }

        // Reverb speaks the Pusher protocol, so pusher-js is the client for both.
        window.Pusher = Pusher;
        window.Echo = new Echo(config);
        console.log(`Echo is initialized (${config.broadcaster}) and ready!`);
    }
}
