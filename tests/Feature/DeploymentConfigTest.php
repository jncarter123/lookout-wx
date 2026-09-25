<?php

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;

afterEach(function () {
    TrustProxies::flushState();
});

test('layouts expose the Pusher key and cluster at runtime', function () {
    config([
        'broadcasting.connections.pusher.key' => 'runtime-key',
        'broadcasting.connections.pusher.options.cluster' => 'eu',
    ]);

    $this->get('/login')
        ->assertOk()
        ->assertSee('<meta name="pusher-key" content="runtime-key">', false)
        ->assertSee('<meta name="pusher-cluster" content="eu">', false);
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
