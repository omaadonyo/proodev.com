<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JobMatchService;
use App\Support\FeatureFlags;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CompaniesLandingController extends Controller
{
    /**
     * Extract skill keywords from a pasted job description or posting URL.
     */
    public function matchSkills(Request $request, JobMatchService $service): JsonResponse
    {
        $request->validate([
            'text' => ['required', 'string', 'max:20000'],
        ]);

        $text = trim((string) $request->input('text'));

        try {
            if (Str::startsWith($text, ['http://', 'https://'])) {
                if (! filter_var($text, FILTER_VALIDATE_URL)) {
                    return response()->json(['error' => 'That does not look like a valid URL.'], 422);
                }

                $skills = $service->keywordsFromUrl($text);
            } else {
                $skills = $service->keywordsFromText($text);
            }
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Could not read that posting.'], 422);
        }

        return response()->json(['skills' => $skills]);
    }

    public function exportPdf(Request $request): Response
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'loc' => ['nullable', 'string', 'max:200'],
            'skills' => ['nullable', 'string', 'max:500'],
            'verified' => ['nullable', 'in:1'],
            'online' => ['nullable', 'in:1'],
        ]);

        $q = Str::lower(trim((string) $request->query('q', '')));
        $loc = Str::lower(trim((string) $request->query('loc', '')));
        $verifiedOnly = $request->query('verified') === '1';
        $onlineOnly = $request->query('online') === '1';
        $activeSkills = array_filter(array_map('trim', explode(',', (string) $request->query('skills', ''))));
        $activeSkillsLower = array_map(fn ($s) => Str::lower($s), $activeSkills);
        $presenceEnabled = FeatureFlags::publicPresenceEnabled();

        $pool = User::visibleToPublic()
            ->with('skills')
            ->orderByDesc('reputation_score')
            ->limit(60)
            ->get();

        $engineers = $pool->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'headline' => $user->headline,
            'location' => $user->location,
            'verified' => $user->isVerified(),
            'online' => $presenceEnabled && $user->isOnline(),
            'skills' => $user->skills->pluck('name')->take(4)->all(),
            'reputation' => (int) $user->reputation_score,
        ])->filter(function (array $engineer) use ($q, $loc, $verifiedOnly, $onlineOnly, $activeSkillsLower) {
            if ($verifiedOnly && ! $engineer['verified']) return false;
            if ($onlineOnly && ! $engineer['online']) return false;
            if ($loc && ! Str::contains(Str::lower($engineer['location'] ?? ''), $loc)) return false;
            if ($activeSkillsLower) {
                $es = array_map(fn ($s) => Str::lower($s), $engineer['skills'] ?? []);
                $matched = false;
                foreach ($activeSkillsLower as $skill) {
                    if (in_array($skill, $es, true)) { $matched = true; break; }
                }
                if (! $matched) return false;
            }
            if ($q === '') return true;
            $hay = Str::lower(implode(' ', [
                $engineer['name'] ?? '',
                $engineer['headline'] ?? '',
                implode(' ', $engineer['skills'] ?? []),
                $engineer['location'] ?? '',
            ]));
            return Str::contains($hay, $q);
        })->values();

        // For guests, blur everything after first 2 results
        $isGuest = ! auth()->check();
        $visibleCount = $isGuest ? 2 : $engineers->count();

        $pdf = Pdf::loadView('pdf.companies-results', [
            'engineers' => $engineers,
            'visibleCount' => $visibleCount,
            'isGuest' => $isGuest,
            'filters' => [
                'q' => $request->query('q'),
                'skills' => $activeSkills,
                'loc' => $request->query('loc'),
                'verified' => $verifiedOnly,
                'online' => $onlineOnly,
            ],
        ])->setPaper('a4')->setOption('isRemoteEnabled', false)->setOption('defaultFont', 'Helvetica');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="proodev-results-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }
}
