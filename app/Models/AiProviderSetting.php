<?php

namespace App\Models;

use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property AiProvider $provider
 * @property bool $enabled
 * @property bool $active
 * @property string|null $api_key
 * @property string|null $base_url
 * @property string|null $model
 * @property array<string, mixed>|null $settings
 */
class AiProviderSetting extends Model
{
    protected $fillable = [
        'provider',
        'enabled',
        'active',
        'api_key',
        'base_url',
        'model',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'enabled' => 'boolean',
            'active' => 'boolean',
            'settings' => 'array',
        ];
    }
}
