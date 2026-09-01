<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_by',
        'title',
        'description',
        'votes',
        'status',
        'admin_target',
        'target_votes',
        'votes_to_build',
        'approved_at',
        'built_at',
        'included_at',
    ];

    protected $casts = [
        'votes' => 'integer',
        'admin_target' => 'integer',
        'target_votes' => 'integer',
        'votes_to_build' => 'integer',
        'approved_at' => 'datetime',
        'built_at' => 'datetime',
        'included_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            // Keep legacy and new columns in sync so either schema works
            if ($model->isDirty('user_id') && empty($model->created_by)) {
                $model->created_by = $model->user_id;
            } elseif ($model->isDirty('created_by') && empty($model->user_id)) {
                $model->user_id = $model->created_by;
            }
            if ($model->isDirty('admin_target') && empty($model->target_votes)) {
                $model->target_votes = $model->admin_target;
            } elseif ($model->isDirty('target_votes') && empty($model->admin_target)) {
                $model->admin_target = $model->target_votes;
            }
            if ($model->isDirty('built_at') && empty($model->included_at)) {
                $model->included_at = $model->built_at;
            } elseif ($model->isDirty('included_at') && empty($model->built_at)) {
                $model->built_at = $model->included_at;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function votesRelation()
    {
        return $this->hasMany(FeatureRequestVote::class);
    }

    public function hasVotedBy(int $userId): bool
    {
        return $this->votesRelation()->where('user_id', $userId)->exists();
    }
}