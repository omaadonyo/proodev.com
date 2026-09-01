<?php

namespace App\Services\Recruiter;

use App\Models\User;

/**
 * Evidence-based interview question generator. Questions are derived from
 * the candidate's actual analyzed evidence (areas, technologies, claimed
 * skills), so interviews probe what the candidate says they know.
 */
class InterviewGeneratorService
{
    public function __construct(private CandidateIntelligenceService $intelligence) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(User $candidate, array $options = [], ?User $recruiter = null): array
    {
        $report = $this->intelligence->report($candidate, [
            'recruiter' => $recruiter,
            'persist' => $recruiter !== null,
        ]);

        $role = $options['role'] ?? $report['suggested_roles'][0] ?? null;
        $count = min(10, max(3, (int) ($options['count'] ?? 8)));

        $questions = [];

        foreach ($this->evidenceQuestions($report, $count) as $q) {
            $questions[] = $q;
            if (count($questions) >= $count) {
                break;
            }
        }

        $sections = [
            'behavioural' => $this->behaviouralQuestions($report),
            'technical' => $questions,
            'probing' => $this->probingQuestions($report, $role),
        ];

        return [
            'candidate' => $report['developer'],
            'role' => $role,
            'sections' => $sections,
            'probe_note' => 'These questions are grounded in the candidate evidence library. Ask for specifics, commit SHAs, and concrete outcomes.',
            'generated_by' => 'evidence-engine',
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<string, string>>
     */
    private function evidenceQuestions(array $report, int $count): array
    {
        $questions = [];
        $technologies = $report['evidence']['technologies'] ?? [];

        foreach (array_slice($technologies, 0, 4) as $tech) {
            $questions[] = [
                'category' => 'technology',
                'question' => "You have analyzed evidence around {$tech}. Walk me through the hardest {$tech} problem you actually solved - what was the trade-off, and what did you decide?",
            ];
        }

        foreach (array_slice($report['evidence']['engineering_areas'] ?? [], 0, 3) as $area) {
            $questions[] = [
                'category' => 'area',
                'question' => "Your evidence shows experience in {$area}. Describe the architecture of a system you built in this area, including the constraints you were optimizing for.",
            ];
        }

        $topEvidence = $report['evidence']['top'] ?? [];
        foreach (array_slice($topEvidence, 0, 2) as $evidence) {
            $questions[] = [
                'category' => 'evidence',
                'question' => "Your profile references \"{$evidence['title']}\". What problem were you solving, and how would you have approached it differently today?",
            ];
        }

        $domains = array_slice($report['evidence']['knowledge_domains'] ?? [], 0, 2);
        foreach ($domains as $domain) {
            $questions[] = [
                'category' => 'domain',
                'question' => "Your knowledge extends into {$domain}. Give me a concrete example of how that knowledge changed an engineering decision you made.",
            ];
        }

        foreach ($questions as $q) {
            $q['question'] = $q['question'] ?? '';
        }

        return $questions;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<string, string>>
     */
    private function behaviouralQuestions(array $report): array
    {
        $weaknesses = $report['weaknesses'] ?? [];

        $questions = [
            ['category' => 'collaboration', 'question' => 'Describe a time a project you worked on missed its target. What did you own, and what did you learn?'],
            ['category' => 'impact', 'question' => 'Tell me about a decision you made that improved a system you worked on. How did you measure the impact?'],
            ['category' => 'growth', 'question' => 'What is the most significant engineering skill you have intentionally improved in the last year, and how did you go about it?'],
        ];

        if ($weaknesses !== []) {
            $questions[] = [
                'category' => 'weakness_probe',
                'question' => 'Our analysis flags a thin area: '.$weaknesses[0].' How do you plan to address that gap?',
            ];
        }

        return $questions;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<string, string>>
     */
    private function probingQuestions(array $report, ?string $role): array
    {
        $seniority = $report['seniority'] ?? 'Mid-level';
        $tech = $report['evidence']['technologies'][0] ?? 'your stack';

        return [
            ['category' => 'verification', 'question' => 'We verified your evidence library. Which piece of your work would you want us to inspect line-by-line first, and why?'],
            ['category' => 'fit', 'question' => $role
                ? "This role is {$role}. Which part of your evidence best demonstrates readiness for it, and where are the gaps?"
                : 'Which part of your evidence best demonstrates your readiness for this team, and where are the gaps?'],
            ['category' => 'depth', 'question' => "Someone says \"{$seniority} {$tech} engineer\". What do you insist they know before you would accept that label?"],
        ];
    }
}
