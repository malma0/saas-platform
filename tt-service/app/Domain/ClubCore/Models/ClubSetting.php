<?php

namespace App\Domain\ClubCore\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Хранилище произвольных настроек клуба (key-value с JSON value).
 * Пример: ['key' => 'booking.min_duration_minutes', 'value' => 30]
 */
class ClubSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = ['club_id', 'key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Получить значение настройки для клуба.
     */
    public static function get(int $clubId, string $key, mixed $default = null): mixed
    {
        $setting = static::withoutGlobalScope('tenant')
            ->where('club_id', $clubId)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }

    /**
     * Установить значение настройки.
     */
    public static function set(int $clubId, string $key, mixed $value): void
    {
        static::withoutGlobalScope('tenant')->updateOrCreate(
            ['club_id' => $clubId, 'key' => $key],
            ['value' => $value]
        );
    }
}
