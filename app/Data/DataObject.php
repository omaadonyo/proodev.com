<?php

namespace App\Data;

use Illuminate\Support\Str;

abstract class DataObject
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        $dto = new static;

        foreach ($data as $key => $value) {
            $method = 'set'.Str::studly($key);

            if (method_exists($dto, $method)) {
                $dto->{$method}($value);

                continue;
            }

            $property = property_exists($dto, $key) ? $key : Str::camel($key);

            if (! property_exists($dto, $property)) {
                continue;
            }

            $dto->{$property} = self::coerce($dto, $property, $value);
        }

        return $dto;
    }

    private static function coerce(object $dto, string $property, mixed $value): mixed
    {
        $type = (new \ReflectionProperty($dto, $property))->getType();

        if ($value === null || $value === '') {
            return $type instanceof \ReflectionNamedType && $type->allowsNull() ? null : $value;
        }

        if (! $type instanceof \ReflectionNamedType || ! enum_exists($type->getName())) {
            return $value;
        }

        $enum = $type->getName();

        if ($value instanceof $enum) {
            return $value;
        }

        if (is_string($value)) {
            return $enum::tryFrom($value) ?? $value;
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
