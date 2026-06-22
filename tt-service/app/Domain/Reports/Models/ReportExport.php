<?php

namespace App\Domain\Reports\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\Ulid;

class ReportExport extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'public_id',
        'club_id',
        'requested_by',
        'report_type',
        'params',
        'status',
        'file_path',
        'file_format',
        'error',
        'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'params'   => 'array',
            'ready_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->public_id)) {
                $model->public_id = strtolower((string) new Ulid());
            }
        });
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function markReady(string $filePath): void
    {
        $this->update([
            'status'    => 'ready',
            'file_path' => $filePath,
            'ready_at'  => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => 'failed', 'error' => $error]);
    }

    public function isReady(): bool { return $this->status === 'ready'; }
    public function isPending(): bool { return $this->status === 'pending'; }
}
