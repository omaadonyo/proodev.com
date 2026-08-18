<?php

namespace App\Models;

use App\Enums\RecognitionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRecognition extends Model
{
    protected $fillable = ['project_id', 'user_id', 'type'];

    protected function casts(): array
    {
        return [
            'type' => RecognitionType::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
