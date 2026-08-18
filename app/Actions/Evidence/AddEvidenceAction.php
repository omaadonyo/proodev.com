<?php

namespace App\Actions\Evidence;

use App\Enums\EvidenceStatus;
use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Events\EvidenceAdded;
use App\Jobs\AnalyzeEvidenceJob;
use App\Models\Evidence;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Services\EvidenceScoutService;
use App\Services\PlagiarismDetectedException;
use App\Services\PlagiarismGuardService;
use App\Services\SubmissionLimitService;
use Illuminate\Support\Str;

class AddEvidenceAction
{
    public function handle(User $user, string $url): Evidence
    {
        $url = Str::startsWith($url, ['http://', 'https://']) ? $url : 'https://'.$url;

        $classified = app(EvidenceScoutService::class)->classify($url);

        $existing = Evidence::where('user_id', $user->id)->where('url', $url)->first();

        if ($existing) {
            return $existing;
        }

        // Reject copied repositories before they ever become proof. The guard
        // notifies both parties and escalates repeat offenders to a ban.
        $strike = app(PlagiarismGuardService::class)->check($user, $url);

        if ($strike) {
            throw new PlagiarismDetectedException($strike);
        }

        app(SubmissionLimitService::class)->recordSubmission($user);

        $evidence = Evidence::create([
            'user_id' => $user->id,
            'type' => $classified['type'],
            'title' => $this->titleFrom($url),
            'url' => $url,
            'source' => $classified['source'],
            'status' => EvidenceStatus::Pending,
        ]);

        TimelineEvent::create([
            'user_id' => $user->id,
            'type' => TimelineEventType::EvidenceAdded,
            'title' => $evidence->title,
            'description' => 'Added '.$evidence->type->label().' evidence to the library.',
            'data' => ['evidence_id' => $evidence->id],
            'target_type' => Evidence::class,
            'target_id' => $evidence->id,
            'visibility' => Visibility::Public,
            'occurred_at' => now(),
        ]);

        app(\App\Services\RepoClaimService::class)->record($user, $url);

        AnalyzeEvidenceJob::dispatch($evidence);

        EvidenceAdded::dispatch($evidence);

        return $evidence;
    }

    private function titleFrom(string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $segments = array_values(array_filter(explode('/', $path)));

        if ($segments !== []) {
            return Str::limit(str_replace('-', ' ', $segments[array_key_last($segments)]), 80);
        }

        return Str::limit(Str::replaceFirst(['https://', 'http://', 'www.'], '', $url), 80);
    }
}
