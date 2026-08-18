<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Application;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationResumeController extends Controller
{
    public function __invoke(Application $application): StreamedResponse|Response
    {
        abort_unless((bool) $application->resume_path, 404);

        $user = auth()->user();

        $canAccess = $user && (
            $user->id === $application->user_id
            || $user->role === UserRole::Recruiter
            || $application->job->company->isMember($user)
        );

        abort_unless($canAccess, 403);

        // Track when an employer or recruiter opens the CV so the applicant
        // can see who has actually looked at their resume.
        if ($canAccess && $user->id !== $application->user_id) {
            $application->timestamps = false;
            $application->forceFill([
                'resume_view_count' => ($application->resume_view_count ?? 0) + 1,
                'last_resume_viewed_at' => now(),
            ])->save();
            $application->timestamps = true;
        }

        $disk = Storage::disk('local');

        abort_unless($disk->exists($application->resume_path), 404);

        $filename = 'resume-'.($application->user->handle() ?: $application->user->id).'.pdf';

        return $disk->download($application->resume_path, $filename);
    }
}
