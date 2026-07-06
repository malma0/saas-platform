{{-- Сетка занятости столов для админа: столы × время, бронь кликом по ячейке. --}}
<div id="tto-root"
     data-grid-url="{{ $gridUrl }}"
     data-book-url="{{ $bookUrl }}"
     data-action-base="{{ $actionBase }}"
     data-csrf="{{ $csrf }}">

  <style>
    #tto-root{--tto-line:#e5e7eb;--tto-bg:#fff;--tto-muted:#6b7280;--tto-accent:#7c3aed;}
    #tto-root *{box-sizing:border-box;}
    .tto-bar{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:14px;}
    .tto-bar select,.tto-bar input[type=date]{padding:7px 10px;border:1px solid var(--tto-line);border-radius:8px;font-size:14px;background:#fff;color:#111;}
    .tto-nav{display:inline-flex;align-items:center;gap:4px;}
    .tto-btn{padding:7px 12px;border:1px solid var(--tto-line);border-radius:8px;background:#fff;color:#111;font-size:14px;font-weight:600;cursor:pointer;line-height:1;}
    .tto-btn:hover{background:#f3f4f6;}
    .tto-btn.tto-primary{background:var(--tto-accent);border-color:var(--tto-accent);color:#fff;}
    .tto-btn.tto-primary:hover{filter:brightness(1.05);}
    .tto-legend{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:12px;font-size:12.5px;color:var(--tto-muted);}
    .tto-legend span{display:inline-flex;align-items:center;gap:6px;}
    .tto-dot{width:12px;height:12px;border-radius:3px;display:inline-block;}

    .tto-wrap{border:1px solid var(--tto-line);border-radius:10px;overflow:auto;background:#fff;}
    .tto-grid{min-width:max-content;}
    .tto-row{display:flex;border-bottom:1px solid var(--tto-line);}
    .tto-row:last-child{border-bottom:none;}
    .tto-row.tto-head{position:sticky;top:0;z-index:3;background:#faf9fc;}
    .tto-name{flex:none;width:120px;padding:10px 12px;font-size:13.5px;font-weight:700;color:#111;border-right:1px solid var(--tto-line);position:sticky;left:0;background:inherit;z-index:2;display:flex;align-items:center;}
    .tto-head .tto-name{background:#faf9fc;}
    .tto-row:not(.tto-head) .tto-name{background:#fff;}
    .tto-track{position:relative;height:46px;flex:none;}
    .tto-head .tto-track{height:34px;display:flex;}
    .tto-hour{flex:none;border-right:1px dashed var(--tto-line);font-size:11.5px;color:var(--tto-muted);display:flex;align-items:center;justify-content:flex-start;padding-left:5px;}
    .tto-cellline{position:absolute;top:0;bottom:0;border-right:1px dashed #eef0f3;pointer-events:none;}
    .tto-track.tto-open{cursor:copy;}
    .tto-track.tto-open:hover{background:rgba(124,58,237,.03);}
    .tto-ev{position:absolute;top:5px;bottom:5px;border-radius:6px;padding:4px 7px;font-size:11.5px;font-weight:600;color:#fff;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;box-shadow:0 1px 2px rgba(0,0,0,.12);cursor:pointer;}
    .tto-ev:hover{filter:brightness(1.06);box-shadow:0 2px 8px rgba(0,0,0,.25);}
    .tto-ev small{display:block;font-weight:500;opacity:.92;font-size:10.5px;}
    .tto-ghost{position:absolute;top:5px;bottom:5px;border:1.5px dashed var(--tto-accent);border-radius:6px;background:rgba(124,58,237,.06);display:none;align-items:center;justify-content:center;color:var(--tto-accent);font-size:11px;font-weight:700;pointer-events:none;}
    .tto-empty{padding:30px;text-align:center;color:var(--tto-muted);font-size:14px;}

    /* Переключатель Сетка / Список */
    .tto-viewtoggle{display:inline-flex;border:1px solid var(--tto-line);border-radius:9px;overflow:hidden;}
    .tto-vt{padding:7px 16px;border:none;background:#fff;color:#374151;font-size:14px;font-weight:700;cursor:pointer;line-height:1;}
    .tto-vt.tto-vt-on{background:var(--tto-accent);color:#fff;}

    /* Список броней */
    .tto-list{border:1px solid var(--tto-line);border-radius:10px;overflow:auto;background:#fff;}
    .tto-list table{width:100%;border-collapse:collapse;min-width:680px;}
    .tto-list th,.tto-list td{padding:11px 14px;text-align:left;font-size:13.5px;border-bottom:1px solid var(--tto-line);white-space:nowrap;}
    .tto-list th{background:#faf9fc;font-weight:700;color:#374151;font-size:12px;text-transform:uppercase;letter-spacing:.3px;position:sticky;top:0;}
    .tto-list tr:last-child td{border-bottom:none;}
    .tto-list tbody tr:hover td{background:#faf9fc;}
    .tto-lstatus{display:inline-block;font-size:11.5px;font-weight:700;border-radius:6px;padding:3px 9px;color:#fff;}
    .tto-lpaid{font-size:11px;color:#10b981;font-weight:700;margin-left:6px;}
    .tto-lact{padding:6px 13px;border:1px solid var(--tto-line);border-radius:8px;background:#fff;font-weight:700;font-size:13px;cursor:pointer;color:#111;}
    .tto-lact:hover{background:var(--tto-accent);color:#fff;border-color:var(--tto-accent);}

    /* Модалка брони */
    .tto-modal-bg{position:fixed;inset:0;z-index:10050;background:rgba(17,17,17,.45);display:none;align-items:center;justify-content:center;padding:20px;}
    .tto-modal-bg.tto-on{display:flex;}
    .tto-modal{background:#fff;border-radius:14px;width:420px;max-width:100%;padding:22px 22px 20px;box-shadow:0 24px 60px rgba(0,0,0,.3);}
    .tto-modal h3{font-size:18px;font-weight:800;margin:0 0 4px;color:#111;}
    .tto-modal .tto-sub{font-size:13px;color:var(--tto-muted);margin-bottom:16px;}
    .tto-field{margin-bottom:12px;}
    .tto-field label{display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:5px;}
    .tto-field input{width:100%;padding:10px 12px;border:1px solid var(--tto-line);border-radius:9px;font-size:14px;color:#111;}
    .tto-stepper{display:flex;align-items:center;gap:8px;}
    .tto-stepper button{width:34px;height:34px;border:1px solid var(--tto-line);border-radius:8px;background:#fff;font-size:18px;cursor:pointer;line-height:1;}
    .tto-stepper .tto-val{min-width:88px;text-align:center;font-weight:700;font-size:14px;}
    .tto-total{display:flex;justify-content:space-between;align-items:center;margin:14px 0;padding-top:12px;border-top:1px solid var(--tto-line);}
    .tto-total .k{color:var(--tto-muted);font-size:13px;}
    .tto-total .v{font-size:20px;font-weight:800;color:var(--tto-accent);}
    .tto-err{display:none;color:#dc2626;font-size:12.5px;font-weight:600;margin-bottom:10px;}
    .tto-modal .tto-actions{display:flex;gap:10px;}
    .tto-modal .tto-actions .tto-btn{flex:1;justify-content:center;padding:11px;text-align:center;}
  </style>

  <div class="tto-bar">
    <select id="tto-branch"></select>
    <div class="tto-nav">
      <button class="tto-btn" id="tto-prev" title="Предыдущий день">‹</button>
      <input type="date" id="tto-date">
      <button class="tto-btn" id="tto-next" title="Следующий день">›</button>
    </div>
    <button class="tto-btn" id="tto-today">Сегодня</button>
    <button class="tto-btn" id="tto-refresh" title="Обновить">⟳</button>
    <div class="tto-viewtoggle" style="margin-left:auto;">
      <button class="tto-vt tto-vt-on" id="tto-view-grid">Сетка</button>
      <button class="tto-vt" id="tto-view-list">Список</button>
    </div>
  </div>

  <div class="tto-legend">
    <span><i class="tto-dot" style="background:#10b981"></i> Подтверждена</span>
    <span><i class="tto-dot" style="background:#f59e0b"></i> Ожидает</span>
    <span><i class="tto-dot" style="background:#3b82f6"></i> Завершена</span>
    <span><i class="tto-dot" style="background:#ef4444"></i> Не явился</span>
    <span><i class="tto-dot" style="background:rgba(124,58,237,.1);border:1.5px dashed #7c3aed"></i> Свободно — клик, чтобы забронировать</span>
  </div>

  <div class="tto-wrap" id="tto-gridwrap"><div class="tto-grid" id="tto-grid"></div></div>
  <div class="tto-list" id="tto-list" style="display:none"></div>

  {{-- Модалка бронирования --}}
  <div class="tto-modal-bg" id="tto-modal-bg">
    <div class="tto-modal">
      <h3>Бронирование стола</h3>
      <div class="tto-sub" id="tto-m-sub">Стол · дата</div>
      <div class="tto-field">
        <label>Начало</label>
        <div class="tto-stepper">
          <button type="button" data-step="s-1">−</button>
          <span class="tto-val" id="tto-m-start">18:00</span>
          <button type="button" data-step="s1">+</button>
        </div>
      </div>
      <div class="tto-field">
        <label>Длительность</label>
        <div class="tto-stepper">
          <button type="button" data-step="d-1">−</button>
          <span class="tto-val" id="tto-m-dur">1 ч</span>
          <button type="button" data-step="d1">+</button>
        </div>
      </div>
      <div class="tto-field">
        <label>Имя клиента</label>
        <input type="text" id="tto-m-name" placeholder="Как зовут клиента" autocomplete="off">
      </div>
      <div class="tto-field">
        <label>Телефон</label>
        <input type="tel" id="tto-m-phone" placeholder="+7 ___ ___-__-__" autocomplete="off">
      </div>
      <div class="tto-err" id="tto-m-err"></div>
      <div class="tto-total"><span class="k">Итого</span><span class="v" id="tto-m-price">— ₽</span></div>
      <div class="tto-actions">
        <button class="tto-btn" id="tto-m-cancel">Отмена</button>
        <button class="tto-btn tto-primary" id="tto-m-ok">Забронировать</button>
      </div>
    </div>
  </div>

  {{-- Модалка действий над существующей бронью --}}
  <style>
    #tto-root .tto-a-meta{display:flex;align-items:center;gap:10px;margin:-6px 0 14px;}
    #tto-root .tto-badge{font-size:11.5px;font-weight:700;border-radius:6px;padding:3px 9px;color:#fff;}
    #tto-root .tto-a-amount{font-size:14px;font-weight:800;color:#111;}
    #tto-root .tto-a-btns{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:6px;}
    #tto-root .tto-abtn{flex:1 1 calc(50% - 4px);min-width:120px;justify-content:center;padding:11px;text-align:center;border:1px solid var(--tto-line);border-radius:9px;background:#fff;color:#111;font-size:13.5px;font-weight:700;cursor:pointer;}
    #tto-root .tto-abtn:hover{background:#f3f4f6;}
    #tto-root .tto-abtn.green{background:#10b981;border-color:#10b981;color:#fff;}
    #tto-root .tto-abtn.red{background:#ef4444;border-color:#ef4444;color:#fff;}
    #tto-root .tto-resch{border-top:1px solid var(--tto-line);margin-top:8px;padding-top:12px;}
  </style>
  <div class="tto-modal-bg" id="tto-act-bg">
    <div class="tto-modal">
      <h3 id="tto-a-name">Клиент</h3>
      <div class="tto-sub" id="tto-a-sub">Стол · время</div>
      <div class="tto-a-meta">
        <span class="tto-badge" id="tto-a-status">—</span>
        <span class="tto-a-amount" id="tto-a-amount"></span>
      </div>
      <div class="tto-err" id="tto-a-err"></div>
      <div class="tto-a-btns" id="tto-a-btns"></div>

      <div class="tto-resch" id="tto-resch" style="display:none">
        <div class="tto-field">
          <label>Новое начало</label>
          <div class="tto-stepper">
            <button type="button" data-rstep="s-1">−</button>
            <span class="tto-val" id="tto-r-start">18:00</span>
            <button type="button" data-rstep="s1">+</button>
          </div>
        </div>
        <div class="tto-field">
          <label>Длительность</label>
          <div class="tto-stepper">
            <button type="button" data-rstep="d-1">−</button>
            <span class="tto-val" id="tto-r-dur">1 ч</span>
            <button type="button" data-rstep="d1">+</button>
          </div>
        </div>
        <button class="tto-btn tto-primary" id="tto-r-ok" style="width:100%;justify-content:center;padding:11px;">Сохранить перенос</button>
      </div>

      <div class="tto-actions" style="margin-top:14px;">
        <button class="tto-btn" id="tto-a-close" style="flex:1;justify-content:center;padding:11px;">Закрыть</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var root = document.getElementById('tto-root');
  if (!root || root.dataset.inited === '1') {
    // повторный заход (SPA) — переинициализируем, если узлы заменились
  }

  var HOUR_PX = 92;          // ширина часа в пикселях
  var STATUS_COLORS = { confirmed:'#10b981', pending:'#f59e0b', completed:'#3b82f6', no_show:'#ef4444', cancelled:'#9ca3af' };

  var state = {
    gridUrl: root.dataset.gridUrl,
    bookUrl: root.dataset.bookUrl,
    actionBase: root.dataset.actionBase,
    csrf: root.dataset.csrf,
    branchId: '', date: '', openMin: 540, closeMin: 1320,
    tables: [], events: [], serviceId: null, rate: 0,
    view: 'grid', // 'grid' | 'list'
    sel: null,    // выбранный пустой слот {tableId, tableName, startMin, durMin}
    act: null     // выбранная бронь для действий
  };

  function $(id){ return document.getElementById(id); }
  function pad(n){ return (n<10?'0':'')+n; }
  function toHHMM(min){ return pad(Math.floor(min/60))+':'+pad(min%60); }
  function parseHHMM(s){ var p=(s||'00:00').split(':'); return (+p[0])*60+(+p[1]); }

  function todayISO(){
    var d = new Date(); return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
  }
  function shiftDate(iso, days){
    var p = iso.split('-'); var d = new Date(+p[0], +p[1]-1, +p[2]); d.setDate(d.getDate()+days);
    return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
  }

  function load(){
    var url = state.gridUrl + '?branch_id=' + encodeURIComponent(state.branchId||'') + '&date=' + encodeURIComponent(state.date||'');
    fetch(url, { headers:{ 'Accept':'application/json' }, credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d.ok) { return; }
        state.openMin  = parseHHMM(d.open);
        state.closeMin = parseHHMM(d.close);
        state.tables   = d.tables || [];
        state.events   = d.events || [];
        state.serviceId= d.service_id;
        state.rate     = d.rate_per_hour || 0;
        state.branchId = d.branch_id;
        state.date     = d.date;
        // селект филиалов
        if (d.branches) {
          var sel = $('tto-branch');
          if (sel.options.length !== d.branches.length || sel.dataset.k !== String(d.branches.length)) {
            sel.innerHTML = '';
            d.branches.forEach(function(b){
              var o=document.createElement('option'); o.value=b.id; o.textContent=b.name; sel.appendChild(o);
            });
            sel.dataset.k = String(d.branches.length);
          }
          sel.value = d.branch_id;
        }
        $('tto-date').value = state.date;
        render();
        renderList();
        applyView();
      })
      .catch(function(){ /* тихо */ });
  }

  function render(){
    var grid = $('tto-grid');
    if (!state.tables.length) {
      grid.innerHTML = '<div class="tto-empty">В этом филиале нет столов.</div>';
      return;
    }
    var openH = Math.floor(state.openMin/60), closeH = Math.ceil(state.closeMin/60);
    var hours = closeH - openH;
    var trackW = hours * HOUR_PX;
    var spanMin = (closeH - openH) * 60;
    var baseMin = openH * 60;

    function leftPct(min){ return ((min - baseMin) / spanMin) * 100; }

    var html = '';
    // шапка с часами
    html += '<div class="tto-row tto-head"><div class="tto-name">Стол</div><div class="tto-track" style="width:'+trackW+'px">';
    for (var h=openH; h<closeH; h++){
      html += '<div class="tto-hour" style="width:'+HOUR_PX+'px">'+pad(h)+':00</div>';
    }
    html += '</div></div>';

    // строки столов
    state.tables.forEach(function(t){
      html += '<div class="tto-row"><div class="tto-name">'+esc(t.name)+'</div>';
      html += '<div class="tto-track tto-open" data-table="'+t.id+'" data-name="'+esc(t.name)+'" style="width:'+trackW+'px">';
      // вертикальные линии часов
      for (var i=1;i<hours;i++){ html += '<div class="tto-cellline" style="left:'+(i*HOUR_PX)+'px"></div>'; }
      // ghost
      html += '<div class="tto-ghost"></div>';
      // брони на этом столе
      state.events.filter(function(e){ return e.resource_id === t.id; }).forEach(function(e){
        var s = parseHHMM(e.start), en = parseHHMM(e.end);
        var l = leftPct(s), w = leftPct(en) - l;
        var c = STATUS_COLORS[e.status] || '#6b7280';
        html += '<div class="tto-ev" data-pid="'+esc(e.public_id)+'" style="left:'+l+'%;width:'+w+'%;background:'+c+'" title="'+esc(e.title)+' · '+e.start+'–'+e.end+' · нажмите для действий">'
              + esc(e.title) + '<small>'+e.start+'–'+e.end+'</small></div>';
      });
      html += '</div></div>';
    });
    grid.innerHTML = html;
  }

  // ── Список броней (та же дата, что и сетка) ──
  function tableName(rid){ return ((state.tables.filter(function(t){ return t.id === rid; })[0]) || {}).name || 'Стол'; }

  function renderList(){
    var box = $('tto-list');
    var evs = (state.events || []).slice().sort(function(a,b){ return parseHHMM(a.start) - parseHHMM(b.start); });
    if (!evs.length) {
      box.innerHTML = '<div class="tto-empty">На этот день броней нет. Откройте «Сетку» и кликните по свободному времени, чтобы забронировать.</div>';
      return;
    }
    var rows = evs.map(function(e){
      var c = STATUS_COLORS[e.status] || '#6b7280';
      return '<tr>'
        + '<td><b>'+e.start+'–'+e.end+'</b></td>'
        + '<td>'+esc(tableName(e.resource_id))+'</td>'
        + '<td>'+esc(e.title)+'</td>'
        + '<td>'+(e.phone ? esc(e.phone) : '<span style="color:#9ca3af">—</span>')+'</td>'
        + '<td><span class="tto-lstatus" style="background:'+c+'">'+esc(e.status_label || e.status)+'</span>'
          + (e.is_paid ? '<span class="tto-lpaid">оплачено</span>' : '')+'</td>'
        + '<td>'+(e.amount != null ? e.amount.toLocaleString('ru-RU')+' ₽' : '')+'</td>'
        + '<td><button class="tto-lact" data-pid="'+esc(e.public_id)+'">Действия ⋯</button></td>'
        + '</tr>';
    }).join('');
    box.innerHTML = '<table><thead><tr>'
      + '<th>Время</th><th>Стол</th><th>Клиент</th><th>Телефон</th><th>Статус</th><th>Сумма</th><th></th>'
      + '</tr></thead><tbody>'+rows+'</tbody></table>';
  }

  function applyView(){
    var grid = state.view !== 'list';
    $('tto-gridwrap').style.display = grid ? '' : 'none';
    $('tto-list').style.display     = grid ? 'none' : '';
    $('tto-view-grid').classList.toggle('tto-vt-on', grid);
    $('tto-view-list').classList.toggle('tto-vt-on', !grid);
  }
  function setView(v){ state.view = v; applyView(); }

  function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

  // ── Клик по свободной дорожке → модалка ──
  function trackMinsFromEvent(track, clientX){
    var r = track.getBoundingClientRect();
    var frac = Math.max(0, Math.min(1, (clientX - r.left) / r.width));
    var openH = Math.floor(state.openMin/60), closeH = Math.ceil(state.closeMin/60);
    var spanMin = (closeH-openH)*60, baseMin = openH*60;
    var mins = baseMin + frac*spanMin;
    mins = Math.floor(mins/30)*30;            // шаг 30 мин
    if (mins < state.openMin) mins = state.openMin;
    if (mins > state.closeMin-30) mins = state.closeMin-30;
    return mins;
  }

  document.addEventListener('mousemove', function(e){
    var track = e.target.closest ? e.target.closest('.tto-track.tto-open') : null;
    document.querySelectorAll('.tto-ghost').forEach(function(g){ g.style.display='none'; });
    if (!track || e.target.closest('.tto-ev')) return;
    var ghost = track.querySelector('.tto-ghost'); if (!ghost) return;
    var mins = trackMinsFromEvent(track, e.clientX);
    var openH = Math.floor(state.openMin/60), closeH = Math.ceil(state.closeMin/60);
    var spanMin = (closeH-openH)*60, baseMin = openH*60;
    ghost.style.left = (((mins-baseMin)/spanMin)*100) + '%';
    ghost.style.width = ((60/spanMin)*100) + '%';
    ghost.style.display = 'flex';
    ghost.textContent = toHHMM(mins);
  });

  document.addEventListener('click', function(e){
    // Клик по существующей брони (сетка) или кнопке «Действия» (список) → меню действий
    var ev = e.target.closest ? e.target.closest('.tto-ev') : null;
    if (ev && ev.dataset.pid) { openActions(ev.dataset.pid); return; }
    var lact = e.target.closest ? e.target.closest('.tto-lact') : null;
    if (lact && lact.dataset.pid) { openActions(lact.dataset.pid); return; }
    // Клик по свободному месту → бронирование
    var track = e.target.closest ? e.target.closest('.tto-track.tto-open') : null;
    if (!track) return;
    var mins = trackMinsFromEvent(track, e.clientX);
    openModal(+track.dataset.table, track.dataset.name, mins);
  });

  // ── Модалка ──
  function openModal(tableId, tableName, startMin){
    state.sel = { tableId: tableId, tableName: tableName, startMin: startMin, durMin: 60 };
    $('tto-m-sub').textContent = tableName + ' · ' + ruDate(state.date);
    $('tto-m-name').value = '';
    $('tto-m-phone').value = '';
    showErr('');
    refreshModal();
    $('tto-modal-bg').classList.add('tto-on');
  }
  function closeModal(){ $('tto-modal-bg').classList.remove('tto-on'); }
  function refreshModal(){
    var s = state.sel; if (!s) return;
    // не выходим за закрытие
    if (s.startMin + s.durMin > state.closeMin) s.durMin = state.closeMin - s.startMin;
    if (s.durMin < 30) s.durMin = 30;
    $('tto-m-start').textContent = toHHMM(s.startMin);
    $('tto-m-dur').textContent = (s.durMin % 60 === 0) ? (s.durMin/60 + ' ч') : (s.durMin + ' мин');
    var price = Math.round(state.rate * (s.durMin/60));
    $('tto-m-price').textContent = price.toLocaleString('ru-RU') + ' ₽';
  }
  function showErr(m){ var el=$('tto-m-err'); el.textContent=m||''; el.style.display=m?'block':'none'; }
  function ruDate(iso){
    var M=['янв','фев','мар','апр','мая','июн','июл','авг','сен','окт','ноя','дек'];
    var p=iso.split('-'); return (+p[2])+' '+M[(+p[1])-1]+' '+p[0];
  }

  function book(){
    var s = state.sel; if (!s) return;
    var name = $('tto-m-name').value.trim();
    var phone = $('tto-m-phone').value.trim();
    showErr('');
    if (name.length < 2) { showErr('Укажите имя клиента.'); return; }
    if (phone.length < 5) { showErr('Укажите телефон.'); return; }
    var btn = $('tto-m-ok'); btn.disabled = true; var lbl = btn.textContent; btn.textContent = 'Бронируем…';
    fetch(state.bookUrl, {
      method:'POST', credentials:'same-origin',
      headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': state.csrf },
      body: JSON.stringify({
        name: name, phone: phone,
        branch_id: state.branchId, resource_id: s.tableId,
        date: state.date, start: toHHMM(s.startMin), end: toHHMM(s.startMin + s.durMin)
      })
    }).then(function(r){ return r.json().then(function(d){ return { ok:r.ok, d:d }; }); })
      .then(function(res){
        if (!res.ok || !res.d.ok) { showErr(res.d.message || 'Не удалось забронировать.'); return; }
        closeModal();
        load(); // обновляем сетку — бронь появится
      })
      .catch(function(){ showErr('Сеть недоступна, попробуйте ещё раз.'); })
      .finally(function(){ btn.disabled = false; btn.textContent = lbl; });
  }

  // ── Действия над существующей бронью (как «3 точки» в списке) ──
  function openActions(pid){
    var e = (state.events || []).filter(function(x){ return x.public_id === pid; })[0];
    if (!e) return;
    state.act = { pid: pid, startMin: parseHHMM(e.start), durMin: parseHHMM(e.end) - parseHHMM(e.start) };
    var tableName = ((state.tables.filter(function(t){ return t.id === e.resource_id; })[0]) || {}).name || 'Стол';
    $('tto-a-name').textContent = e.title + (e.phone ? ' · ' + e.phone : '');
    $('tto-a-sub').textContent = tableName + ' · ' + e.start + '–' + e.end + ' · ' + ruDate(state.date);
    var badge = $('tto-a-status');
    badge.textContent = e.status_label || e.status;
    badge.style.background = STATUS_COLORS[e.status] || '#6b7280';
    $('tto-a-amount').textContent = (e.amount != null ? e.amount.toLocaleString('ru-RU') + ' ₽' : '') + (e.is_paid ? ' · оплачено' : '');
    actErr('');
    $('tto-resch').style.display = 'none';
    renderActionButtons(e);
    refreshResch();
    $('tto-act-bg').classList.add('tto-on');
  }

  function renderActionButtons(e){
    var box = $('tto-a-btns'); box.innerHTML = '';
    var btns = [];
    if (e.status === 'pending') btns.push(['Подтвердить','confirm','green']);
    if (e.status !== 'cancelled' && !e.is_paid) btns.push(['Оплата','paid','green']);
    if (e.status === 'confirmed' || e.status === 'completed') {
      btns.push(['Пришёл','present','green']);
      btns.push(['Не пришёл','no-show','']);
    }
    if (e.status !== 'cancelled' && e.status !== 'completed') {
      btns.push(['Перенести','__resch','']);
      btns.push(['Отменить','cancel','red']);
    }
    btns.forEach(function(b){
      var el = document.createElement('button');
      el.className = 'tto-abtn' + (b[2] ? ' ' + b[2] : '');
      el.textContent = b[0];
      el.addEventListener('click', function(){
        if (b[1] === '__resch') {
          var r = $('tto-resch'); r.style.display = (r.style.display === 'none' ? 'block' : 'none');
          return;
        }
        postAction(b[1], {});
      });
      box.appendChild(el);
    });
  }

  function postAction(action, body){
    var url = state.actionBase + '/' + encodeURIComponent(state.act.pid) + '/' + action;
    return fetch(url, {
      method:'POST', credentials:'same-origin',
      headers:{ 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': state.csrf },
      body: JSON.stringify(body || {})
    }).then(function(r){ return r.json().then(function(d){ return { ok:r.ok, d:d }; }); })
      .then(function(res){
        if (!res.ok || !res.d.ok) { actErr(res.d.message || 'Не удалось выполнить действие.'); return; }
        closeActions(); load();
      })
      .catch(function(){ actErr('Сеть недоступна, попробуйте ещё раз.'); });
  }

  function refreshResch(){
    var a = state.act; if (!a) return;
    if (a.startMin + a.durMin > state.closeMin) a.durMin = state.closeMin - a.startMin;
    if (a.durMin < 30) a.durMin = 30;
    $('tto-r-start').textContent = toHHMM(a.startMin);
    $('tto-r-dur').textContent = (a.durMin % 60 === 0) ? (a.durMin/60 + ' ч') : (a.durMin + ' мин');
  }
  function actErr(m){ var el = $('tto-a-err'); el.textContent = m || ''; el.style.display = m ? 'block' : 'none'; }
  function closeActions(){ $('tto-act-bg').classList.remove('tto-on'); }

  // ── События управления ──
  function wire(){
    $('tto-branch').addEventListener('change', function(){ state.branchId = this.value; load(); });
    $('tto-date').addEventListener('change', function(){ state.date = this.value; load(); });
    $('tto-prev').addEventListener('click', function(){ state.date = shiftDate(state.date||todayISO(), -1); load(); });
    $('tto-next').addEventListener('click', function(){ state.date = shiftDate(state.date||todayISO(), 1); load(); });
    $('tto-today').addEventListener('click', function(){ state.date = todayISO(); load(); });
    $('tto-refresh').addEventListener('click', load);
    $('tto-view-grid').addEventListener('click', function(){ setView('grid'); });
    $('tto-view-list').addEventListener('click', function(){ setView('list'); });

    $('tto-m-cancel').addEventListener('click', closeModal);
    $('tto-m-ok').addEventListener('click', book);
    $('tto-modal-bg').addEventListener('click', function(e){ if (e.target === this) closeModal(); });
    document.querySelectorAll('[data-step]').forEach(function(b){
      b.addEventListener('click', function(){
        var s = state.sel; if (!s) return;
        var k = b.dataset.step;
        if (k==='s-1') s.startMin = Math.max(state.openMin, s.startMin-30);
        if (k==='s1')  s.startMin = Math.min(state.closeMin-30, s.startMin+30);
        if (k==='d-1') s.durMin = Math.max(30, s.durMin-30);
        if (k==='d1')  s.durMin = Math.min(240, s.durMin+30);
        refreshModal();
      });
    });
    // Модалка действий над бронью
    $('tto-a-close').addEventListener('click', closeActions);
    $('tto-act-bg').addEventListener('click', function(e){ if (e.target === this) closeActions(); });
    $('tto-r-ok').addEventListener('click', function(){
      var a = state.act; if (!a) return;
      postAction('reschedule', { date: state.date, start: toHHMM(a.startMin), end: toHHMM(a.startMin + a.durMin) });
    });
    document.querySelectorAll('[data-rstep]').forEach(function(b){
      b.addEventListener('click', function(){
        var a = state.act; if (!a) return;
        var k = b.dataset.rstep;
        if (k==='s-1') a.startMin = Math.max(state.openMin, a.startMin-30);
        if (k==='s1')  a.startMin = Math.min(state.closeMin-30, a.startMin+30);
        if (k==='d-1') a.durMin = Math.max(30, a.durMin-30);
        if (k==='d1')  a.durMin = Math.min(240, a.durMin+30);
        refreshResch();
      });
    });

    document.addEventListener('keydown', function(e){ if (e.key==='Escape') { closeModal(); closeActions(); } });
  }

  function init(){
    if (root.dataset.inited === '1') { return; }
    root.dataset.inited = '1';
    state.date = todayISO();
    wire();
    load();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  setTimeout(init, 300); // на случай SPA-перехода внутри MoonShine
})();
</script>
