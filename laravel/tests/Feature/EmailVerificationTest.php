<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->unverified()->create();
    $this->verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $this->user->id, 'hash' => sha1($this->user->email)]
    );
});

test('verification link can be generated', function () {
    expect($this->verificationUrl)->toBeString();
});

test('verification page shows correct content for mobile devices', function () {
    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15'
    ])->get($this->verificationUrl);
    
    $response->assertStatus(200)
        ->assertSee(__('verification.verified'))
        ->assertSee(__('verification.open_in_app'))
        ->assertSee(__('verification.download_prompt'));
});

test('verification page shows desktop instructions for desktop devices', function () {
    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ])->get($this->verificationUrl);
    
    $response->assertStatus(200)
        ->assertSee(__('verification.desktop_title'))
        ->assertSee(__('verification.desktop_instructions'));
});

test('verification page respects locale setting', function () {
    // Test German translation
    Config::set('app.locale', 'de');
    $response = $this->get($this->verificationUrl);
    $response->assertStatus(200)
        ->assertSee('E-Mail-Verifizierung')
        ->assertSee('In App öffnen');
    
    // Test English translation
    Config::set('app.locale', 'en');
    $response = $this->get($this->verificationUrl);
    $response->assertStatus(200)
        ->assertSee('Email Verification')
        ->assertSee('Open in App');
});

test('invalid verification links are rejected', function () {
    $invalidUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $this->user->id, 'hash' => 'invalid-hash']
    );
    
    $response = $this->get($invalidUrl);
    $response->assertStatus(500)
        ->assertSee('Invalid verification link');
});

test('already verified users can still access verification page', function () {
    $verifiedUser = User::factory()->create(['email_verified_at' => now()]);
    
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $verifiedUser->id, 'hash' => sha1($verifiedUser->email)]
    );
    
    $response = $this->get($verificationUrl);
    $response->assertStatus(200)
        ->assertSee(__('verification.verified'));
});
