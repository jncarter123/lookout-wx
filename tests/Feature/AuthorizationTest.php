<?php

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(AdminUserSeeder::class);
});

test('seeder creates every configured permission and grants them all to Admin', function () {
    $configured = config('auth_permissions.permissions');

    expect(Permission::pluck('name')->sort()->values()->all())
        ->toBe(collect($configured)->sort()->values()->all())
        ->and(Role::findByName('Admin')->permissions()->count())->toBe(count($configured))
        ->and(User::where('email', 'admin@example.com')->first()->hasRole('Admin'))->toBeTrue();
});

test('guests are redirected to login', function () {
    $this->get('/admin/alerts')->assertRedirect('/login');
});

test('admin can open every admin page', function () {
    $this->actingAs(User::where('email', 'admin@example.com')->first());

    foreach (['/admin/home', '/admin/alerts', '/admin/alerts/active', '/admin/users', '/admin/roles', '/admin/tokens', '/admin/queue'] as $url) {
        $this->get($url)->assertOk();
    }
});

test('permission middleware enforces individual permissions', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('alerts.read');

    $this->actingAs($viewer);

    $this->get('/admin/alerts')->assertOk();
    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/roles')->assertForbidden();
    $this->get('/admin/queue')->assertForbidden();
});

test('tokens page accepts either token permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('tokens.manage-own');

    $this->actingAs($user)->get('/admin/tokens')->assertOk();
});
