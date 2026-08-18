<?php

namespace App\Data;

use App\Enums\VouchType;

class VouchData extends DataObject
{
    public int $voucheeId;

    public VouchType $type = VouchType::Skill;

    public ?int $skillId = null;

    public ?string $message = null;

    public static function fromArray(array $data): static
    {
        $dto = parent::fromArray($data);

        if (isset($data['type']) && is_string($data['type'])) {
            $dto->type = VouchType::tryFrom($data['type']) ?? VouchType::Skill;
        }

        return $dto;
    }
}
