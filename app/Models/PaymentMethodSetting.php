<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property PaymentMethod $method
 * @property bool $enabled
 * @property array<string, mixed>|null $settings
 */
class PaymentMethodSetting extends Model
{
    protected $fillable = [
        'method',
        'enabled',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'enabled' => 'boolean',
            'settings' => 'array',
        ];
    }
}
