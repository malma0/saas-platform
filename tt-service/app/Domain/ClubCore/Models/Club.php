<?php

namespace App\Domain\ClubCore\Models;

use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\Ulid;

class Club extends Model
{
    /** @use HasFactory<ClubFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): ClubFactory
    {
        return ClubFactory::new();
    }

    protected $fillable = [
        'public_id',
        'name',
        'email',
        'phone',
        'website',
        'address',
        'default_currency_code',
        'default_locale',
        'timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Club $club) {
            if (empty($club->public_id)) {
                $club->public_id = strtolower((string) new Ulid());
            }
        });
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(\App\Domain\Facilities\Models\Branch::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ClubSetting::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return ClubSetting::get($this->id, $key, $default);
    }

    public function setSetting(string $key, mixed $value): void
    {
        ClubSetting::set($this->id, $key, $value);
    }
}
