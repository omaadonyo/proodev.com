<?php

namespace App\Data;

use App\Enums\Visibility;

class JournalData extends DataObject
{
    public ?string $title = null;

    public string $content = '';

    public Visibility $visibility = Visibility::Private;

    public static function fromArray(array $data): static
    {
        $dto = parent::fromArray($data);

        if (isset($data['visibility']) && is_string($data['visibility'])) {
            $dto->visibility = Visibility::tryFrom($data['visibility']) ?? Visibility::Private;
        }

        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    public function persist(): array
    {
        return [
            'title' => $this->title ?: null,
            'content' => $this->content,
            'visibility' => $this->visibility,
        ];
    }
}
