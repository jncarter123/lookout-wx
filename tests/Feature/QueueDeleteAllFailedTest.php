<?php

use App\Livewire\Queue\Index;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(AdminUserSeeder::class);
});

function failedJob(): void
{
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'redis',
        'queue' => 'processing',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\ProcessNwsAlertsBatch']),
        'exception' => "RuntimeException: NWS timed out\n#0 {main}",
        'failed_at' => now(),
    ]);
}

function queueMonitor(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('queue.monitor');

    return $user;
}

test('delete all empties the failed jobs list', function () {
    failedJob();
    failedJob();
    failedJob();

    Livewire::actingAs(queueMonitor())
        ->test(Index::class)
        ->assertSee('Delete All')
        ->call('deleteAll')
        ->assertSee('No failed jobs. All clear.');

    expect(DB::table('failed_jobs')->count())->toBe(0);
});

test('someone whose queue permission was taken away cannot delete them', function () {
    failedJob();
    $user = queueMonitor();

    $page = Livewire::actingAs($user)->test(Index::class);
    $user->revokePermissionTo('queue.monitor');

    $page->call('deleteAll')->assertForbidden();

    expect(DB::table('failed_jobs')->count())->toBe(1);
});

test('delete all also clears Horizon, and still clears the table when Horizon cannot be reached', function () {
    failedJob();

    Artisan::shouldReceive('call')->once()->with('queue:flush')->andReturnUsing(function () {
        DB::table('failed_jobs')->delete();

        return 0;
    });
    Artisan::shouldReceive('call')->once()->with('horizon:forget', ['--all' => true])
        ->andThrow(new RuntimeException('Connection refused [tcp://127.0.0.1:6379]'));

    Livewire::actingAs(queueMonitor())
        ->test(Index::class)
        ->call('deleteAll')
        ->assertOk();

    expect(DB::table('failed_jobs')->count())->toBe(0);
});
