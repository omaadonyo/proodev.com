<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reporter_id
 * @property string|null $reportable_type
 * @property int|null $reportable_id
 * @property string $reason
 * @property string|null $details
 * @property ReportStatus $status
 * @property int|null $handled_by
 * @property Carbon|null $handled_at
 */
class Report extends Model
{
    protected $fillable = ['reporter_id', 'reportable_type', 'reportable_id', 'reason', 'details', 'status', 'handled_by', 'handled_at'];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
