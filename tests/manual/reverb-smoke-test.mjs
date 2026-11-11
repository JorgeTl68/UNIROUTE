// Smoke test de extremo a extremo para Reverb + Echo (S0-10).
// Uso: 1) php artisan reverb:start  2) node tests/manual/reverb-smoke-test.mjs
//      3) php artisan uniroute:broadcast-test
import Pusher from 'pusher-js';
import { readFileSync } from 'node:fs';

const env = readFileSync('.env', 'utf8');
const key = env.match(/^REVERB_APP_KEY="?([^"\r\n]+)"?$/m)?.[1];
const host = env.match(/^REVERB_HOST="?([^"\r\n]+)"?$/m)?.[1] ?? 'localhost';
const port = Number(env.match(/^REVERB_PORT=(\d+)$/m)?.[1] ?? 8080);

if (!key) {
    console.error('No se encontró REVERB_APP_KEY en el archivo .env.');
    process.exit(1);
}

console.log(`Conectando a ws://${host}:${port} (canal: test.ping)...`);

const client = new Pusher(key, {
    wsHost: host,
    wsPort: port,
    forceTLS: false,
    enabledTransports: ['ws'],
    cluster: 'mt1',
    disableStats: true,
});

const timeout = setTimeout(() => {
    console.error('Fallo: no se recibió el evento en 20 segundos.');
    client.disconnect();
    process.exit(1);
}, 20_000);

client.connection.bind('connected', () => {
    console.log('Conectado a Reverb. Esperando evento...');
});

client.connection.bind('error', (error) => {
    console.error('Error de conexión:', error?.message ?? error);
});

const channel = client.subscribe('test.ping');
channel.bind('App\\Events\\TrackingTestPing', (data) => {
    clearTimeout(timeout);
    console.log('OK: evento recibido en tiempo real ->', JSON.stringify(data));
    client.disconnect();
    process.exit(0);
});
