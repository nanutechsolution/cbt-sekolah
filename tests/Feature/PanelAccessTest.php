<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to the correct panel login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/guru')->assertRedirect('/guru/login');
});

it('shows the login page for both panels', function () {
    $this->get('/admin/login')->assertOk();
    $this->get('/guru/login')->assertOk();
});

it('shows the developer footer on the panel login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Dikembangkan oleh')
        ->assertSee(config('app.developer'))
        ->assertSee('v'.config('app.version'));
});

it('allows an admin to access the admin panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

it('allows a guru to access the guru panel', function () {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)
        ->get('/guru')
        ->assertOk();
});

it('forbids a guru from accessing the admin panel', function () {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)
        ->get('/admin')
        ->assertForbidden();
});

it('forbids an admin from accessing the guru panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/guru')
        ->assertForbidden();
});

it('forbids an inactive user from accessing their panel', function () {
    $admin = User::factory()->admin()->inactive()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertForbidden();
});
