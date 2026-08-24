<?php

namespace App\Services;

use App\Enums\FeedbackCategory;
use App\Enums\HiringStage;
use App\Events\ApplicationStageChanged;
use App\Mail\ApplicationStageUpdateMail;
use App\Models\Application;
use App\Models\ApplicationEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Hiring Transparency core: records auditable application events, keeps the
 * legacy application status in sync, notifies the candidate according to the
 * employer's transparency settings and broadcasts real-time updates.
 */
class HiringTransparencyService
{
    /**
     * Record a stage change on an application.
     *
     * @param  array<string, mixed>  $metadata  Internal context — never exposed to candidates.
     */
    public function recordStage(
        Application $application,
        HiringStage $stage,
        ?User $actor = null,
        ?FeedbackCategory $feedbackCategory = null,
        ?string $feedbackNote = null,
        bool $candidateVisible = true,
        array $metadata = [],
    ): ApplicationEvent {
        return DB::transaction(function () use ($application, $stage, $actor, $feedbackCategory, $feedbackNote, $candidateVisible, $metadata) {
            $application->loadMissing('job.company');

            /** @var ApplicationEvent $event */
            $event = $application->events()->create([
                'actor_id' => $actor?->id,
                'stage' => $stage->value,
                'candidate_visible' => $candidateVisible,
                'feedback_category' => $feedbackCategory?->value,
                'feedback_note' => $feedbackNote,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);

            // Keep the legacy status in sync for existing screens and filters.
            if (($legacy = $stage->legacyStatus()) !== null) {
                $application->forceFill([
                    'status' => $legacy,
                    'reviewed_at' => $application->reviewed_at ?? now(),
                ])->save();
            } elseif ($application->reviewed_at === null && $stage === HiringStage::ApplicationReceived) {
                $application->forceFill(['reviewed_at' => now()])->save();
            }

            if ($candidateVisible) {
                $this->notifyCandidate($application, $event);

                ApplicationStageChanged::dispatch($application->user_id, $event);
            }

            return $event;
        });
    }

    /**
     * Send the candidate-facing email for a stage change when the company's
     * transparency settings allow it.
     */
    public function notifyCandidate(Application $application, ApplicationEvent $event): void
    {
        $company = $application->job->company;

        if (! $company || ! $company->shouldNotifyStage($event->stage())) {
            return;
        }

        Mail::to($application->user)->send(new ApplicationStageUpdateMail($application, $event));
    }

    /**
     * Applications that have been sitting unchanged beyond the given number
     * of days — used to nudge recruiters and prevent ghosting.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Application>
     */
    public function staleForCompany(\App\Models\Company $company, int $days = 21)
    {
        $cutoff = now()->subDays($days);

        return $company->applications()
            ->with(['job', 'user'])
            ->whereIn('applications.status', [\App\Enums\ApplicationStatus::Pending, \App\Enums\ApplicationStatus::Shortlisted])
            ->get()
            ->filter(fn (Application $application) => $this->lastActivity($application)->lessThan($cutoff))
            ->values();
    }

    public function lastActivity(Application $application): \Carbon\CarbonInterface
    {
        $latest = $application->events()->latest('created_at')->first();

        return $latest?->created_at ?? $application->created_at;
    }
}