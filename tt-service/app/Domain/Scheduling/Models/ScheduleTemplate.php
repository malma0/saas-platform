<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Facilities\Models\Branch;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\Ulid;

class ScheduleTemplate extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'public_id',
        'club_id',
        'branch_id',
        'service_offering_id',
        'recurrence_rule',
        'start_date',
        'end_date',
        'start_time',
        'duration_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date'       => 'date',
            'end_date'         => 'date',
            'duration_minutes' => 'integer',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ServiceSession::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }
}
