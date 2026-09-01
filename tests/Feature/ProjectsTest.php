<?php

use App\Actions\Projects\SaveProjectAction;
use App\Data\ProjectData;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectScoutService;
use App\Support\Markdown;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('guests are redirected from the projects index', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
});

test('authenticated users can browse published projects', function () {
    $user = User::factory()->create();

    Project::create([
        'user_id' => $user->id,
        'title' => 'A Shipped Thing',
        'problem' => 'Something painful',
        'solution' => 'A working fix',
        'status' => ProjectStatus::Published,
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertSee('A Shipped Thing');
});

test('the create page renders for authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('projects.create'))
        ->assertOk();
});

test('a draft project can be created and published', function () {
    $user = User::factory()->create();

    $project = app(SaveProjectAction::class)->handle($user, ProjectData::fromArray([
        'title' => 'Draft to Ship',
        'tagline' => 'A tagline',
        'problem' => 'The problem',
        'solution' => 'The solution',
        'architecture' => 'The architecture',
        'tech_stack' => ['PHP', 'Laravel'],
        'engineering_decisions' => ['Keep it simple'],
        'lessons_learned' => 'Measure twice',
        'demo_url' => 'https://example.com',
        'repository_url' => 'https://github.com/example/demo',
    ]));

    expect($project->status)->toBe(ProjectStatus::Draft);

    $published = app(SaveProjectAction::class)->publish($project);

    expect($published->isPublished())->toBeTrue()
        ->and($published->published_at)->not->toBeNull();

    $this->assertDatabaseHas('timeline_events', [
        'user_id' => $user->id,
        'type' => 'project-published',
        'target_type' => Project::class,
        'target_id' => $published->id,
    ]);

    expect($user->fresh()->experience_points)->toBeGreaterThan(0);
});

test('only the owner can update a project', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $project = app(SaveProjectAction::class)->handle($owner, ProjectData::fromArray([
        'title' => 'Private Thing',
        'problem' => 'Problem',
        'solution' => 'Solution',
    ]));

    $this->actingAs($other)->get(route('projects.edit', $project))->assertForbidden();
    $this->actingAs($owner)->get(route('projects.edit', $project))->assertOk();
});

test('unpublished projects are hidden from other users', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();

    $project = app(SaveProjectAction::class)->handle($owner, ProjectData::fromArray([
        'title' => 'Hidden Draft',
        'problem' => 'Problem',
        'solution' => 'Solution',
    ]));

    $this->actingAs($viewer)->get(route('projects.show', $project))->assertForbidden();
    $this->actingAs($owner)->get(route('projects.show', $project))->assertOk();
});

test('the create page shows the URL scout form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('projects.create'))
        ->assertOk()
        ->assertSee('Project scout')
        ->assertSee('Scout my project');
});

