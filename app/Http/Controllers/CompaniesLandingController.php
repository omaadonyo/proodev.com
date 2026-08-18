<?php

namespace App\Http\Controllers;

use App\Services\JobMatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

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
}
