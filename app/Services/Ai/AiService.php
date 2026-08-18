<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProvider;

class AiService
{
    public function __construct(private AiProvider $provider) {}

    public function available(): bool
    {
        return ! ($this->provider instanceof RuleBasedFallbackProvider);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function summarize(string $content, array $context = []): string
    {
        return $this->provider->complete(
            'You are an expert software engineering editor. Summarize the engineering work concisely and factually.',
            $content,
            $context,
        );
    }

    /**
     * Write a short, professional developer bio from extracted profile facts.
     *
     * @param  array<string, mixed>  $context
     */
    public function professionalBio(string $facts, array $context = []): string
    {
        return $this->provider->complete(
            'You are a professional profile writer. Write a concise, third-person developer bio of 2–3 sentences based only on the facts provided. Do not invent achievements.',
            $facts,
            $context,
        );
    }

    /**
     * Draft a full project write-up from extracted source content.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function draftProject(string $facts, array $context = []): array
    {
        return $this->provider->structured(
            'You are an expert software engineering editor. From the extracted project material, write a factual, publish-ready project case study. Do not invent features or claims not supported by the source.',
            'Return JSON with these keys: "title" (string), "tagline" (string), "problem" (string, 2-3 sentences), "solution" (string, 2-4 sentences), "architecture" (string), "tech_stack" (array of strings), "engineering_decisions" (array of strings), "lessons_learned" (string), "demo_url" (string or null), "repository_url" (string or null).',
            array_merge(['content' => $facts], $context),
        );
    }

    /**
     * Analyze an evidence source and produce an evidence-backed engineering report.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function analyzeEvidence(string $content, array $context = []): array
    {
        return $this->provider->structured(
            'You are an expert engineering intelligence analyst. Analyze the provided technical material and produce a factual, evidence-backed report. Never invent claims not supported by the source.',
            'Return JSON with these keys: "summary" (string, 2-4 sentences), "technologies" (array of strings), "engineering_areas" (array of strings like "Backend Engineering", "System Design", "Security"), "complexity" (one of: simple, moderate, complex, advanced), "architecture_observations" (string or null), "skills" (array of {name: string, confidence: 0-100}), "knowledge_domains" (array of strings), "highlights" (array of strings), "strengths" (array of strings), "references" (array of {claim: string, reference: string}).',
            array_merge(['content' => $content], $context),
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function expandJournal(string $content, array $context = []): array
    {
        return $this->provider->structured(
            'You transform raw developer notes into a structured engineering log with a summary, highlights, categories, and tags.',
            $content,
            array_merge(['content' => $content], $context),
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function weeklyInsights(array $context = []): array
    {
        return $this->provider->structured(
            'You summarize a developer weekly engineering report into growth insights and recommendations.',
            'Produce a concise narrative (max 2 paragraphs) plus a list of suggested focus areas for next week.',
            $context,
        );
    }

    /**
     * Draft a complete job posting from a short brief.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function draftJobPosting(string $brief, array $context = []): array
    {
        return $this->provider->structured(
            'You are an expert technical recruiter and hiring manager. From the brief below, write a complete, professional job posting. Do not invent technologies or requirements that are not implied by the brief. When company details are provided in the context, weave a short, professional \'About the company\' paragraph into the description so the posting reads like a real employer brand.',
            'Return JSON with these keys: "title" (string, 3-8 words), "description" (string, 2-4 paragraphs describing the company, responsibilities and what success looks like — include the \'About the company\' paragraph when company details are available), "requirements" (array of strings, 4-8 bullet points), "location" (string or null), "is_remote" (boolean), "employment_type" (one of full-time, part-time, contract, internship), "salary_min" (integer or null), "salary_max" (integer or null), "currency" (string, 3 uppercase letters), "deadline" (string date YYYY-MM-DD or null).',
            array_merge(['content' => $brief], $context),
        );
    }

    /**
     * Compare a developer profile against a job posting and judge the fit.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function matchJob(string $profile, string $job, array $context = []): array
    {
        return $this->provider->structured(
            'You are an expert technical recruiter. Compare the candidate developer profile with the job posting and judge how well the candidate fits the role. Be evidence-based and honest — never inflate a weak match.',
            'Return JSON with these keys: "score" (integer 0-100), "summary" (string, 2-3 sentences), "matched_skills" (array of strings), "missing_skills" (array of strings), "recommendation" (one of: strong_match, possible_match, weak_match), "strengths" (array of strings).',
            array_merge(['profile' => $profile, 'job' => $job], $context),
        );
    }
}
