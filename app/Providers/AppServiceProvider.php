<?php

namespace App\Providers;

use App\Events\EvidenceAdded;
use App\Events\EvidenceAnalyzed;
use App\Listeners\AddAdminBccToOutgoingEmails;
use App\Listeners\NotifyEvidenceActivity;
use App\Listeners\ScheduleChatReplyReminder;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiProvider;
use App\Services\Ai\OpenAiProvider;
use App\Services\Ai\RuleBasedFallbackProvider;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Feature;
use Wirechat\Wirechat\Events\MessageCreated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiProvider::class, function () {
            $settings = $this->app->make(AiSettings::class);
            $active = $settings->active();
            $config = $settings->activeConfig();

            if (! $active->isFallback() && ! empty($config['api_key']) && ! empty($config['base_url'])) {
                return new OpenAiProvider($settings);
            }

            return new RuleBasedFallbackProvider;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->defineFeatures();

        Event::listen(MessageCreated::class, ScheduleChatReplyReminder::class);
        Event::listen(EvidenceAdded::class, NotifyEvidenceActivity::class);
        Event::listen(EvidenceAnalyzed::class, NotifyEvidenceActivity::class);
        Event::listen(MessageSending::class, AddAdminBccToOutgoingEmails::class);
    }

    /**
     * Register legacy and transitional feature flags with Laravel Pennant.
     */
    protected function defineFeatures(): void
    {
        foreach ([
            'battles' => config('features.battles', false),
            'linkedin-onboarding' => config('features.linkedin_onboarding', false),
            'profile-completion' => config('features.profile_completion', true),
            'evidence-pipeline' => config('features.evidence_pipeline', true),
            'companies' => config('features.companies', true),
            'credits' => config('features.credits', true),
            'verification' => config('features.verification', true),
            'auto-scan' => config('features.auto_scan', true),
            'public-presence' => config('features.public_presence', false),
        ] as $name => $enabled) {
            Feature::define($name, fn (): bool => (bool) $enabled);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
