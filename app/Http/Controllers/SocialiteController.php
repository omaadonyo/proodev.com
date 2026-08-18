<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Models\User;
use App\Services\TimelineService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as BaseRedirectResponse;

class SocialiteController extends Controller
{
    public const PROVIDERS = ['google', 'github', 'gitlab', 'bitbucket'];

    /**
     * Redirect the user to the provider's authorization page.
     */
    public function redirect(string $provider): BaseRedirectResponse
    {
        $this->guardProvider($provider);

        return $this->driver($provider)->redirect();
    }

    /**
     * Handle the provider callback and authenticate the user.
     */
    public function callback(string $provider): RedirectResponse
    {
        $this->guardProvider($provider);

        try {
            $socialUser = $this->driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('status', "We couldn't sign you in with {$provider}. Please try again.");
        }

        $user = $this->resolveUser($provider, $socialUser);

        auth()->login($user, true);

        if (! $user->hasCompletedOnboarding()) {
            return redirect()->route('onboarding');
        }

        return redirect()->intended(route('home'));
    }

    /**
     * Resolve an existing account by email/handle, or create a new one.
     */
    private function resolveUser(string $provider, object $socialUser): User
    {
        $email = $socialUser->getEmail();
        $nickname = $socialUser->getNickname();

        $user = $email ? User::where('email', $email)->first() : null;

        if (! $user && $nickname) {
            $user = User::where('username', $nickname)->first();
        }

        if ($user) {
            return $user;
        }

        return $this->createSocialUser($provider, $socialUser);
    }

    private function createSocialUser(string $provider, object $socialUser): User
    {
        $name = $socialUser->getName() ?: $socialUser->getNickname() ?: ucfirst($provider).' user';
        $nickname = $socialUser->getNickname();
        $email = $socialUser->getEmail();

        $username = (new CreateNewUser)->generateUsername($nickname ?: $name);

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email ?: $username.'@'.$provider.'.auth',
            'password' => Str::random(64),
            'github_url' => $provider === 'github' && $nickname ? "https://github.com/{$nickname}" : null,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        event(new Registered($user));

        app(TimelineService::class)->record(
            $user,
            TimelineEventType::Joined,
            'Joined ProoDev',
            "Signed up with {$provider}.",
            [],
            visibility: Visibility::Public,
        );

        return $user;
    }

    private function driver(string $provider): AbstractProvider
    {
        return Socialite::driver($provider);
    }

    private function guardProvider(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
    }
}
