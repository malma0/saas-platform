<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Определение версии сайта (мобильная/десктоп) по устройству И разрешению.
 *
 * Приоритет:
 *   1. ?m=1 / ?m=0 — ручное переключение (для показа), запоминается в куку.
 *   2. Запомненный ручной выбор (кука tt_view).
 *   3. АВТО: телефон по User-Agent → всегда мобильная;
 *      иначе — по реальной ширине окна (кука tt_vw, её ставит device.js):
 *      уже порога → мобильная, шире → десктоп.
 *   4. До первого замера ширины (первый рендер) — по User-Agent.
 *
 * Так одна ссылка сама отдаёт правильную версию: телефон → мобильная,
 * широкий десктоп → десктоп, узкое окно десктопа → мобильная (ничего не «съезжает»).
 */
final class Device
{
    /** Ширина (px), ниже которой показываем мобильную версию. */
    public const BREAKPOINT = 860;

    private const COOKIE_FORCE = 'tt_view'; // ручной выбор: m|d
    private const COOKIE_WIDTH = 'tt_vw';   // измеренная ширина окна
    private const TTL_MIN = 43200;          // 30 дней

    public static function isMobile(?Request $request = null): bool
    {
        $request ??= request();

        $force = $request->query('m');
        if ($force === '1') {
            Cookie::queue(self::COOKIE_FORCE, 'm', self::TTL_MIN);
            return true;
        }
        if ($force === '0') {
            Cookie::queue(self::COOKIE_FORCE, 'd', self::TTL_MIN);
            return false;
        }

        $forced = $request->cookie(self::COOKIE_FORCE);
        if ($forced === 'm') {
            return true;
        }
        if ($forced === 'd') {
            return false;
        }

        $phone = self::isPhoneUserAgent((string) $request->userAgent());
        $width = (int) $request->cookie(self::COOKIE_WIDTH);

        if ($width > 0) {
            // Телефон — всегда мобильная (даже в альбомной), иначе решает ширина.
            return $phone || $width < self::BREAKPOINT;
        }

        // Первый рендер: ширину ещё не замерили — ориентируемся на устройство.
        return $phone;
    }

    private static function isPhoneUserAgent(string $ua): bool
    {
        return $ua !== '' && (bool) preg_match(
            '/Mobile|Android|iPhone|iPod|Windows Phone|BlackBerry|webOS|Opera Mini|IEMobile/i',
            $ua
        );
    }
}
