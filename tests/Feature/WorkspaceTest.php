<?php

use App\Enums\CompanyPlan;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\RecruiterNote;
use App\Models\TalentAlert;
use App\Models\TalentPool;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\Recruiter\AgencyWorkspaceService;
use App\Services\Recruiter\WorkspaceService;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function wsOwner(): User
{
    return User::factory()->create(['role' => UserRole::Recruiter]);
}

function wsMember(string $email = 'member@example.test'): User
{
    return User::factory()->create(['role' => UserRole::Recruiter, 'email' => $email]);
}

test('a recruiter can create a workspace and becomes the owner seat', function () {
    $recruiter = wsOwner();

    $workspace = app(WorkspaceService::class)->create($recruiter, 'Northstar Talent');

    expect($workspace->owner_id)->toBe($recruiter->id)
        ->and(WorkspaceMember::where('workspace_id', $workspace->id)->where('user_id', $recruiter->id)->where('role', 'owner')->exists())->toBeTrue()
        ->and($workspace->users()->count())->toBe(1)
        ->and(app(WorkspaceService::class)->current($recruiter)->id)->toBe($workspace->id);
});

test('the workspaces page renders and creates a workspace', function () {
    $recruiter = wsOwner();

    Livewire::actingAs($recruiter)
        ->test('pages::workspaces')
        ->assertOk()
        ->assertSee('Workspaces')
        ->set('newName', 'Acme Recruiting')
        ->call('createWorkspace')
        ->assertRedirect();

    expect(Workspace::where('name', 'Acme Recruiting')->exists())->toBeTrue();
});

test('the owner can add a member seat and the member can switch to the workspace', function () {
    $recruiter = wsOwner();
    $member = wsMember();

    $workspace = app(WorkspaceService::class)->create($recruiter, 'Agency');

    app(WorkspaceService::class)->addMember($workspace, $member, 'member', $recruiter);

    expect($workspace->users()->whereKey($member->id)->exists())->toBeTrue()
        ->and($workspace->users()->count())->toBe(2);

    app(WorkspaceService::class)->switchToId($member, $workspace->id);

    expect(app(WorkspaceService::class)->current($member)->id)->toBe($workspace->id)
        ->and(app(WorkspaceService::class)->available($member)->contains('id', $workspace->id))->toBeTrue();
});

test('adding a member via the workspaces page works', function () {
    $recruiter = wsOwner();
    $member = wsMember('other@example.test');

    $workspace = app(WorkspaceService::class)->create($recruiter, 'Agency');

    Livewire::actingAs($recruiter)
        ->test('pages::workspaces')
        ->call('manageWorkspace', $workspace->id)
        ->set('memberEmail', 'other@example.test')
        ->call('addMember')
        ->assertHasNoErrors('memberEmail');

    expect($workspace->users()->whereKey($member->id)->exists())->toBeTrue();
});

test('a non-owner cannot manage or remove seats', function () {
    $recruiter = wsOwner();
    $member = wsMember();

    $workspace = app(WorkspaceService::class)->create($recruiter, 'Agency');

    app(WorkspaceService::class)->addMember($workspace, $member, 'member', $recruiter);

    Livewire::actingAs($member)
        ->test('pages::workspaces')
        ->call('manageWorkspace', $workspace->id)
        ->assertStatus(403);
});

test('the owner cannot be removed from a workspace', function () {
    $recruiter = wsOwner();

    $workspace = app(WorkspaceService::class)->create($recruiter, 'Agency');

    expect(fn () => app(WorkspaceService::class)->removeMember($workspace, $recruiter, $recruiter))
        ->toThrow(HttpException::class);
});

test('talent pools are scoped to the active workspace', function () {
    $recruiter = wsOwner();
    $workspace = app(WorkspaceService::class)->create($recruiter, 'Client A');

    $service = app(AgencyWorkspaceService::class);
    $service->createPool($recruiter, ['name' => 'Backend shortlist', 'kind' => 'collection']);

    expect(TalentPool::where('workspace_id', $workspace->id)->count())->toBe(1);

    $other = app(WorkspaceService::class)->create($recruiter, 'Client B');
    app(WorkspaceService::class)->switchToId($recruiter, $other->id);

    $service->createPool($recruiter, ['name' => 'Frontend shortlist', 'kind' => 'collection']);

    expect($service->overview($recruiter)['pools']->pluck('name'))->toContain('Frontend shortlist')
        ->and($service->overview($recruiter)['pools']->pluck('name'))->not->toContain('Backend shortlist')
        ->and(TalentPool::where('workspace_id', $workspace->id)->count())->toBe(1)
        ->and(TalentPool::where('workspace_id', $other->id)->count())->toBe(1);
});

