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
use App\Services\ScanEmailBatcher;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

        // Scan email batches must survive from the scout/onboarding component
        // to the evidence event listeners within the same request or job.
        $this->app->singleton(ScanEmailBatcher::class);
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

        // Local dev servers (XAMPP) ship without a CA bundle, which makes
        // every HTTPS scout request fail SSL verification. Point the HTTP
        // client at the bundled cacert.pem and retry transient failures.
        $caBundle = 'C:\\xampp\\php\\extras\\ssl\\cacert.pem';

        Http::globalOptions([
            'connect_timeout' => 8,
            'curl' => is_file($caBundle) ? [CURLOPT_CAINFO => $caBundle] : [],
        ]);

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
