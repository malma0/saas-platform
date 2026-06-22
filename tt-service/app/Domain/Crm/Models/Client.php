<?php

namespace App\Domain\Crm\Models;

use App\Domain\Booking\Models\Booking;
use App\Support\Tenancy\BelongsToTenant;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Symfony\Component\Uid\Ulid;

class Client extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity, SoftDeletes;

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }

    /**
     * Аудит (Правило 5): фиксируем изменения карточки клиента и блокировку.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('client')
            ->logOnly(['first_name', 'last_name', 'phone', 'email', 'is_blocked', 'block_reason', 'deleted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'public_id',
        'club_id',
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'birth_date',
        'gender',
        'quick_note',
        'source',
        'is_blocked',
        'block_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_blocked' => 'boolean',
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

    // -------------------------------------------------------------------------
    // Вычисляемые атрибуты
    // -------------------------------------------------------------------------

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class)->orderByDesc('is_pinned')->orderByDesc('created_at');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ClientTag::class, 'client_tag_pivot');
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(ClientPreference::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->orderByDesc('start_at');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // -------------------------------------------------------------------------
    // Работа с предпочтениями (key-value)
    // -------------------------------------------------------------------------

    public function getPreference(string $key, mixed $default = null): mixed
    {
        $pref = $this->preferences()->where('key', $key)->first();
        return $pref ? $pref->value : $default;
    }

    public function setPreference(string $key, mixed $value): void
    {
        $this->preferences()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
