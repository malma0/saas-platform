<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Booking\Cache\AvailabilityCache;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Scheduling\Models\ScheduleTemplate;
use App\Domain\Scheduling\Models\ServiceSession;
use App\Support\Enums\SessionStatus;
use Carbon\Carbon;
use RRule\RRule;

/**
 * Материализует ServiceSession-записи из ScheduleTemplate на горизонт N дней.
 *
 * Каждая сессия = конкретный слот в базе + резерв ресурсов (session_resources).
 * Это позволяет AvailabilityService видеть их как занятые слоты.
 */
class SessionMaterializer
{
    /** Горизонт материализации вперёд от сегодня (в днях). */
    public const HORIZON_DAYS = 90;

    /**
     * Материализует занятия для шаблона на горизонт HORIZON_DAYS дней вперёд.
     * Идемпотентно: повторный вызов не создаёт дубликаты.
     *
     * @param array<int> $resourceIds  Ресурсы для резерва (передаём явно, не берём из требований)
     * @return ServiceSession[]        Новые сессии (уже существующие не включаются)
     */
    public function materialize(ScheduleTemplate $template, array $resourceIds): array
    {
        $branch   = Branch::find($template->branch_id);
        $tz       = $branch?->timezone ?? 'UTC';

        $horizon  = Carbon::now($tz)->addDays(self::HORIZON_DAYS)->endOfDay();
        $from     = Carbon::now($tz)->startOfDay();

        // Если шаблон уже завершён — не материализуем
        if ($template->end_date && $template->end_date->lt($from)) {
            return [];
        }

        $occurrences = $this->expand($template, $from, $horizon, $tz);
        $created     = [];

        foreach ($occurrences as $localStart) {
            $localEnd = $localStart->copy()->addMinutes($template->duration_minutes);

            // Конвертируем в UTC для хранения
            $startUtc = $localStart->copy()->utc();
            $endUtc   = $localEnd->copy()->utc();

            // Идемпотентность: проверяем существование по template+start_at
            $exists = ServiceSession::withoutGlobalScopes()
                ->where('schedule_template_id', $template->id)
                ->where('start_at', $startUtc)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $session = ServiceSession::create([
                'club_id'              => $template->club_id,
                'schedule_template_id' => $template->id,
                'start_at'             => $startUtc,
                'end_at'               => $endUtc,
                'status'               => SessionStatus::Scheduled,
                'is_cancelled'         => false,
            ]);

            // Привязываем ресурсы
            if (!empty($resourceIds)) {
                $session->resources()->syncWithoutDetaching($resourceIds);
            }

            $created[] = $session;
        }

        // Резерв изменил доступность филиала → сбрасываем кэш (Фаза 14: «и резерва»)
        if (! empty($created)) {
            app(AvailabilityCache::class)->invalidateBranch($template->branch_id);
        }

        return $created;
    }

    /**
     * Расширяем RRULE в конкретные локальные DateTimes на горизонте.
     *
     * @return Carbon[]
     */
    public function expand(ScheduleTemplate $template, Carbon $from, Carbon $until, string $tz): array
    {
        // Строим DTSTART из start_date + start_time в локальной timezone
        [$h, $m] = explode(':', $template->start_time);
        $dtStart = Carbon::parse($template->start_date->format('Y-m-d'), $tz)
            ->setTime((int)$h, (int)$m);

        // Если шаблон заканчивается раньше горизонта — используем end_date
        $rruleUntil = $template->end_date
            ? min($until, Carbon::parse($template->end_date->format('Y-m-d'), $tz)->endOfDay())
            : $until;

        // Собираем итоговое правило
        $ruleStr = $template->recurrence_rule;

        // Добавляем UNTIL если нет COUNT
        if (!str_contains(strtoupper($ruleStr), 'UNTIL') && !str_contains(strtoupper($ruleStr), 'COUNT')) {
            $ruleStr .= ';UNTIL=' . $rruleUntil->utc()->format('Ymd\THis\Z');
        }

        // rlanvin/php-rrule принимает плоский массив параметров (без ключа RRULE)
        // Разбираем строку вида "FREQ=WEEKLY;BYDAY=MO,WE" в массив
        try {
            $params = ['DTSTART' => $dtStart->format('Y-m-d\TH:i:s')];
            foreach (explode(';', $ruleStr) as $part) {
                if (!str_contains($part, '=')) {
                    continue;
                }
                [$key, $val] = explode('=', $part, 2);
                $params[strtoupper(trim($key))] = trim($val);
            }

            $rrule = new RRule($params);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                "Неверное RRULE «{$template->recurrence_rule}»: {$e->getMessage()}"
            );
        }

        $occurrences = [];
        foreach ($rrule as $occurrence) {
            $dt = Carbon::instance($occurrence)->setTimezone($tz);

            // Пропускаем прошлые
            if ($dt->lt($from)) {
                continue;
            }
            if ($dt->gt($rruleUntil)) {
                break;
            }

            $occurrences[] = $dt;
        }

        return $occurrences;
    }
}
