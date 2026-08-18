<?php

namespace App\Services\Recruiter;

use App\Models\User;
use App\Services\EngineeringMagnitudeService;
use Illuminate\Support\Str;

/**
 * Generates a compact single-page PDF brief for a candidate, reusing the
 * dependency-free PDF writer pattern from JobMatchService.
 */
class CandidatePdfService
{
    public function forUser(User $candidate, User $recruiter): string
    {
        $magnitude = app(EngineeringMagnitudeService::class)->breakdown($candidate);
        $label = app(EngineeringMagnitudeService::class)->labelFor($magnitude['total']);
        $percentile = app(EngineeringMagnitudeService::class)->percentile($magnitude['total']);

        $skills = $candidate->skills->pluck('name')->take(12)->all();
        $verifiedSkills = $candidate->skills
            ->filter(fn ($skill) => $skill->pivot->verified_at !== null)
            ->pluck('name')
            ->take(8)
            ->all();

        $lines = [];

        // Title block.
        $lines[] = ['size' => 16, 'text' => $candidate->name];
        $lines[] = ['size' => 9, 'text' => 'Candidate brief - prepared for '.$recruiter->name.' on '.now()->format('M j, Y')];
        $lines[] = ['rule' => true];

        $snapshot = [
            'Headline' => $candidate->headline,
            'Location' => $candidate->location,
            'Handle' => $candidate->handle() ? '@'.$candidate->handle() : null,
            'Level' => $candidate->levelTitle(),
            'XP' => $candidate->experience_points !== null ? number_format($candidate->experience_points).' XP' : null,
            'Reputation' => $candidate->reputation_score > 0 ? number_format($candidate->reputation_score).' / 1000' : null,
            'Engineering Magnitude' => $magnitude['total'].' / 1000 ('.$label.', top '.$percentile.'%)',
            'Evidence sources' => $candidate->evidence()->ready()->count(),
            'Verified' => $candidate->isVerified() ? 'Yes' : 'No',
        ];

        foreach ($snapshot as $label => $value) {
            if ($value === null || $value === '' || (is_numeric($value) && (int) $value === 0)) {
                continue;
            }
            $lines[] = ['size' => 9, 'text' => Str::title($label).': '.$value];
        }

        $lines[] = ['rule' => true];

        if ($skills !== []) {
            $lines[] = ['size' => 10.5, 'text' => 'Skills'];
            foreach (array_chunk($skills, 4) as $chunk) {
                $lines[] = ['size' => 9, 'text' => implode('  |  ', $chunk)];
            }
        }

        if ($verifiedSkills !== []) {
            $lines[] = ['rule' => true];
            $lines[] = ['size' => 10.5, 'text' => 'Verified skills'];
            $lines[] = ['size' => 9, 'text' => implode('  |  ', $verifiedSkills)];
        }

        $topAreas = collect($magnitude['factors'])
            ->filter(fn ($f) => ($f['points'] / max(1, $f['max'])) >= 0.7)
            ->keys()
            ->take(5)
            ->all();

        if ($topAreas !== []) {
            $lines[] = ['rule' => true];
            $lines[] = ['size' => 10.5, 'text' => 'Top engineering areas'];
            $lines[] = ['size' => 9, 'text' => implode('  |  ', $topAreas)];
        }

        if ($candidate->bio) {
            $lines[] = ['rule' => true];
            $lines[] = ['size' => 10.5, 'text' => 'Summary'];
            $lines[] = ['size' => 9, 'text' => $candidate->bio];
        }

        return $this->render($lines);
    }

    /**
     * @param  array<int, array{size?: float, text?: string, rule?: bool}>  $lines
     */
    private function render(array $lines): string
    {
        $margin = 48;
        $pageWidth = 595;
        $pageHeight = 842;
        $lineHeight = 14;

        $objects = [
            1 => ['type' => 'dict', 'data' => '/Type /Catalog /Pages 2 0 R'],
            2 => ['type' => 'dict', 'data' => '/Type /Pages /Kids [] /Count 0'],
            3 => ['type' => 'dict', 'data' => '/Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding'],
        ];

        $nextObject = 4;
        $pageRefs = [];

        $newPage = function () use (&$objects, &$nextObject, &$pageRefs): array {
            $contentRef = $nextObject++;
            $pageRef = $nextObject++;

            $objects[$contentRef] = ['type' => 'stream', 'data' => ''];
            $objects[$pageRef] = ['type' => 'dict', 'data' => '/Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentRef.' 0 R'];
            $pageRefs[] = $pageRef;

            return ['ref' => $pageRef, 'contentRef' => $contentRef];
        };

        $current = $newPage();

        $write = function (string $text) use (&$objects, &$current): void {
            $objects[$current['contentRef']]['data'] .= $text;
        };

        $y = $pageHeight - 60;

        foreach ($lines as $line) {
            if (! empty($line['rule'])) {
                $write('0.85 0.88 0.93 RG '.$margin.' '.$y.' m '.($pageWidth - $margin).' '.$y." l S\n");
                $y -= 8;

                continue;
            }

            $text = (string) ($line['text'] ?? '');
            $size = (float) ($line['size'] ?? 9);

            // New page if we run out of room.
            if ($y < 70) {
                $current = $newPage();
                $y = $pageHeight - 60;
            }

            // Approximate proportional width: Helvetica averages ~0.5em/char.
            $maxChars = (int) floor(($pageWidth - ($margin * 2)) / ($size * 0.5));

            foreach ($this->wrap($text, max(20, $maxChars)) as $piece) {
                $write('BT /F1 '.number_format($size, 1, '.', '').' Tf '.$margin.' '.$y.' Td ('.$this->escapePdf($piece).") Tj ET\n");
                $y -= $lineHeight;

                if ($y < 70) {
                    $current = $newPage();
                    $y = $pageHeight - 60;
                }
            }
        }

        $objects[2]['data'] = '/Type /Pages /Kids ['.implode(' ', array_map(fn (int $ref) => $ref.' 0 R', $pageRefs)).'] /Count '.count($pageRefs);

        return $this->assemblePdf($objects);
    }

    /**
     * @return array<int, string>
     */
    private function wrap(string $text, int $maxChars): array
    {
        if ($text === '') {
            return [''];
        }

        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            if ($current !== '' && (Str::length($current) + 1 + Str::length($word)) > $maxChars) {
                $lines[] = $current;
                $current = '';
            }

            $current = $current === '' ? $word : $current.' '.$word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function escapePdf(string $value): string
    {
        $value = Str::limit($value, 200);
        $value = preg_replace('/[^\x20-\x7E]/u', '', $value) ?? '';
        $value = str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $value);

        return $value;
    }

    /**
     * @param  array<int, array<string, string>>  $objects
     */
    private function assemblePdf(array $objects): string
    {
        $out = "%PDF-1.4\n";
        $offsets = [];

        ksort($objects);

        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($out);
            $out .= $num." 0 obj\n";
            if (($obj['type'] ?? 'dict') === 'dict') {
                $out .= '<< '.$obj['data']." >>\n";
            } else {
                $out .= "stream\n".$obj['data']."\nendstream\n";
            }
            $out .= "endobj\n";
        }

        $xrefStart = strlen($out);
        $out .= "xref\n0 ".(count($objects) + 1)."\n";
        $out .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $out .= sprintf("%010d 00000 n \n", $offset);
        }

        $out .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefStart."\n%%EOF";

        return $out;
    }
}
