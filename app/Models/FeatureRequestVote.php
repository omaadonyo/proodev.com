<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureRequestVote extends Model
{
    public $timestamps = false;

    protected $fillable = ['feature_request_id', 'user_id', 'created_at'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
