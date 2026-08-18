<?php

namespace App\Services\Recruiter;

use App\Enums\EvidenceStatus;
use App\Enums\UserRole;
use App\Mail\CandidateShortlistMail;
use App\Models\EvidenceAnalysis;
use App\Models\Job;
use App\Models\Skill;
use App\Models\User;
use App\Services\EvidenceScoutService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Matches developers to a job opportunity described either as raw text or as
 * a URL to a job posting. Keywords (skills and evidence technologies) are
 * extracted from the description, then developers are ranked — verified
 * engineers first, then by reputation / evidence signal.
 */
class JobMatchService
{
    public function __construct(private EvidenceScoutService $scout) {}

    /**
     * Extract the job description text from the given input. When only a URL
     * is provided the page is fetched and its body text is used.
     *
     * @return array{text: string, source: string}
     */
    public function resolveText(string $description = '', ?string $url = null): array
    {
        $text = trim($description);

        if ($text === '' && $url) {
            $normalized = Str::startsWith($url, ['http://', 'https://']) ? $url : 'https://'.$url;

            try {
                $material = $this->scout->fetch($normalized);
                $text = trim((string) ($material['title'] ?? '')."\n\n".(string) ($material['description'] ?? '')."\n\n".(string) ($material['content'] ?? ''));
            } catch (\Throwable) {
                $text = '';
            }
        }

        return [
            'text' => Str::limit($text, 20000),
            'source' => $text !== '' && trim($description) === '' && $url ? 'url' : 'text',
        ];
    }

    /**
     * Extract candidate keywords from a job description: known skill names
     * plus technologies that appear inside analyzed evidence.
     *
     * @return array{skills: array<int, string>, technologies: array<int, string>}
     */
    public function extractKeywords(string $text): array
    {
        $normalized = Str::lower(html_entity_decode(strip_tags($text)));

        $skills = Skill::query()
            ->get(['name', 'slug'])
            ->filter(fn (Skill $skill) => $this->containsWord($normalized, Str::lower($skill->name)))
            ->pluck('slug')
            ->values()
            ->all();

        $technologies = EvidenceAnalysis::query()
            ->whereNotNull('technologies')
            ->limit(3000)
            ->get('technologies')
            ->flatMap(fn ($analysis) => (array) $analysis->technologies)
            ->unique()
            ->filter(fn (string $tech) => $this->containsWord($normalized, Str::lower($tech)))
            ->values()
            ->take(30)
            ->all();

        return [
            'skills' => array_slice($skills, 0, 30),
            'technologies' => $technologies,
        ];
    }

    /**
     * Find developers matching the extracted keywords. Verified engineers are
     * always ranked first, then by reputation and evidence signal.
     *
     * @param  array{skills: array<int, string>, technologies: array<int, string>}  $keywords
     * @return Collection<int, User>
     */
    public function match(array $keywords, int $limit = 60): Collection
    {
        $hasSkillKeywords = $keywords['skills'] !== [];
        $hasTechKeywords = $keywords['technologies'] !== [];

        if (! $hasSkillKeywords && ! $hasTechKeywords) {
            return collect();
        }

        $query = User::query()
            ->visibleToPublic()
            ->where('public_passport', true)
            ->with(['skills'])
            ->withCount(['evidence as evidence_count' => fn ($q) => $q->ready()])
            ->where(function ($q) use ($keywords, $hasSkillKeywords, $hasTechKeywords) {
                if ($hasSkillKeywords) {
                    $q->orWhereHas('skills', fn ($s) => $s->whereIn('skills.slug', $keywords['skills']));
                }

                if ($hasTechKeywords) {
                    $q->orWhereHas('evidence', function ($e) use ($keywords) {
                        $e->where('status', EvidenceStatus::Ready)->whereHas('analysis', function ($a) use ($keywords) {
                            foreach (array_slice($keywords['technologies'], 0, 15) as $tech) {
                                $a->orWhere('technologies', 'like', '%'.$tech.'%');
                            }
                        });
                    });
                }
            });

        return $query
            ->orderByDesc('is_verified')
            ->orderByDesc('reputation_score')
            ->orderByDesc('experience_points')
            ->limit($limit)
            ->get();
    }

