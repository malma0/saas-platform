<?php

namespace App\Domain\Identity\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Сервис контроля доступа.
 *
 * Отвечает за вопросы:
 *  - может ли пользователь видеть данный клуб/филиал?
 *  - какие филиалы доступны пользователю?
 */
class AccessControlService
{
    /**
     * Проверяет, принадлежит ли пользователь указанному клубу.
     * Superadmin принадлежит всем клубам.
     */
    public function userBelongsToClub(User $user, int $clubId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->club_id === $clubId;
    }

    /**
     * Может ли пользователь видеть данный клуб.
     */
    public function canAccessClub(User $user, int $clubId): bool
    {
        return $this->userBelongsToClub($user, $clubId);
    }

    /**
     * Может ли пользователь видеть данный филиал.
     *
     * Owner/superadmin → все филиалы своего клуба.
     * Admin/user → только филиалы из user_branch_access.
     */
    public function canAccessBranch(User $user, int $branchId, int $clubId): bool
    {
        if (! $this->userBelongsToClub($user, $clubId)) {
            return false;
        }

        // Owner и superadmin видят все филиалы
        if ($user->hasAnyRole(['superadmin', 'owner'])) {
            return true;
        }

        // Для admin/user — проверяем явную привязку
        return \Illuminate\Support\Facades\DB::table('user_branch_access')
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->exists();
    }

    /**
     * Филиалы, доступные пользователю.
     *
     * null — без ограничений (superadmin/owner, либо для admin/user
     * не настроено ни одной записи в user_branch_access — обратная
     * совместимость: видит все филиалы своего клуба).
     *
     * @return array<int>|null
     */
    public function accessibleBranchIds(User $user): ?array
    {
        if ($user->hasAnyRole(['superadmin', 'owner'])) {
            return null;
        }

        $ids = \Illuminate\Support\Facades\DB::table('user_branch_access')
            ->where('user_id', $user->id)
            ->pluck('branch_id')
            ->all();

        return $ids === [] ? null : array_map('intval', $ids);
    }

    /**
     * Назначить пользователю доступ к филиалу.
     */
    public function grantBranchAccess(User $user, int $branchId): void
    {
        \Illuminate\Support\Facades\DB::table('user_branch_access')->insertOrIgnore([
            'user_id'    => $user->id,
            'branch_id'  => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Отозвать доступ пользователя к филиалу.
     */
    public function revokeBranchAccess(User $user, int $branchId): void
    {
        \Illuminate\Support\Facades\DB::table('user_branch_access')
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->delete();
    }
}
