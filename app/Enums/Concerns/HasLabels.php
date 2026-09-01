<?php

namespace App\Enums\Concerns;

trait HasLabels
{
    public function label(): string
    {
        if (defined(static::class.'::LABELS')) {
            return static::LABELS[$this->value] ?? ucfirst(strtolower($this->name));
        }

        return ucfirst(strtolower($this->name));
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * @param  array<int, self>  $cases
     * @return array<string, string>
     */
    public static function optionsFor(array $cases): array
    {
        $options = [];

        foreach ($cases as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
