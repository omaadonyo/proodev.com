<?php

namespace App\Data;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVerificationStatus;

class ProjectData extends DataObject
{
    public string $title = '';

    public string $tagline = '';

    public string $problem = '';

    public string $solution = '';

    public string $architecture = '';

    public array $techStack = [];

    public array $screenshots = [];

    public array $engineeringDecisions = [];

    public string $lessonsLearned = '';

    public ?string $demoUrl = null;

    public ?string $repositoryUrl = null;

    public ?int $aiScore = null;

    public ProjectStatus $status = ProjectStatus::Draft;

    public static function fromArray(array $data): static
    {
        $dto = parent::fromArray($data);

        if (isset($data['status']) && is_string($data['status'])) {
            $dto->status = ProjectStatus::tryFrom($data['status']) ?? ProjectStatus::Draft;
        }

        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    public function persist(): array
    {
        return [
            'title' => $this->title,
            'tagline' => $this->tagline,
            'problem' => $this->problem,
            'solution' => $this->solution,
            'architecture' => $this->architecture,
            'tech_stack' => $this->techStack,
            'screenshots' => $this->screenshots,
            'engineering_decisions' => $this->engineeringDecisions,
            'lessons_learned' => $this->lessonsLearned,
            'demo_url' => $this->demoUrl ?: null,
            'repository_url' => $this->repositoryUrl ?: null,
            'ai_score' => $this->aiScore,
            'status' => $this->status,
            'verification_status' => ProjectVerificationStatus::Unverified,
        ];
    }
}
