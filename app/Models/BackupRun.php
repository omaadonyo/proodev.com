<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $file_name
 * @property string $file_path
 * @property int $file_size
 * @property string $status
 * @property string|null $error
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $emailed_at
 */
class BackupRun extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'file_size',
        'status',
        'error',
        'started_at',
        'completed_at',
        'emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'emailed_at' => 'datetime',
        ];
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function humanSize(): string
    {
        $bytes = max(0, (int) $this->file_size);

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, $bytes < 10 && $unit !== 'B' ? 2 : 0).' '.$unit;
            }

            $bytes /= 1024;
        }

        return round($bytes, 2).' TB';
    }
}
