{{-- Форма выгрузки отчётов (Фаза 11). GET → скачивание файла. --}}
@php
    $from = now()->startOfMonth()->format('Y-m-d');
    $to   = now()->format('Y-m-d');
    $action = route('admin.reports.export');
@endphp

<div style="background:#fff;border-radius:8px;padding:20px;max-width:560px;">
    <form method="GET" action="{{ $action }}" style="display:flex;flex-direction:column;gap:16px;">
        <label style="display:flex;flex-direction:column;gap:6px;">
            <span style="font-weight:600;">Тип отчёта</span>
            <select name="type" required style="padding:8px;border:1px solid #ccc;border-radius:6px;">
                <option value="bookings">Бронирования</option>
                <option value="revenue">Выручка по дням</option>
                <option value="occupancy">Загрузка столов</option>
            </select>
        </label>

        <div style="display:flex;gap:12px;">
            <label style="display:flex;flex-direction:column;gap:6px;flex:1;">
                <span style="font-weight:600;">Период с</span>
                <input type="date" name="date_from" value="{{ $from }}" required
                       style="padding:8px;border:1px solid #ccc;border-radius:6px;">
            </label>
            <label style="display:flex;flex-direction:column;gap:6px;flex:1;">
                <span style="font-weight:600;">по</span>
                <input type="date" name="date_to" value="{{ $to }}" required
                       style="padding:8px;border:1px solid #ccc;border-radius:6px;">
            </label>
        </div>

        <label style="display:flex;flex-direction:column;gap:6px;">
            <span style="font-weight:600;">Формат</span>
            <select name="format" required style="padding:8px;border:1px solid #ccc;border-radius:6px;">
                <option value="xlsx">Excel (XLSX)</option>
                <option value="csv">CSV</option>
            </select>
        </label>

        <button type="submit"
                style="padding:10px 18px;background:#7c3aed;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;width:fit-content;">
            Скачать отчёт
        </button>
    </form>
</div>
