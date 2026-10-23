<?php

use App\Filament\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('authenticates an admin using username and password', function () {
    $admin = User::factory()->admin()->create([
        'password' => 'rahasia123',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'username' => $admin->username,
            'password' => 'rahasia123',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($admin->id);
});

it('normalizes username case when authenticating', function () {
    $admin = User::factory()->admin()->create([
        'username' => 'AdminSatu',
        'password' => 'rahasia123',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'username' => 'ADMINSATU',
            'password' => 'rahasia123',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(auth()->id())->toBe($admin->id);
});

it('rejects an invalid password', function () {
    $admin = User::factory()->admin()->create([
        'password' => 'rahasia123',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'username' => $admin->username,
            'password' => 'password-salah',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['username']);

    expect(auth()->check())->toBeFalse();
});

it('rejects an inactive admin', function () {
    $admin = User::factory()->admin()->inactive()->create([
        'password' => 'rahasia123',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'username' => $admin->username,
            'password' => 'rahasia123',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['username']);

    expect(auth()->check())->toBeFalse();
});

it('allows a guru to login to the guru panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $guru = User::factory()->guru()->create([
        'password' => 'rahasia123',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'username' => $guru->username,
            'password' => 'rahasia123',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/guru');

    expect(auth()->id())->toBe($guru->id);
});

it('rejects an admin who tries to login to the guru panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $admin = User::factory()->admin()->create([
        'password' => 'rahasia123',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'username' => $admin->username,
            'password' => 'rahasia123',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['username']);

    expect(auth()->check())->toBeFalse();
});