test('alerts and notes are scoped to the active workspace', function () {
    $recruiter = wsOwner();
    $candidate = User::factory()->create();

    $workspaceA = app(WorkspaceService::class)->create($recruiter, 'Client A');
    app(AgencyWorkspaceService::class)->addNote($recruiter, $candidate, 'Great backend depth.');
    TalentAlert::create([
        'workspace_id' => $workspaceA->id,
        'recruiter_id' => $recruiter->id,
        'name' => 'Laravel in Berlin',
        'criteria' => ['skills' => ['laravel'], 'location' => 'Berlin'],
    ]);

    $workspaceB = app(WorkspaceService::class)->create($recruiter, 'Client B');
    app(WorkspaceService::class)->switchToId($recruiter, $workspaceB->id);

    expect(RecruiterNote::where('workspace_id', $workspaceA->id)->count())->toBe(1)
        ->and(app(AgencyWorkspaceService::class)->notes($recruiter)->count())->toBe(0);

    $alertsPage = Livewire::actingAs($recruiter)->test('pages::recruiter.alerts');

    expect($alertsPage->alerts->count())->toBe(0);

    app(WorkspaceService::class)->switchToId($recruiter, $workspaceA->id);

    expect(app(AgencyWorkspaceService::class)->notes($recruiter)->count())->toBe(1)
        ->and(app(AgencyWorkspaceService::class)->alerts($recruiter)->count())->toBe(1);
});

test('a member seat sees shared workspace pools', function () {
    $recruiter = wsOwner();
    $member = wsMember();

    $workspace = app(WorkspaceService::class)->create($recruiter, 'Shared Agency');
    app(WorkspaceService::class)->addMember($workspace, $member, 'member', $recruiter);

    app(AgencyWorkspaceService::class)->createPool($recruiter, ['name' => 'Shared pool', 'kind' => 'collection']);

    app(WorkspaceService::class)->switchToId($member, $workspace->id);

    expect(app(AgencyWorkspaceService::class)->overview($member)['pools']->pluck('name'))->toContain('Shared pool');
});

test('the workspace switcher lists available workspaces for the user', function () {
    $recruiter = wsOwner();
    app(WorkspaceService::class)->create($recruiter, 'Northstar');

    Livewire::actingAs($recruiter)
        ->test('workspace-switcher')
        ->assertOk()
        ->assertSee('Northstar');
});

test('the workspace switcher renders for a recruiter with no workspaces yet', function () {
    $recruiter = wsOwner();

    Livewire::actingAs($recruiter)
        ->test('workspace-switcher')
        ->assertOk()
        ->assertSee('Create workspace')
        ->assertSee('No workspaces yet.');
});

test('the workspace switcher renders in the sidebar for a free company account', function () {
    $owner = User::factory()->create(['role' => UserRole::Company]);
    Company::factory()->create(['owner_id' => $owner->id, 'plan' => CompanyPlan::Trial, 'status' => 'approved']);

    $this->actingAs($owner)
        ->get(route('subscription'))
        ->assertOk()
        ->assertSee('Upgrade for Workspaces');
});

test('a free company account is blocked from the workspaces page', function () {
    $owner = User::factory()->create(['role' => UserRole::Company]);
    Company::factory()->create(['owner_id' => $owner->id, 'plan' => CompanyPlan::Trial, 'status' => 'approved']);

    $this->actingAs($owner)
        ->get(route('workspaces'))
        ->assertForbidden();
});

test('a company owner on a paid plan can access the workspaces page', function () {
    $owner = User::factory()->create(['role' => UserRole::Company]);
    Company::factory()->create(['owner_id' => $owner->id, 'plan' => CompanyPlan::Intelligence, 'status' => 'approved']);

    $this->actingAs($owner)
        ->get(route('workspaces'))
        ->assertOk();
});
