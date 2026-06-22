<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Трейт мультиарендности.
 *
 * Подключается ко всем бизнес-моделям, имеющим club_id.
 * Автоматически:
 *  - фильтрует запросы по club_id текущего пользователя;
 *  - подставляет club_id при создании записи.
 *
 * Superadmin (club_id = null) видит все клубы — scope не применяется.
 */
trait BelongsToTenant
{
    /**
     * Загружает глобальный scope при инициализации модели.
     */
    public static function bootBelongsToTenant(): void
    {
        // Глобальный scope — фильтрация по клубу
        static::addGlobalScope('tenant', function (Builder $query) {
            $user = Auth::user();

            // Superadmin (club_id = null) видит всё
            if (! $user || $user->club_id === null) {
                return;
            }

            $query->where(
                (new static())->getTable() . '.club_id',
                $user->club_id
            );
        });

        // Авто-подстановка club_id при создании
        static::creating(function ($model) {
            if (empty($model->club_id)) {
                $user = Auth::user();
                if ($user && $user->club_id !== null) {
                    $model->club_id = $user->club_id;
                }
            }
        });
    }

    /**
     * Scope для явного обхода ограничения (суперадмин, джобы).
     */
    public function scopeForClub(Builder $query, int $clubId): Builder
    {
        return $query->withoutGlobalScope('tenant')->where('club_id', $clubId);
    }
}
