<?php

namespace App\Livewire\Forms;

use App\Data\VouchData;
use App\Enums\VouchType;
use Livewire\Attributes\Rule;
use Livewire\Form;

class VouchForm extends Form
{
    public ?int $voucheeId = null;

    #[Rule(['required', 'in:skill,architecture,project,mentorship,code-review,collaboration'])]
    public string $type = 'skill';

    #[Rule(['nullable', 'exists:skills,id'])]
    public ?int $skillId = null;

    #[Rule(['nullable', 'string', 'max:500'])]
    public ?string $message = null;

    public function data(): VouchData
    {
        return VouchData::fromArray([
            'vouchee_id' => $this->voucheeId,
            'type' => VouchType::tryFrom($this->type) ?? VouchType::Skill,
            'skill_id' => $this->skillId,
            'message' => $this->message,
        ]);
    }
}
