<?php

namespace App\Actions\Moderation;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReportContentAction
{
    public function handle(User $reporter, Model $reportable, string $reason, ?string $details = null): Report
    {
        return Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $reportable::class,
            'reportable_id' => $reportable->getKey(),
            'reason' => $reason,
            'details' => $details,
            'status' => ReportStatus::Open,
        ]);
    }
}
