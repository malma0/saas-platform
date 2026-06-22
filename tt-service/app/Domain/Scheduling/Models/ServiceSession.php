<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Facilities\Models\Resource;
use App\Support\Enums\SessionStatus;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\Ulid;

class ServiceSession extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'public_id',
        'club_id',
        'schedule_template_id',
        'start_at',
        'end_at',
        'status',
        'is_cancelled',
    ];

    protected function casts(): array
    {
        return [
            'start_at'     => 'datetime',
            'end_at'       => 'datetime',
            'status'       => SessionStatus::class,
            'is_cancelled' => 'boolean',
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }

    /**
     * Ресурсы, зарезервированные этим занятием.
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'session_resources')
                    ->withTimestamps();
    }

    public function isCancelled(): bool
    {
        return $this->is_cancelled || $this->status === SessionStatus::Cancelled;
    }
}
