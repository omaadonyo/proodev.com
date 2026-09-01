<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'logo_url', 'website_url', 'tagline', 'is_active', 'sort_order', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Restrict to records whose run window is active right now
     * (no start date, or start date in the past; no end date, or end date in the future).
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * Schedule state for the admin table: upcoming, running, ended, or null (always).
     */
    public function runStatus(): ?string
    {
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'upcoming';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'ended';
        }

        return 'running';
    }
}
