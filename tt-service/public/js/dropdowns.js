/* ===== Универсальные выпадающие меню + календарь — Теннис Клуб НСК ===== */
/* Подключи:  <script src="dropdowns.js"></script>
   Список:    <div class="field" data-dd data-dd-options="A|B|C"> … <span class="v" data-dd-val>A</span> … </div>
   Календарь: <div class="field" data-dd data-dd-type="calendar"> … <span class="v" data-dd-val>24 мая, сб</span> … </div>
   Колбэк:    data-dd-callback="имяГлобальнойФункции"  (получает (значение, триггер))            */
(function(){
  'use strict';

  const MON_GEN = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
  const MON_NOM = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
  const DOW     = ['вс','пн','вт','ср','чт','пт','сб'];
  const BASE_YEAR = 2026;

  // ── styles ───────────────────────────────────────
  const css = `
  .dd-menu{
    position:fixed;background:#181a15;border:1px solid rgba(255,255,255,.11);
    border-radius:12px;padding:6px;z-index:99999;min-width:160px;
    box-shadow:0 16px 44px rgba(0,0,0,.62);max-height:300px;overflow-y:auto;
    animation:ddIn .13s cubic-bezier(.2,.7,.3,1);
    font-family:'Manrope',system-ui,sans-serif;
  }
  @keyframes ddIn{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:none}}
  .dd-menu::-webkit-scrollbar{width:9px;}
  .dd-menu::-webkit-scrollbar-track{background:transparent;}
  .dd-menu::-webkit-scrollbar-thumb{background:rgba(255,255,255,.16);border-radius:5px;border:2px solid #181a15;}
  .dd-item{
    display:flex;align-items:center;justify-content:space-between;gap:16px;
    padding:10px 13px;border-radius:8px;cursor:pointer;font-size:14.5px;font-weight:600;
    color:#dcdfd6;white-space:nowrap;transition:background .1s,color .1s;
  }
  .dd-item:hover{background:rgba(255,255,255,.06);color:#fff;}
  .dd-item.sel{color:#c6e21a;}
  .dd-item .dd-check{display:none;color:#c6e21a;flex:none;}
  .dd-item.sel .dd-check{display:flex;}
  [data-dd]{position:relative;}
  [data-dd] .chev{transition:transform .2s ease;}
  [data-dd].dd-open .chev{transform:rotate(180deg);}
  [data-dd].dd-open{border-color:rgba(198,226,26,.5)!important;}
  /* calendar */
  .dd-cal{padding:12px;overflow:visible;min-width:262px!important;}
  .dc-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
  .dc-title{font-size:14.5px;font-weight:700;color:#f0f1ec;}
  .dc-nav{width:30px;height:30px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);color:#cfd1ca;font-size:16px;cursor:pointer;display:grid;place-items:center;line-height:1;}
  .dc-nav:hover{background:rgba(255,255,255,.1);color:#fff;}
  .dc-week{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:4px;}
  .dc-week span{text-align:center;font-size:11px;font-weight:600;color:#8c8f86;padding:4px 0;}
  .dc-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;}
  .dc-day{height:34px;display:grid;place-items:center;font-size:13.5px;font-weight:600;color:#dcdfd6;border-radius:8px;cursor:pointer;transition:background .1s;}
  .dc-day:not(.empty):hover{background:rgba(255,255,255,.08);}
  .dc-day.empty{cursor:default;}
  .dc-day.we{color:#b06a5e;}
  .dc-day.today{box-shadow:inset 0 0 0 1px rgba(198,226,26,.45);}
  .dc-day.sel{background:#c6e21a!important;color:#13160a!important;}
  `;
  const st = document.createElement('style');
  st.textContent = css;
  document.head.appendChild(st);

  const CHECK = '<span class="dd-check"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-11"/></svg></span>';

  let openMenu = null, openTrigger = null;

  function valEl(t){ return t.querySelector('[data-dd-val]') || t.querySelector('.v') || t.querySelector('.fv'); }
  function fireCb(trigger, value){
    const cb = trigger.getAttribute('data-dd-callback');
    if(cb && typeof window[cb] === 'function') window[cb](value, trigger);
  }
  function closeAll(){
    if(openMenu){ openMenu.remove(); openMenu = null; }
    if(openTrigger){ openTrigger.classList.remove('dd-open'); openTrigger = null; }
  }
  function place(menu, trigger){
    const r = trigger.getBoundingClientRect();
    if(!menu.classList.contains('dd-cal')) menu.style.minWidth = r.width + 'px';
    menu.style.left = '-9999px'; menu.style.top = '0px';
    const mw = menu.offsetWidth, mh = menu.offsetHeight;
    let left = r.left, top = r.bottom + 6;
    if(left + mw > window.innerWidth - 10) left = window.innerWidth - mw - 10;
    if(left < 10) left = 10;
    if(top + mh > window.innerHeight - 10){
      const above = r.top - 6 - mh;
      top = above > 10 ? above : window.innerHeight - mh - 10;
    }
    menu.style.left = Math.round(left) + 'px';
    menu.style.top  = Math.round(top)  + 'px';
  }

  // ── option list ──────────────────────────────────
  function buildList(trigger){
    const opts = (trigger.getAttribute('data-dd-options')||'').split('|').map(s=>s.trim()).filter(Boolean);
    if(!opts.length) return;
    const ve = valEl(trigger);
    const current = ve ? ve.textContent.trim() : '';
    const menu = document.createElement('div');
    menu.className = 'dd-menu';
    opts.forEach(opt=>{
      const item = document.createElement('div');
      item.className = 'dd-item' + (opt===current ? ' sel' : '');
      item.innerHTML = `<span>${opt}</span>${CHECK}`;
      item.addEventListener('click', ev=>{
        ev.stopPropagation();
        if(ve) ve.textContent = opt;
        fireCb(trigger, opt);
        closeAll();
      });
      menu.appendChild(item);
    });
    document.body.appendChild(menu);
    place(menu, trigger);
    trigger.classList.add('dd-open');
    openMenu = menu; openTrigger = trigger;
  }

  // ── calendar ─────────────────────────────────────
  function parseRuDate(str){
    if(!str) return null;
    const m = str.match(/(\d{1,2})\s+([а-яё]+)/i);
    if(!m) return null;
    const mi = MON_GEN.indexOf(m[2].toLowerCase());
    if(mi < 0) return null;
    return new Date(BASE_YEAR, mi, +m[1]);
  }
  function fmtRuDate(d){ return `${d.getDate()} ${MON_GEN[d.getMonth()]}, ${DOW[d.getDay()]}`; }

  function calHTML(view, sel){
    const y = view.getFullYear(), m = view.getMonth();
    const startDow = (new Date(y,m,1).getDay()+6)%7;   // Mon=0
    const days = new Date(y,m+1,0).getDate();
    const today = new Date();
    let h = `<div class="dc-head"><button class="dc-nav" data-nav="-1">‹</button><div class="dc-title">${MON_NOM[m]} ${y}</div><button class="dc-nav" data-nav="1">›</button></div>`;
    h += `<div class="dc-week">`+['Пн','Вт','Ср','Чт','Пт','Сб','Вс'].map(w=>`<span>${w}</span>`).join('')+`</div>`;
    h += `<div class="dc-grid">`;
    for(let i=0;i<startDow;i++) h += `<span class="dc-day empty"></span>`;
    for(let d=1;d<=days;d++){
      const isSel = sel && sel.getFullYear()===y && sel.getMonth()===m && sel.getDate()===d;
      const isToday = today.getFullYear()===y && today.getMonth()===m && today.getDate()===d;
      const we = ((new Date(y,m,d).getDay()+6)%7) >= 5;
      h += `<span class="dc-day${isSel?' sel':''}${isToday?' today':''}${we?' we':''}" data-day="${d}">${d}</span>`;
    }
    h += `</div>`;
    return h;
  }

  function buildCalendar(trigger){
    const ve = valEl(trigger);
    let sel = parseRuDate(ve ? ve.textContent : '') || new Date(BASE_YEAR,4,24);
    let view = new Date(sel.getFullYear(), sel.getMonth(), 1);
    const menu = document.createElement('div');
    menu.className = 'dd-menu dd-cal';
    function draw(){
      menu.innerHTML = calHTML(view, sel);
      menu.querySelectorAll('.dc-nav').forEach(b=>{
        b.addEventListener('click', ev=>{
          ev.stopPropagation();
          view.setMonth(view.getMonth() + (+b.dataset.nav));
          draw(); place(menu, trigger);
        });
      });
      menu.querySelectorAll('.dc-day[data-day]').forEach(d=>{
        d.addEventListener('click', ev=>{
          ev.stopPropagation();
          const picked = new Date(view.getFullYear(), view.getMonth(), +d.dataset.day);
          if(ve) ve.textContent = fmtRuDate(picked);
          fireCb(trigger, ve ? ve.textContent : '');
          closeAll();
        });
      });
    }
    draw();
    document.body.appendChild(menu);
    place(menu, trigger);
    trigger.classList.add('dd-open');
    openMenu = menu; openTrigger = trigger;
  }

  function build(trigger){
    closeAll();
    if(trigger.getAttribute('data-dd-type') === 'calendar') buildCalendar(trigger);
    else buildList(trigger);
  }

  // ── events ───────────────────────────────────────
  document.addEventListener('click', ev=>{
    const trig = ev.target.closest('[data-dd]');
    if(trig){
      ev.stopPropagation();
      if(trig === openTrigger) closeAll();
      else build(trig);
    } else if(!ev.target.closest('.dd-menu')){
      closeAll();
    }
  });
  document.addEventListener('keydown', ev=>{ if(ev.key==='Escape') closeAll(); });
  window.addEventListener('resize', closeAll);
  // close on PAGE scroll, but allow scrolling INSIDE the menu
  window.addEventListener('scroll', ev=>{
    if(openMenu && ev.target && ev.target.nodeType===1 &&
       (ev.target===openMenu || (ev.target.closest && ev.target.closest('.dd-menu')))) return;
    closeAll();
  }, true);

})();