    /**
     * Developers worth emailing about a published job: same keyword extraction
     * as the recruiter discovery flow, but scoped to developer accounts (no
     * public-passport requirement) and ranked verified-first.
     *
     * @return Collection<int, User>
     */
    public function matchingDevelopersFor(Job $job, int $limit = 200): Collection
    {
        $requirements = is_array($job->requirements)
            ? implode("\n", $job->requirements)
            : (string) $job->requirements;

        $text = implode("\n\n", array_filter([
            $job->title,
            $job->description,
            $requirements,
        ]));

        $keywords = $this->extractKeywords($text);

        if ($keywords['skills'] === [] && $keywords['technologies'] === []) {
            return collect();
        }

        return User::query()
            ->where('role', UserRole::Developer)
            ->whereNull('suspended_at')
            ->where(function ($q) use ($keywords) {
                if ($keywords['skills'] !== []) {
                    $q->orWhereHas('skills', fn ($s) => $s->whereIn('skills.slug', $keywords['skills']));
                }

                if ($keywords['technologies'] !== []) {
                    $q->orWhereHas('evidence', function ($e) use ($keywords) {
                        $e->where('status', EvidenceStatus::Ready)->whereHas('analysis', function ($a) use ($keywords) {
                            foreach (array_slice($keywords['technologies'], 0, 15) as $tech) {
                                $a->orWhere('technologies', 'like', '%'.$tech.'%');
                            }
                        });
                    });
                }
            })
            ->orderByDesc('is_verified')
            ->orderByDesc('reputation_score')
            ->orderByDesc('experience_points')
            ->limit($limit)
            ->get();
    }

