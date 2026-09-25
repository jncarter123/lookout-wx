<?php

use App\Livewire\ActiveAlertsDashboard;
use App\Livewire\AlertsDashboard;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.home'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/home', fn () => view('admin.home'))->name('home');
    Route::get('/alerts', AlertsDashboard::class)->name('alerts')->middleware('permission:alerts.read');
    Route::get('/alerts/active', ActiveAlertsDashboard::class)->name('alerts.active')->middleware('permission:alerts.read');
    Route::get('/users', fn () => view('admin.users'))->name('users')->middleware('permission:users.read');
    Route::get('/roles', fn () => view('admin.roles'))->name('roles')->middleware('permission:roles.read');
    Route::get('/tokens', fn () => view('admin.tokens'))->name('tokens')->middleware('permission:tokens.manage|tokens.manage-own');
    Route::get('/queue', fn () => view('admin.queue'))->name('queue')->middleware('permission:queue.monitor');
});
