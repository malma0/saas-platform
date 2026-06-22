/* ===== Теннис Клуб НСК — Поиск партнёра ===== */
(function(){
  'use strict';

  // ---- avatar helper (initials in tinted circle) ----
  const TINTS = {
    'Мария':'#b86a8e','Олег':'#5a7fb8','Илья':'#5aa882','Дмитрий':'#7a6ab8',
    'Андрей':'#5a8fb8','Ольга':'#a86a8a','Сергей':'#b88a5a','Юлия':'#a05a9a',
    'Никита':'#5a9a7a','Максим':'#5a86b0','Артём':'#6a7ab8','Татьяна':'#a86a7a',
    'Ксения':'#9a6aa8','Полина':'#b8895a','Алексей':'#3a5a7a','Екатерина':'#8a6ab0',
    'Денис':'#b8a05a','Владимир':'#5a9a6a','Михаил':'#5a8aa8','Егор':'#b8925a',
    'Анна':'#b06a8a'
  };
  function avatar(name, size){
    const first = name.trim().split(' ')[0];
    const initial = first[0];
    const tint = TINTS[first] || '#5a7088';
    const fs = Math.round(size*0.4);
    return `<div class="av" style="width:${size}px;height:${size}px;background:${tint};font-size:${fs}px"><span>${initial}</span></div>`;
  }

  const lvlClass = l => 'lvl-'+l.toLowerCase();

  // ---- фильтры ----
  let filterState = {week:'26 мая – 1 июня', time:'Весь день', level:'Все уровни', loc:'Все клубы и локации'};
  function filtersActive(){
    const f=filterState;
    return !(f.level==='Все уровни' && f.loc==='Все клубы и локации' && f.time==='Весь день');
  }
  function timeOk(tf, s){
    if(!tf || tf==='Весь день') return true;
    const h=parseInt(s,10);
    if(tf.indexOf('Утро')>=0) return h>=8 && h<12;
    if(tf.indexOf('День')>=0) return h>=12 && h<17;
    if(tf.indexOf('Вечер')>=0) return h>=17;
    return true;
  }
  function passCal(c){
    if(c.multi) return true;
    const f=filterState;
    if(f.level!=='Все уровни' && c.lvl!==f.level) return false;
    if(f.loc!=='Все клубы и локации' && !f.loc.includes(c.loc)) return false;
    if(!timeOk(f.time, c.s)) return false;
    return true;
  }
  function passList(r){
    const f=filterState;
    const lvl = r.lvl || (r.locSub||'').split(' ')[0];
    const loc = (r.loc||'').split(',')[0];
    const start = (r.time||'').split(/[–-]/)[0].trim();
    if(f.level!=='Все уровни' && lvl!==f.level) return false;
    if(f.loc!=='Все клубы и локации' && !f.loc.includes(loc)) return false;
    if(!timeOk(f.time, start)) return false;
    return true;
  }
  function emptyMsg(){
    return `<div style="text-align:center;padding:46px 20px;color:var(--muted);font-size:13.5px;line-height:1.5">
      <div style="font-size:15px;font-weight:700;color:#bcbeb7;margin-bottom:6px">Ничего не найдено</div>
      Под выбранные фильтры нет заявок.<br>Измените параметры или сбросьте фильтры.</div>`;
  }

  // =====================================================
  // VIEW 1 — ДОСТУПНЫЕ ЗАЯВКИ (недельный календарь)
  // =====================================================
  const DAYS = [
    {d:'ПН', dt:'26 мая'},{d:'ВТ', dt:'27 мая'},{d:'СР', dt:'28 мая'},
    {d:'ЧТ', dt:'29 мая'},{d:'ПТ', dt:'30 мая'},{d:'СБ', dt:'31 мая', we:true},{d:'ВС', dt:'1 июня', we:true}
  ];
  const CAL_START = 9, CAL_END = 23, HOUR_H = 58; // 09:00–23:00

  // status: active | resp | conf | past
  const CAL = [
    // ПН
    {day:0, s:'09:00', e:'11:00', name:'Дмитрий К.', ttw:900, lvl:'Intermediate', loc:'Топ-Спин', status:'active', sub:'Столы свободны'},
    {day:0, s:'12:00', e:'13:00', name:'Ольга Л.',  ttw:270, lvl:'Intermediate', loc:'Топ-Спин', status:'past',   sub:'Время прошло'},
    {day:0, s:'14:00', e:'16:00', name:'Сергей Б.',  ttw:560, lvl:'Intermediate', loc:'Топ-Спин', status:'active', sub:'1 отклик', cup:true},
    {day:0, s:'17:00', e:'18:30', name:'Юлия Ф.',    ttw:430, lvl:'Intermediate', loc:'Топ-Спин', status:'conf',   sub:'Партнёр подтверждён'},
    {day:0, s:'20:00', e:'21:30', name:'Никита З.',  ttw:350, lvl:'Intermediate', loc:'Топ-Спин', status:'active', sub:'Столы свободны'},
    // ВТ
    {day:1, s:'11:00', e:'12:30', name:'Андрей С.',  ttw:400, lvl:'Intermediate', loc:'Топ-Спин', status:'resp',   sub:'Есть в др. клубах'},
    {day:1, s:'17:30', e:'19:00', name:'Полина К.',  ttw:380, lvl:'Intermediate', loc:'Топ-Спин', status:'active', sub:'1 отклик', cup:true},
    {day:1, s:'19:30', e:'21:00', name:'Алексей В.', ttw:570, lvl:'Intermediate', loc:'Топ-Спин', status:'active', sub:'2 отклика', cup:true},
    {day:1, s:'21:00', e:'22:30', name:'Ксения А.',  ttw:380, lvl:'Intermediate', loc:'Топ-Спин', status:'past',   sub:'Время прошло'},
    // СР — special multi cell at 15:00-16:00
    {day:2, s:'09:00', e:'10:30', name:'Мария П.',   ttw:380, lvl:'Intermediate', loc:'Топ-Спин', status:'conf',   sub:'Партнёр подтверждён'},
    {day:2, s:'15:00', e:'16:00', multi:true, count:3},
    // ЧТ
    {day:3, s:'10:00', e:'11:30', name:'Илья Н.',    ttw:510, lvl:'Advanced',     loc:'Топ-Спин', status:'active', sub:'Столы свободны'},
    {day:3, s:'13:00', e:'14:30', name:'Максим С.',  ttw:350, lvl:'Intermediate', loc:'Топ-Спин', status:'active', sub:'Столы свободны'},
    {day:3, s:'16:00', e:'17:30', name:'Артём К.',   ttw:430, lvl:'Intermediate', loc:'Топ-Спин', status:'resp',   sub:'Есть в др. клубах'},
    {day:3, s:'19:30', e:'21:00', name:'Татьяна С.', ttw:380, lvl:'Intermediate', loc:'Топ-Спин', status:'conf',   sub:'Партнёр подтверждён'},
    // ПТ
    {day:4, s:'09:00', e:'10:30', name:'Олег Р.',    ttw:720, lvl:'Advanced',     loc:'Арена',    status:'active', sub:'2 отклика', cup:true},
    {day:4, s:'13:30', e:'15:00', name:'Денис Т.',   ttw:410, lvl:'Intermediate', loc:'Топ-Спин', status:'active', sub:'1 отклик'},
    {day:4, s:'17:00', e:'18:30', name:'Егор Ш.',    ttw:560, lvl:'Advanced',     loc:'Арена',    status:'active', sub:'2 отклика', cup:true},
    {day:4, s:'20:00', e:'21:30', name:'Михаил Д.',  ttw:320, lvl:'Intermediate', loc:'Арена',    status:'resp',   sub:'Есть в др. клубах'},
    // СБ
    {day:5, s:'10:00', e:'11:30', name:'Екатерина Л.',ttw:380,lvl:'Intermediate', loc:'Топ-Спин', status:'conf',   sub:'Партнёр подтверждён'},
    {day:5, s:'17:30', e:'19:00', name:'Татьяна С.', ttw:380, lvl:'Intermediate', loc:'Топ-Спин', status:'conf',   sub:'Партнёр подтверждён'},
    // ВС
    {day:6, s:'13:30', e:'15:00', name:'Владимир С.',ttw:600, lvl:'Intermediate', loc:'Топ-Спин', status:'active', sub:'Столы свободны'},
  ];

  function toMin(t){const[h,m]=t.split(':').map(Number);return h*60+m;}
  function topPx(t){return ((toMin(t)-CAL_START*60)/60)*HOUR_H;}

  function renderAvailable(){
    const wrap = document.getElementById('tab-available');
    let h = '';

    // legend
    h += `<div class="cal-legend">
      <div class="li"><span class="dot d-active"></span>Активная заявка</div>
      <div class="li"><span class="dot d-resp"></span>Есть отклики</div>
      <div class="li"><span class="dot d-conf"></span>Партнёр подтверждён</div>
      <div class="li"><span class="dot d-past"></span>Прошедшая / неактивна</div>
      <div class="li-info"><svg width="15" height="15" viewBox="0 0 24 24" class="ico" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.5"/></svg>Любой игрок может откликнуться на заявку. Автор выбирает и подтверждает одного партнёра.</div>
    </div>`;

    // calendar
    h += `<div class="cal-panel"><div class="cal-scroll"><div class="cal">`;
    // header
    h += `<div class="cal-hcell tcorner">ВРЕМЯ</div>`;
    DAYS.forEach(d=>{
      h += `<div class="cal-hcell${d.we?' weekend':''}"><div class="d-day">${d.d}</div><div class="d-date">${d.dt}</div></div>`;
    });
    const totalH = (CAL_END-CAL_START)*HOUR_H;
    // time column
    h += `<div class="cal-timecol" style="height:${totalH}px">`;
    for(let hr=CAL_START; hr<=CAL_END-1; hr++){
      h += `<div class="th" style="top:${(hr-CAL_START)*HOUR_H}px">${String(hr).padStart(2,'0')}:00</div>`;
    }
    h += `</div>`;
    // day columns
    DAYS.forEach((d,di)=>{
      h += `<div class="cal-col" style="height:${totalH}px">`;
      for(let hr=CAL_START+1; hr<CAL_END; hr++){
        h += `<div class="hline" style="top:${(hr-CAL_START)*HOUR_H}px"></div>`;
      }
      CAL.filter(c=>c.day===di && passCal(c)).forEach((c,ci)=>{
        const top = topPx(c.s)+4;
        const hgt = topPx(c.e)-topPx(c.s)-8;
        if(c.multi){
          h += `<div class="cal-card c-multi" id="multi-cell" style="top:${top}px;height:${Math.max(hgt,96)}px"
                 onclick="window.PARTNERS.openSlot()">
            <div style="display:flex;align-items:center;justify-content:space-between"><span class="m-count">${c.count} заявки</span><span class="new-badge">Новое</span></div>
            <div class="m-time">${c.s} – ${c.e}</div>
            <div class="m-hint">Нажмите, чтобы посмотреть</div>
          </div>`;
        } else {
          const cls = 'c-'+c.status;
          const cup = c.cup ? `<svg width="13" height="13" viewBox="0 0 24 24" class="ico" style="position:absolute;right:9px;bottom:9px;stroke:var(--muted-2)"><path d="M6 4h12v3a6 6 0 0 1-12 0V4Z"/><path d="M6 6H4v1a3 3 0 0 0 3 3M18 6h2v1a3 3 0 0 1-3 3M9 16h6M10 16v3M14 16v3M8 21h8"/></svg>` : '';
          const sd = c.status==='active'?'d-active':c.status==='resp'?'d-resp':c.status==='conf'?'d-conf':'d-past';
          h += `<div class="cal-card ${cls}" style="top:${top}px;height:${Math.max(hgt,84)}px">
            <div class="cc-top">${avatar(c.name,30)}<div><div class="cc-name">${c.name}</div><div class="cc-lvl ${lvlClass(c.lvl)}">${c.lvl}</div></div></div>
            <div class="cc-loc">${c.loc}</div>
            <div class="cc-status"><span class="dot ${sd}"></span>${c.sub}</div>
            ${cup}
          </div>`;
        }
      });
      h += `</div>`;
    });
    h += `</div></div></div>`;

    wrap.innerHTML = h;
  }

  // =====================================================
  // VIEW 2 — МОИ ЗАЯВКИ (список с раскрытием)
  // =====================================================
  const MINE = [
    {date:'Пн, 26 мая', time:'17:00 – 18:00', loc:'Топ-Спин, Новосибирск', locSub:'350 TTW · Столы свободны',
     lvl:'Intermediate', status:'active', count:3},
    {date:'Ср, 28 мая', time:'15:00 – 16:00', loc:'Топ-Спин, Новосибирск', locSub:'Intermediate · Столы свободны',
     lvl:'Intermediate', status:'wait', count:3, open:true,
     authorNote:'Ищу партнёра для тренировки и отработки топ-спина справа.',
     responders:[
       {name:'Мария П.', ttw:380, match:'Подходит по уровню', when:'Была онлайн сегодня', best:true},
       {name:'Олег Р.',  ttw:720, match:'Подходит по уровню', when:'Был онлайн вчера'},
       {name:'Илья Н.',  ttw:510, match:'Подходит по уровню', when:'Был онлайн 2 дня назад'}
     ]},
    {date:'Пт, 30 мая', time:'09:00 – 10:00', loc:'Топ-Спин, Новосибирск', locSub:'Advanced · Партнёр подтверждён',
     lvl:'Advanced', status:'conf', count:1},
    {date:'Сб, 31 мая', time:'18:00 – 19:00', loc:'Топ-Спин, Новосибирск', locSub:'Advanced · Ожидает подтверждения',
     lvl:'Advanced', status:'wait', count:2},
    {date:'Вс, 1 июня', time:'14:00 – 15:00', loc:'Арена, Новосибирск', locSub:'600 TTW · Столы свободны',
     lvl:'Advanced', status:'past', count:0},
  ];

  const STATUS_TXT = {
    active:{c:'s-active', d:'d-active', t:'Активна'},
    wait:{c:'s-wait', d:'d-wait', t:'Ждёт подтверждения автора'},
    conf:{c:'s-conf', d:'d-conf', t:'Партнёр подтверждён'},
    past:{c:'s-past', d:'d-past', t:'Прошедшая'}
  };

  const calIco = `<svg width="17" height="17" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg>`;
  const pinIco = `<svg width="17" height="17" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg>`;
  const clockIco= `<svg width="17" height="17" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>`;

  function renderMine(){
    const wrap = document.getElementById('tab-mine');
    const visible = MINE.filter(passList);
    let h = filtersActive()
      ? `<div class="list-meta">Найдено <b>${visible.length}</b> ${plural(visible.length,'заявка','заявки','заявок')}</div>`
      : `<div class="list-meta">У вас <b>5 заявок</b>, из них <b>2 с откликами</b></div>`;
    if(!visible.length){ wrap.innerHTML = h + emptyMsg(); return; }
    MINE.forEach((r,i)=>{
      if(!passList(r)) return;
      const st = STATUS_TXT[r.status];
      const cntCls = r.count>0 ? 'cnt-on' : 'cnt-off';
      h += `<div class="rcard${r.open?' open':''}" data-mine="${i}">`;
      h += `<div class="rrow" onclick="window.PARTNERS.toggleMine(${i})">
        <div class="rcell-date"><span class="ri">${calIco}</span><div><div class="rd-1">${r.date}</div><div class="rd-2">${r.time}</div></div></div>
        <div class="rcell-loc"><span class="ri">${pinIco}</span><div><div class="rl-1">${r.loc}</div><div class="rl-2">${r.locSub}</div></div></div>
        <div class="lvl ${lvlClass(r.lvl)}"><span class="dot d-conf" style="background:currentColor"></span>${r.lvl}</div>
        <div class="status-txt ${st.c}"><span class="dot ${st.d}"></span>${st.t}</div>
        <div class="cnt-badge ${cntCls}">${r.count} ${plural(r.count,'отклик','отклика','откликов')}</div>
        <div class="r-chev"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></div>
      </div>`;

      if(r.responders){
        h += `<div class="rbody"><div class="rb-grid">`;
        // author card
        h += `<div class="author-card">
          <div class="ac-top">${avatar('Алексей',54)}<div><div class="ac-role">Вы автор заявки</div><div class="ac-name">Алексей В.</div><div class="ac-ttw">570 TTW</div></div></div>
          <div class="ac-line"><span class="ai">${calIco}</span>Ср, 28 мая, 15:00 – 16:00</div>
          <div class="ac-line"><span class="ai">${pinIco}</span>Топ-Спин, Новосибирск<br>Столы свободны</div>
          <div class="ac-line"><span class="ai"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><path d="M6 20V10M12 20V4M18 20v-7"/></svg></span>Уровень: Intermediate</div>
          <div class="ac-note">«${r.authorNote}»</div>
        </div>`;
        // responders
        h += `<div>`;
        h += `<div class="resp-info"><span class="ii"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.5"/></svg></span><p>Любой игрок может откликнуться на вашу заявку.<br>Вы выбираете и подтверждаете одного партнёра.</p></div>`;
        h += `<div class="resp-title">Откликнувшиеся игроки (${r.responders.length})</div>`;
        r.responders.forEach(p=>{
          h += `<div class="responder${p.best?' best':''}">
            ${avatar(p.name,42)}
            <div style="min-width:150px"><div class="rs-name">${p.name}</div><div class="rs-ttw">${p.ttw} TTW</div></div>
            <div class="rs-mid"><div class="rs-match">${p.match}</div><div class="rs-when">${p.when}</div></div>`;
          if(p.best){
            h += `<button class="btn btn-lime"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg> Подтвердить игру</button>
                  <button class="btn btn-red">Отклонить</button>`;
          } else {
            h += `<button class="btn btn-dark">Выбрать</button>
                  <button class="btn btn-ico"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><circle cx="5" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1.5" fill="currentColor" stroke="none"/></svg></button>`;
          }
          h += `</div>`;
        });
        h += `</div>`;
        h += `</div></div>`;
      }
      h += `</div>`;
    });

    wrap.innerHTML = h;
  }

  // =====================================================
  // VIEW 3 — МОИ ОТКЛИКИ (список с раскрытием)
  // =====================================================
  const RESP = [
    {date:'Ср, 28 мая', time:'15:00 – 16:00', author:'Олег Р.', ttw:720, loc:'Топ-Спин, Новосибирск',
     locSub:'Intermediate · Столы свободны', status:'wait', open:true,
     authorNote:'Хочу потренироваться в атакующей игре, отрабатываю топ-спины справа. Буду рад игре на равных.',
     sentAt:'сегодня в 14:05'},
    {date:'Пн, 26 мая', time:'17:00 – 18:00', author:'Мария П.', ttw:380, loc:'Топ-Спин, Новосибирск',
     locSub:'Intermediate · Столы свободны', status:'conf'},
    {date:'Сб, 31 мая', time:'18:00 – 19:00', author:'Илья Н.', ttw:510, loc:'Арена, Новосибирск',
     locSub:'Advanced · Партнёр подтверждён', status:'notsel'},
    {date:'Вс, 1 июня', time:'14:00 – 15:00', author:'Владимир С.', ttw:600, loc:'Арена, Новосибирск',
     locSub:'Advanced · Столы свободны', status:'past'},
  ];
  const RESP_STATUS = {
    wait:{c:'s-wait', d:'d-wait', t:'Ждёт решения автора'},
    conf:{c:'s-conf', d:'d-conf', t:'Партнёр подтверждён'},
    notsel:{c:'s-past', d:'d-past', t:'Не выбран'},
    past:{c:'s-past', d:'d-past', t:'Прошедшая'}
  };

  function renderResponses(){
    const wrap = document.getElementById('tab-responses');
    const visible = RESP.filter(passList);
    let h = filtersActive()
      ? `<div class="list-meta">Найдено <b>${visible.length}</b> ${plural(visible.length,'отклик','отклика','откликов')}</div>`
      : `<div class="list-meta">Вы откликнулись на <b>6 заявок</b>, <b>2 из них активны</b></div>`;
    if(!visible.length){ wrap.innerHTML = h + emptyMsg(); return; }
    RESP.forEach((r,i)=>{
      if(!passList(r)) return;
      const st = RESP_STATUS[r.status];
      h += `<div class="rcard${r.open?' open':''}" data-resp="${i}">`;
      h += `<div class="rrow" style="grid-template-columns:150px 220px 1fr auto auto 28px" onclick="window.PARTNERS.toggleResp(${i})">
        <div class="rcell-date"><span class="ri">${clockIco}</span><div><div class="rd-1">${r.date}</div><div class="rd-2">${r.time}</div></div></div>
        <div class="rcell-player">${avatar(r.author,40)}<div><div class="rp-name">${r.author}</div><div class="rp-ttw">${r.ttw} TTW</div></div></div>
        <div class="rcell-loc"><span class="ri">${pinIco}</span><div><div class="rl-1">${r.loc}</div><div class="rl-2">${r.locSub}</div></div></div>
        <div class="status-txt ${st.c}"><span class="dot ${st.d}"></span>${st.t}</div>
        <button class="btn ${r.open?'btn-dark':'btn-dark'}" onclick="event.stopPropagation();window.PARTNERS.toggleResp(${i})">${r.open?'Отменить отклик':'Подробнее'}</button>
        <div class="r-chev"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></div>
      </div>`;

      if(r.authorNote){
        h += `<div class="rbody"><div class="rb-author">`;
        h += `<div class="author-card">
          <div class="ac-top">${avatar(r.author,54)}<div><div class="ac-name">${r.author}</div><div class="ac-ttw">${r.ttw} TTW</div></div></div>
          <div class="ac-line" style="color:var(--muted)">Топ-Спин, Новосибирск<br>Intermediate</div>
          <div class="ac-line"><span class="ai">${calIco}</span>${r.date}</div>
          <div class="ac-line"><span class="ai">${clockIco}</span>${r.time}</div>
          <div class="ac-line"><span class="ai">${pinIco}</span>Топ-Спин, Новосибирск<br>Столы свободны</div>
        </div>`;
        h += `<div>
          <div class="about-req"><span class="ab-ico"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></span>О заявке от автора</div>
          <div class="about-text">${r.authorNote}</div>
          <div class="status-banner wait">
            <span class="sb-ico"><svg width="18" height="18" viewBox="0 0 24 24" class="ico" style="color:var(--orange)"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
            <div>
              <div class="sb-1">Вы уже откликнулись на эту заявку</div>
              <div class="sb-2">Теперь ожидайте, пока автор выберет партнёра и подтвердит вашу заявку.</div>
              <div class="sb-3"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg>Отклик отправлен ${r.sentAt}</div>
            </div>
          </div>
          <div style="display:flex;gap:10px">
            <button class="btn btn-red"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="M6 6l12 12M18 6 6 18"/></svg> Отменить отклик</button>
            <button class="btn btn-dark"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg> Написать</button>
          </div>
        </div>`;
        h += `</div></div>`;
      }
      h += `</div>`;
    });

    wrap.innerHTML = h;
  }

  // =====================================================
  // SIDEBAR (контекстный по вкладкам)
  // =====================================================
  const SIDE = {
    available: () => {
      // slot detail (Ср, 28 мая, 15:00–16:00)
      const offers = [
        {name:'Мария П.', ttw:380, lvl:'Intermediate', loc:'Топ-Спин, Новосибирск', desc:'Свободна в указанное время, опыт парных игр.', pub:'2 часа назад', state:'open'},
        {name:'Илья Н.',  ttw:510, lvl:'', loc:'Топ-Спин, Новосибирск', desc:'Свободен в указанное время, ищу игру.', pub:'3 часа назад', state:'replied'},
        {name:'Олег Р.',  ttw:720, lvl:'Advanced', loc:'Новосибирск', desc:'Люблю быструю атаку и активные розыгрыши.', pub:'4 часа назад', state:'open'},
        {name:'Анна К.',  ttw:480, lvl:'', loc:'Арена, Новосибирск', desc:'Играю в паре регулярно.', pub:'5 часов назад', state:'done'}
      ];
      let h = `<div class="side-card">
        <div class="slot-head"><div class="sh-title">Ср, 28 мая, 15:00 – 16:00 <span class="new-badge">Новое</span></div>
          <span class="slot-close" onclick="window.PARTNERS.switchTab('available')"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><path d="M6 6l12 12M18 6 6 18"/></svg></span></div>
        <div class="slot-sub">3 заявки на это время</div>
        <div class="slot-note"><span class="ni"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.5"/></svg></span>Выберите партнёра и откликнитесь на заявку.</div>`;
      offers.forEach(o=>{
        const hot = o.state==='open';
        h += `<div class="offer${hot?' hot':''}">
          <div class="of-top">${avatar(o.name,40)}
            <div style="flex:1;min-width:0"><div class="of-name">${o.name}</div>
              <div class="of-lvl ${o.lvl?lvlClass(o.lvl):''}" style="${o.lvl?'':'color:var(--muted)'}">${o.lvl?o.lvl+' · ':''}${o.ttw} TTW</div>
              <div class="of-loc">${o.loc}</div></div>
            <div class="of-pub">Опубликовано<br>${o.pub}</div></div>
          <div class="of-desc">${o.desc}</div>`;
        if(o.state==='open'){
          h += `<div class="of-actions"><button class="btn btn-lime"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="M5 12h14M13 6l6 6-6 6"/></svg> Откликнуться</button>
            <button class="btn btn-ico"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></button></div>`;
        } else if(o.state==='replied'){
          h += `<div class="offer-replied"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg> Вы уже откликнулись</div>`;
        } else {
          h += `<div class="offer-done"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg> Партнёр найден</div>`;
        }
        h += `</div>`;
      });
      h += `<div class="slot-foot"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>Обновлено: сегодня, 08:45</div>`;
      h += `</div>`;
      return h;
    },

    mine: () => `
      <div class="side-card">
        <h3>Как работает подтверждение</h3>
        <div class="step"><span class="sn">1</span><div><div class="st-1">Создайте заявку</div><div class="st-2">Укажите время, место и уровень игры. Другие игроки увидят вашу заявку.</div></div></div>
        <div class="step"><span class="sn">2</span><div><div class="st-1">Игроки откликаются</div><div class="st-2">Любой подходящий игрок может откликнуться на вашу заявку.</div></div></div>
        <div class="step"><span class="sn">3</span><div><div class="st-1">Выберите партнёра</div><div class="st-2">Просмотрите отклики и подтвердите одного игрока для игры.</div></div></div>
        <div class="step"><span class="sn">4</span><div><div class="st-1">Играйте!</div><div class="st-2">Встретьтесь в клубе и наслаждайтесь игрой.</div></div></div>
      </div>
      <div class="side-card">
        <h3>Статусы заявок</h3>
        <div class="sl"><span class="sl-dot d-active"></span><div><div class="sl-1 s-active">Активна</div><div class="sl-2">Ваша заявка видна другим игрокам</div></div></div>
        <div class="sl"><span class="sl-dot d-wait"></span><div><div class="sl-1 s-wait">Ждёт подтверждения автора</div><div class="sl-2">Есть отклики, выберите партнёра</div></div></div>
        <div class="sl"><span class="sl-dot d-conf"></span><div><div class="sl-1 s-conf">Партнёр подтверждён</div><div class="sl-2">Партнёр выбран, встреча подтверждена</div></div></div>
        <div class="sl"><span class="sl-dot d-past"></span><div><div class="sl-1 s-past">Прошедшая</div><div class="sl-2">Игра завершена</div></div></div>
      </div>`,

    responses: () => `
      <div class="side-card">
        <h3>Как работают мои отклики</h3>
        <div class="step"><span class="sn">1</span><div><div class="st-1">Найдите подходящую заявку</div><div class="st-2">Используйте фильтры, чтобы найти игрока по времени, уровню и локации.</div></div></div>
        <div class="step"><span class="sn">2</span><div><div class="st-1">Откликнитесь на заявку</div><div class="st-2">Отправьте отклик автору и коротко представьтесь при желании.</div></div></div>
        <div class="step"><span class="sn">3</span><div><div class="st-1">Дождитесь решения автора</div><div class="st-2">Автор рассмотрит отклики и выберет партнёра для игры.</div></div></div>
        <div class="step"><span class="sn">4</span><div><div class="st-1">Получите подтверждение</div><div class="st-2">После подтверждения вы сможете увидеть детали игры в расписании и связаться с партнёром.</div></div></div>
      </div>
      <div class="side-card">
        <h3>Статусы откликов</h3>
        <div class="sl"><span class="sl-dot d-active"></span><div><div class="sl-1 s-active">Отклик отправлен</div><div class="sl-2">Вы откликнулись, ожидая решения автора</div></div></div>
        <div class="sl"><span class="sl-dot d-wait"></span><div><div class="sl-1 s-wait">Ждёт решения автора</div><div class="sl-2">Ваш отклик ожидает подтверждения автора</div></div></div>
        <div class="sl"><span class="sl-dot d-conf"></span><div><div class="sl-1 s-conf">Партнёр подтверждён</div><div class="sl-2">Автор выбрал вас, игра подтверждена</div></div></div>
        <div class="sl"><span class="sl-dot d-past"></span><div><div class="sl-1 s-past">Не выбран</div><div class="sl-2">Автор выбрал другого партнёра</div></div></div>
      </div>`
  };

  function plural(n, one, few, many){
    const m10=n%10, m100=n%100;
    if(m10===1 && m100!==11) return one;
    if(m10>=2 && m10<=4 && (m100<10||m100>=20)) return few;
    return many;
  }

  // =====================================================
  // CONTROLLER
  // =====================================================
  let activeTab = 'available';

  function setSidebar(){ document.getElementById('pl-side').innerHTML = SIDE[activeTab](); }

  function switchTab(tab){
    activeTab = tab;
    document.querySelectorAll('.ptab').forEach(b=>b.classList.toggle('active', b.dataset.tab===tab));
    document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
    document.getElementById('tab-'+tab).classList.add('active');
    setSidebar();
  }

  const API = {
    switchTab,
    toggleMine(i){
      MINE[i].opened = !MINE[i].open;
      MINE[i].open = !MINE[i].open;
      const card = document.querySelector(`[data-mine="${i}"]`);
      if(card) card.classList.toggle('open');
    },
    toggleResp(i){
      RESP[i].open = !RESP[i].open;
      const card = document.querySelector(`[data-resp="${i}"]`);
      if(card) card.classList.toggle('open');
    },
    openSlot(){
      const cell = document.getElementById('multi-cell');
      if(cell) cell.classList.add('sel');
      setSidebar();
      document.getElementById('pl-side').scrollIntoView({behavior:'smooth', block:'nearest'});
    }
  };
  window.PARTNERS = API;

  // filters wiring
  function fTxt(s){const v=s.querySelector('[data-dd-val]');return v?v.textContent.trim():'';}
  function readFilters(){
    const sels=document.querySelectorAll('.filters .f-sel[data-dd]');
    if(sels.length>=4){ filterState.week=fTxt(sels[0]); filterState.time=fTxt(sels[1]); filterState.level=fTxt(sels[2]); filterState.loc=fTxt(sels[3]); }
  }
  function renderAll(){ renderAvailable(); renderMine(); renderResponses(); }
  window.onPartnerFilter = function(){ readFilters(); renderAll(); };

  // init
  renderAvailable();
  renderMine();
  renderResponses();
  setSidebar();
  document.querySelectorAll('.ptab').forEach(b=>{
    b.addEventListener('click', ()=>switchTab(b.dataset.tab));
  });
  const resetBtn=document.querySelector('.f-btn.reset');
  if(resetBtn) resetBtn.addEventListener('click', ()=>{
    const defs=['26 мая – 1 июня','Весь день','Все уровни','Все клубы и локации'];
    document.querySelectorAll('.filters .f-sel[data-dd]').forEach((s,i)=>{const v=s.querySelector('[data-dd-val]'); if(v&&defs[i]!==undefined) v.textContent=defs[i];});
    filterState={week:defs[0],time:defs[1],level:defs[2],loc:defs[3]};
    renderAll();
  });
  const searchBtn=document.querySelector('.f-btn.search');
  if(searchBtn) searchBtn.addEventListener('click', ()=>{ readFilters(); renderAll(); });

})();