    /**
     * Rows for exports: flatten a matched collection into printable rows.
     *
     * @param  Collection<int, User>  $developers
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(Collection $developers): array
    {
        return $developers->values()->map(fn (User $user, int $index) => [
            'rank' => $index + 1,
            'name' => $user->name,
            'username' => $user->handle(),
            'headline' => (string) $user->headline,
            'location' => (string) $user->location,
            'level' => 'Lv '.$user->level(),
            'xp' => (int) $user->experience_points,
            'reputation' => (int) $user->reputation_score,
            'verified' => $user->isVerified() ? 'Yes' : 'No',
            'email' => $user->email,
            'skills' => $user->skills->pluck('name')->take(6)->implode(', '),
            'evidence_count' => (int) $user->evidence_count,
            'passport_url' => route('passport', $user->handle()),
            'avatar' => $user->avatarUrl(),
        ])->all();
    }

    /**
     * Build a UTF-8 CSV (Excel-compatible) from export rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $columns
     */
    public function toCsv(array $rows, array $columns = []): string
    {
        $rows = array_values($rows);
        // The avatar is for the email layout, not for spreadsheets — keep it
        // out of the default CSV columns.
        $defaultColumns = ['name', 'username', 'headline', 'location', 'level', 'xp', 'reputation', 'verified', 'email', 'skills', 'evidence_count', 'passport_url'];
        $first = $rows[0] ?? array_fill_keys($defaultColumns, '');
        $columns = $columns ?: array_values(array_intersect(array_keys($first), $defaultColumns));

        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM so Excel renders accents correctly.
        $csv .= implode(',', array_map(fn ($c) => $this->csvField(Str::title(str_replace('_', ' ', $c))), $columns))."\r\n";

        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($c) => $this->csvField((string) ($row[$c] ?? '')), $columns))."\r\n";
        }

        return $csv;
    }

    /**
     * Build a minimal, dependency-free PDF from export rows. Layout is a
     * clean table suitable for sharing with hiring managers.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function toPdf(array $rows, string $title = 'Candidate Shortlist'): string
    {
        $rows = array_values($rows);

        $columns = ['rank' => 'Rank', 'name' => 'Name', 'headline' => 'Headline', 'location' => 'Location', 'level' => 'Level', 'reputation' => 'Reputation', 'verified' => 'Verified', 'email' => 'Email'];

        $widths = ['rank' => 40, 'name' => 130, 'headline' => 210, 'location' => 95, 'level' => 55, 'reputation' => 75, 'verified' => 55, 'email' => 140];
        $margin = 48;
        $pageWidth = 595;
        $pageHeight = 842;
        $rowHeight = 34;
        $tableTop = 132;

        // Fixed object layout: 1 = catalog, 2 = pages, 3 = Helvetica font,
        // then alternating page / content-stream objects.
        /** @var array<int, array{type: string, data: string}> $objects */
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

        // Title block.
        $write('BT /F1 16 Tf '.$margin.' '.(842 - 64).' Td ('.$this->escapePdf($title).") Tj ET\n");
        $write('BT /F1 9 Tf '.$margin.' '.(842 - 80).' Td (ProoDev candidate shortlist - generated '.now()->format('M j, Y').") Tj ET\n");
        $write('0.87 0.9 0.95 RG '.$margin.' '.(842 - 96).' m '.($pageWidth - $margin).' '.(842 - 96)." l S\n");

        $y = $tableTop;

        $drawHeader = function () use (&$write, $columns, $widths, $margin, $y, $pageWidth): void {
            $x = $margin;

            foreach ($columns as $key => $label) {
                $write('0.2 0.32 0.92 RG '.$x.' '.$y.' m '.($x + $widths[$key]).' '.$y." l S\n");
                $write('BT /F1 8.5 Tf '.($x + 3).' '.($y + 9).' Td ('.$this->escapePdf(Str::upper($label)).") Tj ET\n");
                $x += $widths[$key];
            }

            $write('0.2 0.32 0.92 RG '.$margin.' '.$y.' m '.($pageWidth - $margin).' '.$y." l S\n");
        };

        $drawHeader();
        $y -= $rowHeight;

        foreach ($rows as $row) {
            if ($y < 70) {
                $write('0.87 0.9 0.95 RG '.$margin.' '.($y + $rowHeight).' m '.($pageWidth - $margin).' '.($y + $rowHeight)." l S\n");
                $current = $newPage();
                $y = $tableTop;
                $drawHeader();
                $y -= $rowHeight;
            }

            $x = $margin;

            foreach ($columns as $key => $label) {
                $value = (string) ($row[$key] ?? '');
                if (Str::length($value) > 60) {
                    $value = Str::limit($value, 57);
                }
                $write('BT /F1 '.($key === 'name' ? 9 : 8).' Tf '.($x + 3).' '.($y + 9).' Td ('.$this->escapePdf($value).") Tj ET\n");
                $x += $widths[$key];
            }

            $y -= $rowHeight;
        }

        $write('0.87 0.9 0.95 RG '.$margin.' '.($y + $rowHeight).' m '.($pageWidth - $margin).' '.($y + $rowHeight)." l S\n");

        $objects[2]['data'] = '/Type /Pages /Kids ['.implode(' ', array_map(fn (int $ref) => $ref.' 0 R', $pageRefs)).'] /Count '.count($pageRefs);

        return $this->assemblePdf($objects);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function emailShortlist(User $recruiter, string $to, array $rows, string $title = 'Candidate shortlist'): void
    {
        Mail::to($to)->send(
            new CandidateShortlistMail($recruiter, $rows, $title),
        );
    }

    private function escapePdf(string $value): string
    {
        $value = Str::limit($value, 200);
        $value = preg_replace('/[^\x20-\x7E]/u', '', $value) ?? '';
        $value = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);

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

    private function containsWord(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return false;
        }

        return preg_match('/\b'.preg_quote($needle, '/').'/', $haystack) === 1;
    }

    private function csvField(string $value): string
    {
        $value = trim(strip_tags($value));

        if (preg_match('/[",\r\n]/', $value)) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
