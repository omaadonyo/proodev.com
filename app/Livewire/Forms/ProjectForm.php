<?php

namespace App\Livewire\Forms;

use App\Data\ProjectData;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Livewire\Attributes\Rule;
use Livewire\Form;

class ProjectForm extends Form
{
    public ?int $projectId = null;

    #[Rule(['required', 'string', 'max:120'])]
    public string $title = '';

    #[Rule(['nullable', 'string', 'max:180'])]
    public string $tagline = '';

    #[Rule(['required', 'string', 'max:5000'])]
    public string $problem = '';

    #[Rule(['required', 'string', 'max:5000'])]
    public string $solution = '';

    #[Rule(['nullable', 'string', 'max:10000'])]
    public string $architecture = '';

    #[Rule(['nullable', 'array', 'max:30'])]
    public array $techStack = [];

    #[Rule(['nullable', 'array', 'max:12'])]
    public array $screenshots = [];

    #[Rule(['nullable', 'string', 'max:10000'])]
    public string $lessonsLearned = '';

    #[Rule(['nullable', 'array', 'max:30'])]
    public array $engineeringDecisions = [];

    #[Rule(['nullable', 'url', 'max:255'])]
    public ?string $demoUrl = null;

    #[Rule(['nullable', 'url', 'max:255'])]
    public ?string $repositoryUrl = null;

    public ?int $aiScore = null;

    #[Rule(['required', 'in:draft,published'])]
    public string $status = 'draft';

    #[Rule(['nullable', 'string', 'max:100'])]
    public string $newTech = '';

    /**
     * @param  array<string, mixed>  $draft
     */
    public function applyDraft(array $draft): void
    {
        $this->title = (string) ($draft['title'] ?? '');
        $this->tagline = (string) ($draft['tagline'] ?? '');
        $this->problem = (string) ($draft['problem'] ?? '');
        $this->solution = (string) ($draft['solution'] ?? '');
        $this->architecture = (string) ($draft['architecture'] ?? '');
        $this->techStack = array_values(array_filter((array) ($draft['tech_stack'] ?? [])));
        $this->engineeringDecisions = array_values(array_filter((array) ($draft['engineering_decisions'] ?? [])));
        $this->lessonsLearned = (string) ($draft['lessons_learned'] ?? '');
        $this->demoUrl = ($draft['demo_url'] ?? null) ?: null;
        $this->repositoryUrl = ($draft['repository_url'] ?? null) ?: null;
        $this->aiScore = isset($draft['score']) ? (int) $draft['score'] : null;
    }

    public function set(Project $project): void
    {
        $this->projectId = $project->id;
        $this->title = $project->title;
        $this->tagline = $project->tagline ?? '';
        $this->problem = $project->problem;
        $this->solution = $project->solution;
        $this->architecture = $project->architecture ?? '';
        $this->techStack = $project->tech_stack ?? [];
        $this->screenshots = $project->screenshots ?? [];
        $this->lessonsLearned = $project->lessons_learned ?? '';
        $this->engineeringDecisions = $project->engineering_decisions ?? [];
        $this->demoUrl = $project->demo_url;
        $this->repositoryUrl = $project->repository_url;
        $this->aiScore = $project->ai_score;
        $this->status = $project->status->value;
    }

    public function data(): ProjectData
    {
        return ProjectData::fromArray([
            'title' => $this->title,
            'tagline' => $this->tagline,
            'problem' => $this->problem,
            'solution' => $this->solution,
            'architecture' => $this->architecture,
            'tech_stack' => $this->techStack,
            'screenshots' => $this->screenshots,
            'engineering_decisions' => $this->engineeringDecisions,
            'lessons_learned' => $this->lessonsLearned,
            'demo_url' => $this->demoUrl,
            'repository_url' => $this->repositoryUrl,
            'ai_score' => $this->aiScore,
            'status' => $this->status === 'published' ? ProjectStatus::Published : ProjectStatus::Draft,
        ]);
    }
}
