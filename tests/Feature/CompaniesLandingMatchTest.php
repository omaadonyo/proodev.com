<?php

use App\Services\JobMatchService;
use Illuminate\Support\Facades\Http;

test('the matcher extracts skills from pasted job description text', function () {
    $this->postJson(route('for-companies.match-skills'), [
        'text' => 'We are hiring a Senior Laravel Engineer. You will build features with Vue and Livewire, work with MySQL and Redis, and deploy containers with Docker on AWS.',
    ])->assertOk()
        ->assertJsonPath('skills', fn (array $skills) => collect([
            'laravel', 'vue', 'mysql', 'redis', 'docker', 'aws',
        ])->every(fn (string $needle) => in_array($needle, $skills, true)));
});

test('the matcher fetches a job posting url and extracts skills from its content', function () {
    Http::fake([
        'company-jobs.test/*' => Http::response(
            '<html><head><style>.hide{display:none}</style></head><body>'
            .'<h1>Backend Engineer</h1>'
            .'<p>We use <strong>PHP</strong> with PostgreSQL, queue jobs on Redis, and ship with Docker.</p>'
            .'<script>console.log("php php php")</script>'
            .'</body></html>'
        ),
    ]);

    $this->postJson(route('for-companies.match-skills'), [
        'text' => 'https://company-jobs.test/backend-engineer',
    ])->assertOk()
        ->assertJsonPath('skills', fn (array $skills) => collect([
            'php', 'postgres', 'redis', 'docker',
        ])->every(fn (string $needle) => in_array($needle, $skills, true)));
});

test('the matcher rejects a malformed url', function () {
    $this->postJson(route('for-companies.match-skills'), [
        'text' => 'https://not a valid url',
    ])->assertStatus(422)
        ->assertJsonPath('error', 'That does not look like a valid URL.');
});

test('the matcher reports an unreachable posting url', function () {
    Http::fake(['*' => Http::response('Not found', 404)]);

    $this->postJson(route('for-companies.match-skills'), [
        'text' => 'https://company-jobs.test/missing',
    ])->assertStatus(422)
        ->assertJsonPath('error', 'Could not fetch that URL (HTTP 404).');
});

test('the matcher requires a non-empty posting', function () {
    $this->postJson(route('for-companies.match-skills'), ['text' => '   '])
        ->assertStatus(422);
});

test('the for-companies landing page shows the job-description matcher panel', function () {
    $this->get(route('for-companies'))
        ->assertOk()
        ->assertSee('Paste a job description or URL to auto-apply skills', false)
        ->assertSee('/for-companies/match-skills', false)
        ->assertSee('matchSkills', false);
});

test('the for-companies avatar grid shows an evidence-match percentage badge', function () {
    $this->get(route('for-companies'))
        ->assertOk()
        ->assertSee('matchPct', false)
        ->assertSee("% of the selected skills'", false);
});

test('full-coverage engineers get a best-match ring and badge', function () {
    $this->get(route('for-companies'))
        ->assertOk()
        ->assertSee('#34d399, #14b8a6', false)
        ->assertSee('#10b981, #14b8a6', false)
        ->assertSee('Perfect match — covers all selected skills', false);
});

test('keywordsFromText is shared with the recruiter job matcher', function () {
    $skills = app(JobMatchService::class)->keywordsFromText(
        'We need a Python and TypeScript developer with GraphQL, CI/CD pipelines and Kubernetes experience.'
    );

    expect($skills)->toContain('python')
        ->and($skills)->toContain('typescript')
        ->and($skills)->toContain('graphql')
        ->and($skills)->toContain('devops')
        ->and($skills)->toContain('kubernetes');
});
