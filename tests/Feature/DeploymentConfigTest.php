<?php

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

afterEach(function () {
    TrustProxies::flushState();
});

test('layouts expose the Pusher key and cluster at runtime', function () {
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'runtime-key',
        'broadcasting.connections.pusher.options.cluster' => 'eu',
    ]);

    $this->get('/login')
        ->assertOk()
        ->assertSee('<meta name="pusher-key" content="runtime-key">', false)
        ->assertSee('<meta name="pusher-cluster" content="eu">', false);
});

test('layouts expose where to reach an external Reverb server at runtime', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'lookout-key',
        'broadcasting.connections.reverb.secret' => 'never-in-a-page',
        'broadcasting.connections.reverb.options.host' => 'soundboard.example.com',
        'broadcasting.connections.reverb.options.port' => 443,
        'broadcasting.connections.reverb.options.scheme' => 'https',
    ]);

    $this->get('/login')
        ->assertOk()
        ->assertSee('<meta name="broadcast-driver" content="reverb">', false)
        ->assertSee('<meta name="reverb-key" content="lookout-key">', false)
        ->assertSee('<meta name="reverb-host" content="soundboard.example.com">', false)
        ->assertSee('<meta name="reverb-port" content="443">', false)
        ->assertSee('<meta name="reverb-scheme" content="https">', false)
        ->assertDontSee('never-in-a-page', false)
        ->assertDontSee('pusher-key', false);
});

test('pages tell the browser there is nothing to listen to when broadcasting is off', function () {
    config(['broadcasting.default' => 'log']);

    $this->get('/login')
        ->assertOk()
        ->assertSee('<meta name="broadcast-driver" content="log">', false)
        ->assertDontSee('reverb-key', false)
        ->assertDontSee('pusher-key', false);
});

test('broadcasts go to the Reverb host set in the environment, not Pusher\'s cloud', function () {
    // The address used to sit outside `options`, where Laravel never reads it, so the
    // broadcaster silently fell back to api-mt1.pusher.com. Read the real config file
    // with the environment a deployment sets, as production does.
    $env = [
        'REVERB_APP_ID' => '42',
        'REVERB_APP_KEY' => 'lookout-key',
        'REVERB_APP_SECRET' => 'lookout-secret',
        'REVERB_HOST' => 'soundboard.example.com',
        'REVERB_PORT' => '443',
        'REVERB_SCHEME' => 'https',
    ];

    foreach ($env as $name => $value) {
        putenv("{$name}={$value}");
        $_ENV[$name] = $_SERVER[$name] = $value;
    }

    try {
        $broadcasting = require config_path('broadcasting.php');
    } finally {
        foreach (array_keys($env) as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }

    config(['broadcasting.connections.reverb' => $broadcasting['connections']['reverb']]);

    $settings = Broadcast::connection('reverb')->getPusher()->getSettings();

    expect($settings['host'])->toBe('soundboard.example.com')
        ->and((int) $settings['port'])->toBe(443)
        ->and($settings['scheme'])->toBe('https');
});

test('trusted proxies make generated URLs follow X-Forwarded-Proto', function () {
    config(['app.trusted_proxies' => '*']);
    (new AppServiceProvider(app()))->boot();

    $this->withHeaders([
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'lookout.example.com',
        'X-Forwarded-Port' => '443',
    ])->get('/login')
        ->assertOk()
        ->assertSee('https://lookout.example.com/', false);
});

test('forwarded headers are ignored when no proxies are trusted', function () {
    config(['app.trusted_proxies' => null]);
    (new AppServiceProvider(app()))->boot();

    $this->withHeaders(['X-Forwarded-Host' => 'evil.example.com'])
        ->get('/login')
        ->assertOk()
        ->assertDontSee('evil.example.com', false);
});

test('horizon runs a worker for every queue jobs are dispatched to', function (string $env) {
    // Defaults only merge into supervisors an environment names, so each queue needs its own there.
    $queues = collect(config("horizon.environments.$env"))->pluck('queue')->flatten()->unique();

    expect($queues->sort()->values()->all())->toContain('default', 'polling', 'processing');
})->with(['production', 'local']);

test('broadcasting is off when no connection is chosen', function () {
    $saved = getenv('BROADCAST_CONNECTION');
    putenv('BROADCAST_CONNECTION');
    unset($_ENV['BROADCAST_CONNECTION'], $_SERVER['BROADCAST_CONNECTION']);

    try {
        $broadcasting = require config_path('broadcasting.php');
    } finally {
        putenv("BROADCAST_CONNECTION={$saved}");
        $_ENV['BROADCAST_CONNECTION'] = $_SERVER['BROADCAST_CONNECTION'] = $saved;
    }

    expect($broadcasting['default'])->toBe('null');
});

test('a broadcaster missing its settings is turned off instead of failing every broadcast', function (string $connection, array $settings, string $missing) {
    Log::spy();
    config(['broadcasting.default' => $connection, "broadcasting.connections.$connection" => $settings]);

    (new AppServiceProvider(app()))->boot();

    expect(config('broadcasting.default'))->toBe('null');
    Log::shouldHaveReceived('warning')->with(Mockery::on(fn ($m) => str_contains($m, $missing)))->once();
})->with([
    'reverb without a host' => ['reverb', [
        'driver' => 'reverb', 'app_id' => '42', 'key' => 'k', 'secret' => 's',
        'options' => ['host' => '', 'port' => 443, 'scheme' => 'https'],
    ], 'options.host'],
    'pusher without credentials' => ['pusher', [
        'driver' => 'pusher', 'app_id' => null, 'key' => null, 'secret' => null, 'options' => ['cluster' => 'mt1'],
    ], 'app_id, key, secret'],
]);

test('a fully configured broadcaster is left on', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.app_id' => '42',
        'broadcasting.connections.reverb.key' => 'k',
        'broadcasting.connections.reverb.secret' => 's',
        'broadcasting.connections.reverb.options.host' => 'soundboard.example.com',
    ]);

    (new AppServiceProvider(app()))->boot();

    expect(config('broadcasting.default'))->toBe('reverb');
});