test('scouting a github repository drafts a scored project', function () {
    Http::fake([
        'api.github.com/repos/MrPunyapal/awesome-app' => Http::response([
            'full_name' => 'MrPunyapal/awesome-app',
            'name' => 'awesome-app',
            'description' => 'A scalable task queue',
            'html_url' => 'https://github.com/MrPunyapal/awesome-app',
            'language' => 'PHP',
            'topics' => ['laravel', 'redis'],
            'homepage' => 'https://demo.example.com',
            'default_branch' => 'main',
        ], 200),
        'api.github.com/repos/MrPunyapal/awesome-app/readme' => Http::response([
            'content' => base64_encode('Built with Laravel queues to process jobs. Uses Redis for caching and a clean service architecture.'),
        ], 200),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::projects.create')
        ->set('url', 'https://github.com/MrPunyapal/awesome-app')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->call('tick')
        ->assertSet('log.0.state', 'done')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->assertSet('phase', 'review')
        ->assertSet('form.title', 'awesome-app')
        ->assertSet('form.repositoryUrl', 'https://github.com/MrPunyapal/awesome-app')
        ->assertSet('score', 100);
});

test('scouting a generic web page drafts a project', function () {
    Http::fake([
        'https://my-project.dev*' => Http::response('
            <html><head>
                <title>My Cool Project</title>
                <meta name="description" content="A real-time dashboard built with React and Node.">
            </head><body>
                <h1>My Cool Project</h1>
                <p>We built a real-time dashboard that streams metrics over WebSockets.</p>
            </body></html>
        ', 200),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::projects.create')
        ->set('url', 'https://my-project.dev')
        ->call('begin')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->assertSet('phase', 'review')
        ->assertSet('form.demoUrl', 'https://my-project.dev')
        ->assertNotSet('score', null)
        ->assertSet('form.title', 'My Cool Project');
});

test('a draft can be created from a scouted review', function () {
    Http::fake([
        'api.github.com/repos/MrPunyapal/awesome-app' => Http::response([
            'full_name' => 'MrPunyapal/awesome-app',
            'name' => 'awesome-app',
            'description' => 'A scalable task queue',
            'html_url' => 'https://github.com/MrPunyapal/awesome-app',
            'language' => 'PHP',
            'topics' => ['laravel'],
            'homepage' => null,
            'default_branch' => 'main',
        ], 200),
        'api.github.com/repos/MrPunyapal/awesome-app/readme' => Http::response([
            'content' => base64_encode('Processes jobs with Laravel queues and Redis caching.'),
        ], 200),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::projects.create')
        ->set('url', 'https://github.com/MrPunyapal/awesome-app')
        ->call('begin')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->assertSet('phase', 'review')
        ->set('form.problem', 'A hand-edited problem statement.')
        ->call('createDraft')
        ->assertRedirect();

    $project = Project::where('title', 'awesome-app')->first();

    expect($project)->not->toBeNull()
        ->and($project->problem)->toBe('A hand-edited problem statement.')
        ->and($project->ai_score)->not->toBeNull()
        ->and($project->repository_url)->toBe('https://github.com/MrPunyapal/awesome-app');
});

test('scouting an invalid repository returns to the input phase', function () {
    Http::fake([
        'api.github.com/repos/ghost/repo' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::projects.create')
        ->set('url', 'https://github.com/ghost/repo')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->call('tick')
        ->assertSet('phase', 'input')
        ->assertNotSet('error', null);
});

test('the show page renders markdown instead of raw markup', function () {
    $owner = User::factory()->create();

    $project = app(SaveProjectAction::class)->handle($owner, ProjectData::fromArray([
        'title' => 'Markdown Project',
        'tagline' => 'A tagline',
        'problem' => "## The problem\n\nBuilt with <p align=\"left\">raw html</p> and **bold** text.",
        'solution' => "- first\n- second\n\n> A quote",
        'architecture' => 'Some architecture',
        'lessons_learned' => 'Lessons learned',
    ]));

    $this->actingAs($owner)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('The problem', escape: false)
        ->assertSee('<strong>bold</strong>', escape: false)
        ->assertSee('<li>first</li>', escape: false)
        ->assertDontSee('<p align="left">', escape: false)
        ->assertDontSee('raw html</p>', escape: false);
});

test('project cards sanitize raw taglines', function () {
    $user = User::factory()->create();

    Project::create([
        'user_id' => $user->id,
        'title' => 'Tagline Project',
        'tagline' => '<p align="left"><strong>Laravel Engineer &amp; Open Source Maintainer</strong></p>',
        'problem' => 'Problem',
        'solution' => 'Solution',
        'status' => ProjectStatus::Published,
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertSee('Tagline Project')
        ->assertSee('Laravel Engineer & Open Source Maintainer')
        ->assertDontSee('<p align="left">', escape: false);
});

test('scouting a repo with a raw-html readme drafts clean fields', function () {
    Http::fake([
        'api.github.com/repos/acme/tool' => Http::response([
            'full_name' => 'acme/tool',
            'name' => 'tool',
            'description' => 'A tool for teams',
            'html_url' => 'https://github.com/acme/tool',
            'language' => 'PHP',
            'topics' => ['laravel'],
            'homepage' => null,
            'default_branch' => 'main',
        ], 200),
        'api.github.com/repos/acme/tool/readme' => Http::response([
            'content' => base64_encode("<p align=\"left\"><strong>Laravel Engineer</strong></p>\n\n## Open Source\n\n- Core Team @ Pest.\n- Contributor to 100+ repositories."),
        ], 200),
    ]);

    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('pages::projects.create')
        ->set('url', 'https://github.com/acme/tool')
        ->call('begin')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->assertSet('phase', 'review');

    $problem = (string) $component->get('form.problem');

    expect($problem)->not->toContain('<p')
        ->and($problem)->not->toContain('align=');
});

test('the sanitize command converts raw html in content fields to clean text', function () {
    $user = User::factory()->create();

    Project::create([
        'user_id' => $user->id,
        'title' => 'Dirty',
        'tagline' => '<p align="left"><strong>Laravel Engineer &amp; Open Source Maintainer</strong></p>',
        'problem' => "<p align=\"left\">Built with <strong>Laravel</strong> queues.</p>\n\n## Open Source",
        'solution' => '<a href="https://example.com">A solution</a>',
        'architecture' => 'Clean markdown stays untouched',
        'status' => ProjectStatus::Published,
        'published_at' => now(),
    ]);

    $this->artisan('projects:sanitize-content')->assertSuccessful();

    $project = Project::where('title', 'Dirty')->first();

    expect($project->tagline)->toBe('Laravel Engineer & Open Source Maintainer')
        ->and($project->problem)->toContain('Built with Laravel queues')
        ->and($project->problem)->not->toContain('<p')
        ->and($project->solution)->toBe('A solution')
        ->and($project->architecture)->toBe('Clean markdown stays untouched');
});

test('the sanitize command leaves clean markdown fields alone', function () {
    $user = User::factory()->create();

    Project::create([
        'user_id' => $user->id,
        'title' => 'Clean',
        'problem' => "## The problem\n\nSome **bold** reasoning.",
        'solution' => "- one\n- two",
        'status' => ProjectStatus::Published,
        'published_at' => now(),
    ]);

    $this->artisan('projects:sanitize-content')->assertSuccessful();

    $project = Project::where('title', 'Clean')->first();

    expect($project->problem)->toBe("## The problem\n\nSome **bold** reasoning.")
        ->and($project->solution)->toBe("- one\n- two");
});

test('the sanitize command dry-run reports without saving', function () {
    $user = User::factory()->create();

    Project::create([
        'user_id' => $user->id,
        'title' => 'Draft Run',
        'problem' => '<p>Raw markup</p>',
        'solution' => 'A solution',
        'status' => ProjectStatus::Draft,
    ]);

    Artisan::call('projects:sanitize-content', ['--dry-run' => true]);

    expect(Artisan::output())
        ->toContain('dry-run')
        ->toContain('Draft Run');

    expect(Project::where('title', 'Draft Run')->first()->problem)->toBe('<p>Raw markup</p>');
});

test('the markdown helper renders and strips safely', function () {
    expect(Markdown::render("## Title\n\n**bold** and `code`"))
        ->toContain('<h2>Title</h2>')
        ->toContain('<strong>bold</strong>');

    expect(Markdown::plain('<p align="left"><strong>Laravel Engineer</strong></p>'))
        ->toBe('Laravel Engineer');

    expect(Markdown::render('<script>alert(1)</script>'))
        ->not->toContain('<script>');
});

test('the project scout service rejects unreachable pages', function () {
    Http::fake(['https://example.com/*' => Http::response('', 500)]);

    expect(fn () => app(ProjectScoutService::class)->fetch('https://example.com/x'))
        ->toThrow(InvalidArgumentException::class);
});
