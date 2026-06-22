<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Symfony\Component\Uid\Ulid;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'public_id',
        'club_id',
        'name',
        'email',
        'avatar',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        // Авто-генерация ULID
        static::creating(function (User $user) {
            if (empty($user->public_id)) {
                $user->public_id = strtolower((string) new Ulid());
            }
        });

        /**
         * Глобальный scope мультиарендности для User.
         *
         * Суперадмин (club_id = null) видит всех пользователей.
         * Все остальные видят только пользователей своего клуба.
         */
        static::addGlobalScope('tenant', function (Builder $query) {
            // ВАЖНО: Auth::hasUser() НЕ запускает резолв пользователя.
            // Без этой защиты, когда панель логинит через App\Models\User,
            // резолв юзера из сессии делает запрос к User → применяет этот scope
            // → снова Auth::user() → бесконечная рекурсия (исчерпание памяти).
            if (! Auth::hasUser()) {
                return;
            }

            $user = Auth::user();

            if (! $user || $user->club_id === null) {
                return; // superadmin или неаутентифицированный — без ограничений
            }

            $query->where('users.club_id', $user->club_id);
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Суперадмин — пользователь без привязки к клубу.
     */
    public function isSuperAdmin(): bool
    {
        return $this->club_id === null && $this->hasRole('superadmin');
    }

    // -------------------------------------------------------------------------
    // Отношения
    // -------------------------------------------------------------------------

    public function club(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Domain\ClubCore\Models\Club::class);
    }
}
