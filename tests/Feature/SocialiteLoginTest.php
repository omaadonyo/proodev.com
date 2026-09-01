<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

it('renders social login buttons on the login and register pages', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Or continue with')
        ->assertSee('data-provider="google"', false)
        ->assertSee('data-provider="github"', false)
        ->assertSee('data-provider="gitlab"', false)
        ->assertSee('data-provider="bitbucket"', false);

    $this->get('/register')
        ->assertOk()
        ->assertSee('data-provider="github"', false);
});

it('redirects to the provider authorization page', function () {
    $driver = Mockery::mock(AbstractProvider::class);
    $driver->shouldReceive('redirect')->andReturn(redirect('https://github.com/login/oauth/authorize?scope=user'));
    Socialite::shouldReceive('driver')->with('github')->andReturn($driver);

    $this->get('/auth/github/redirect')->assertRedirect('https://github.com/login/oauth/authorize?scope=user');
});

it('creates a user and logs them in on their first social login', function () {
    $socialUser = new SocialiteUser;
    $socialUser->map(['id' => '456', 'nickname' => 'newdev', 'name' => 'New Dev', 'email' => 'newdev@example.com']);

    $driver = Mockery::mock(AbstractProvider::class);
    $driver->shouldReceive('user')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('github')->andReturn($driver);

    $this->get('/auth/github/callback')->assertRedirect('/onboarding');

    $user = User::where('email', 'newdev@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->username)->toBe('newdev')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

it('logs in an existing user when the social email matches', function () {
    $user = User::factory()->create(['email' => 'jane@example.com', 'username' => 'janedoe']);

    $socialUser = new SocialiteUser;
    $socialUser->map(['id' => '789', 'nickname' => 'janedoe', 'name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $driver = Mockery::mock(AbstractProvider::class);
    $driver->shouldReceive('user')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('github')->andReturn($driver);

    $this->get('/auth/github/callback')->assertRedirect('/home');

    expect(User::count())->toBe(1);
    $this->assertAuthenticatedAs($user);
});

it('links by provider handle when the provider does not share an email', function () {
    $user = User::factory()->create(['email' => 'other@example.com', 'username' => 'devguy']);

    $socialUser = new SocialiteUser;
    $socialUser->map(['id' => '321', 'nickname' => 'devguy', 'name' => 'Dev Guy', 'email' => null]);

    $driver = Mockery::mock(AbstractProvider::class);
    $driver->shouldReceive('user')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('github')->andReturn($driver);

    $this->get('/auth/github/callback')->assertRedirect('/home');

    expect(User::count())->toBe(1);
    $this->assertAuthenticatedAs($user);
});

it('returns 404 for unsupported providers', function () {
    $this->get('/auth/twitter/redirect')->assertNotFound();
    $this->get('/auth/twitter/callback')->assertNotFound();
});

it('bounces back to login when the provider callback fails', function () {
    $driver = Mockery::mock(AbstractProvider::class);
    $driver->shouldReceive('user')->andThrow(new Exception('access_denied'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

    $this->get('/auth/google/callback')
        ->assertRedirect('/login')
        ->assertSessionHas('status', "We couldn't sign you in with google. Please try again.");
});
