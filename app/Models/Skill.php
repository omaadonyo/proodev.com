<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = ['name', 'slug', 'category'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')->withPivot('level', 'verified_at', 'times_used')->withTimestamps();
    }
}
