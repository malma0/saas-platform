{{-- Календарный вид броней (Фаза 12). FullCalendar 6 через CDN. --}}
<div>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select id="tt-cal-branch" class="form-select" style="min-width:200px;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;">
            <option value="">Все филиалы</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>

        <select id="tt-cal-resource" class="form-select" style="min-width:200px;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;">
            <option value="">Все ресурсы</option>
            @foreach ($resources as $resource)
                <option value="{{ $resource->id }}" data-branch="{{ $resource->branch_id }}">{{ $resource->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
        <span class="inline-flex items-center gap-1"><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:#10b981"></span> Подтверждено</span>
        <span class="inline-flex items-center gap-1"><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:#f59e0b"></span> Ожидает</span>
        <span class="inline-flex items-center gap-1"><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:#3b82f6"></span> Завершено</span>
        <span class="inline-flex items-center gap-1"><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:#ef4444"></span> Не явился</span>
        <span class="inline-flex items-center gap-1"><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:#9ca3af"></span> Отменено</span>
        <span class="inline-flex items-center gap-1"><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:#8b5cf6"></span> Регулярное занятие</span>
    </div>

    <div style="background:#fff;border-radius:8px;padding:12px;">
        <div id="tt-booking-calendar"></div>
    </div>
</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css' rel='stylesheet'>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script>
(function () {
    function init() {
        var el = document.getElementById('tt-booking-calendar');
        if (!el || typeof FullCalendar === 'undefined') { return; }
        if (el.dataset.rendered) { return; }
        el.dataset.rendered = '1';

        var branchSel   = document.getElementById('tt-cal-branch');
        var resourceSel = document.getElementById('tt-cal-resource');

        var calendar = new FullCalendar.Calendar(el, {
            initialView: 'timeGridWeek',
            locale: 'ru',
            firstDay: 1,
            nowIndicator: true,
            slotMinTime: '07:00:00',
            slotMaxTime: '24:00:00',
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: { today: 'Сегодня', month: 'Месяц', week: 'Неделя', day: 'День' },
            events: {
                url: @json(route('admin.booking-calendar.events')),
                method: 'GET',
                extraParams: function () {
                    return {
                        branch_id: branchSel ? branchSel.value : '',
                        resource_id: resourceSel ? resourceSel.value : ''
                    };
                }
            }
        });
        calendar.render();

        function refetch() { calendar.refetchEvents(); }

        if (branchSel) {
            branchSel.addEventListener('change', function () {
                // Скрываем ресурсы чужих филиалов
                if (resourceSel) {
                    var bid = branchSel.value;
                    Array.prototype.forEach.call(resourceSel.options, function (opt) {
                        if (!opt.value) { return; }
                        opt.hidden = !!bid && opt.dataset.branch !== bid;
                    });
                    if (bid && resourceSel.selectedOptions[0] && resourceSel.selectedOptions[0].hidden) {
                        resourceSel.value = '';
                    }
                }
                refetch();
            });
        }
        if (resourceSel) { resourceSel.addEventListener('change', refetch); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    // На случай SPA-перехода внутри Moonshine
    setTimeout(init, 300);
})();
</script>
