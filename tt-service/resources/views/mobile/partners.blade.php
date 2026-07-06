<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Поиск партнёра — Теннис Клуб НСК</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="{{ asset('js/device.js') }}"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  .ph-ava{display:grid;place-items:center;width:100%;height:100%;background:linear-gradient(135deg,#222a1e,#2e3a26);color:#c6e21a;font-weight:800;font-size:15px;}
  :root{
    --bg:#0a0b09;--panel:#101210;--field:rgba(255,255,255,.04);
    --line:rgba(255,255,255,.08);--line-soft:rgba(255,255,255,.05);
    --txt:#f0f1ec;--muted:#8c8f86;--muted-2:#6f726a;
    --green:#c6e21a;--green-deep:#9fb50f;--red:#e2554a;--radius:16px;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  html,body{height:100%;}
  body{background:#000;font-family:'Manrope',system-ui,sans-serif;-webkit-font-smoothing:antialiased;display:flex;justify-content:center;}
  a{color:inherit;text-decoration:none;}
  .green{color:var(--green);}
  svg{display:block;}
  .ico{stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round;}

  .app{position:relative;width:100%;max-width:430px;background:var(--bg);color:var(--txt);height:100vh;display:flex;flex-direction:column;overflow:hidden;}
  @media (min-width:480px){.app{height:min(920px,96vh);margin:auto;border-radius:30px;border:1px solid rgba(255,255,255,.1);box-shadow:0 30px 80px rgba(0,0,0,.6);}}
  .screen{flex:1;overflow-y:auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;padding:0 16px calc(86px + env(safe-area-inset-bottom));}
  .screen::-webkit-scrollbar{width:0;}

  header{display:flex;align-items:center;gap:12px;padding:16px 2px 12px;position:sticky;top:0;z-index:20;background:linear-gradient(180deg,var(--bg) 76%,rgba(10,11,9,0));}
  .logo{display:flex;align-items:center;gap:11px;flex:1;min-width:0;}
  .logo-mark{width:38px;height:38px;flex:none;}
  .brand{font-size:16px;font-weight:800;letter-spacing:.3px;line-height:1.05;}
  .sub{font-size:10px;color:var(--muted);margin-top:3px;}
  .hicon{width:46px;height:46px;border-radius:13px;background:var(--field);border:1px solid var(--line);display:grid;place-items:center;color:var(--txt);flex:none;}
  .hicon svg{color:var(--green);}

  .ptitle{font-size:25px;font-weight:800;letter-spacing:-.3px;margin-top:6px;line-height:1.1;}
  .psub{font-size:13px;color:var(--muted);margin-top:8px;line-height:1.5;}

  .actions{display:flex;flex-direction:column;gap:10px;margin-top:16px;}
  .btn-green{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;background:var(--green);color:#13160a;font-weight:800;font-size:15px;border:none;border-radius:12px;padding:15px;cursor:pointer;font-family:inherit;}
  .btn-green:active{filter:brightness(.94);}
  .btn-ghost{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;background:var(--field);border:1px solid var(--line);color:var(--txt);font-weight:700;font-size:14.5px;border-radius:12px;padding:14px;cursor:pointer;font-family:inherit;}
  .btn-ghost svg{color:var(--green);}

  .filters{margin-top:14px;background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:14px;}
  .fgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
  .ffield{background:var(--field);border:1px solid var(--line);border-radius:11px;padding:10px 12px;display:flex;align-items:center;gap:9px;cursor:pointer;min-width:0;}
  .ffield .fi{color:var(--green);flex:none;}
  .ffield .fl{flex:1;min-width:0;}
  .ffield .fl .k{font-size:10px;color:var(--muted);}
  .ffield .fl .v{font-size:13.5px;font-weight:700;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .ffield .chev{color:var(--muted-2);flex:none;}
  .frow{display:flex;gap:10px;margin-top:10px;}
  .frow .btn-green,.frow .btn-ghost{margin:0;}
  .frow .btn-green{flex:1;}
  .frow .btn-ghost{flex:1;}

  .tabs{display:flex;gap:6px;margin-top:18px;border-bottom:1px solid var(--line);}
  .tabx{flex:1;text-align:center;padding:11px 4px;font-size:13.5px;font-weight:700;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-1px;cursor:pointer;}
  .tabx.active{color:var(--green);border-bottom-color:var(--green);}

  .days{display:flex;gap:8px;margin-top:14px;overflow-x:auto;padding-bottom:4px;}
  .days::-webkit-scrollbar{height:0;}
  .day{flex:0 0 auto;width:52px;background:var(--panel);border:1px solid var(--line);border-radius:13px;padding:9px 0;display:flex;flex-direction:column;align-items:center;gap:5px;cursor:pointer;}
  .day.sel{border-color:var(--green);background:rgba(198,226,26,.06);}
  .day .dw{font-size:11px;font-weight:700;color:var(--muted);}
  .day.we .dw,.day.we .dd{color:var(--red);}
  .day .dd{font-size:12px;font-weight:600;}
  .day .dc{width:20px;height:20px;border-radius:50%;background:var(--green);color:#13160a;font-size:11px;font-weight:800;display:grid;place-items:center;}
  .day.we .dc{background:var(--red);color:#fff;}
  .day .dot{width:5px;height:5px;border-radius:50%;background:var(--muted-2);}

  .cards{margin-top:16px;display:flex;flex-direction:column;gap:12px;}
  .pcard{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:14px;position:relative;}
  .pc-new{position:absolute;top:12px;right:12px;font-size:10.5px;font-weight:800;padding:3px 9px;border-radius:7px;background:var(--red);color:#fff;}
  .pc-top{display:flex;gap:12px;}
  .pc-av{width:54px;height:54px;border-radius:50%;flex:none;overflow:hidden;border:1px solid var(--line);}
  .pc-id{flex:1;min-width:0;}
  .pc-name{font-size:16px;font-weight:800;}
  .pc-meta{display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap;}
  .lvl{font-size:11px;font-weight:700;padding:3px 9px;border-radius:7px;border:1px solid;}
  .lvl-mid{color:#c6b3f0;border-color:rgba(154,124,226,.5);background:rgba(154,124,226,.14);}
  .lvl-adv{color:#e6b066;border-color:rgba(214,150,70,.5);background:rgba(214,150,70,.14);}
  .ttw{font-size:11.5px;color:var(--muted);}
  .pc-loc{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--muted);margin-top:7px;}
  .pc-loc svg{color:var(--green-deep);flex:none;}
  .pc-status{display:flex;align-items:flex-start;gap:8px;font-size:12px;margin-top:12px;line-height:1.4;}
  .pc-status .sdot{width:8px;height:8px;border-radius:50%;flex:none;margin-top:4px;}
  .sdot.ok{background:#5fc46a;}
  .sdot.busy{background:var(--red);}
  .sdot.off{background:var(--muted-2);}
  .pc-status .stxt{color:#bcbfb6;}
  .pc-desc{font-size:12.5px;color:var(--muted);margin-top:8px;line-height:1.45;}
  .pc-foot{display:flex;align-items:center;gap:10px;margin-top:14px;}
  .pc-time{font-size:13.5px;font-weight:700;background:var(--field);border:1px solid var(--line);border-radius:10px;padding:9px 12px;flex:none;}
  .pc-act{flex:1;display:flex;align-items:center;justify-content:center;background:var(--green);color:#13160a;font-weight:800;font-size:13.5px;border:none;border-radius:10px;padding:11px;cursor:pointer;font-family:inherit;}
  .pc-act.done{background:transparent;border:1px solid var(--line);color:var(--muted);}
  .pc-chat{width:42px;height:42px;flex:none;border-radius:10px;background:var(--field);border:1px solid var(--line);color:var(--muted);display:grid;place-items:center;cursor:pointer;}

  .tabbar{position:fixed;left:0;right:0;bottom:0;z-index:100;display:flex;background:rgba(13,14,12,.96);-webkit-backdrop-filter:blur(14px);backdrop-filter:blur(14px);border-top:1px solid var(--line);padding:9px 4px calc(9px + env(safe-area-inset-bottom));}
  .tab{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;color:var(--muted);font-size:9.5px;font-weight:600;padding:2px 0;background:none;border:none;font-family:inherit;cursor:pointer;white-space:nowrap;overflow:hidden;}
  .tab svg{flex:none;}
  .tab.active{color:var(--green);}
</style>
</head>
<body>
<div class="app">

  <div class="screen" data-screen-label="Поиск партнёра (мобильная)">
    <header>
      <div class="logo">
        <svg class="logo-mark" viewBox="0 0 48 48">
          <g transform="rotate(-32 24 24)"><ellipse cx="18" cy="17" rx="13" ry="14" fill="#1d1f1a" stroke="rgba(255,255,255,.18)" stroke-width="1"/><rect x="15.5" y="29" width="5" height="13" rx="2.5" fill="#1d1f1a"/></g>
          <g transform="rotate(28 30 22)"><ellipse cx="30" cy="17" rx="13" ry="14" fill="#c6e21a"/><rect x="27.5" y="29" width="5" height="13" rx="2.5" fill="#c6e21a"/></g>
          <circle cx="14" cy="11" r="3.4" fill="#f0f1ec"/>
        </svg>
        <div><div class="brand">ТЕННИС КЛУБ <span class="green">НСК</span></div><div class="sub">Премиальный клуб настольного тенниса</div></div>
      </div>
      <a class="hicon" href="tel:+73832078620"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><path d="M6.5 4h3l1.5 4-2 1.5a11 11 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2A16 16 0 0 1 4.5 6a2 2 0 0 1 2-2Z"/></svg></a>
    </header>

    <h1 class="ptitle">Поиск партнёра для игры</h1>
    <p class="psub">Найдите партнёра по уровню и времени. Откликайтесь на заявки других игроков или создайте свою и ждите откликов!</p>

    <div class="actions">
      <button class="btn-green"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M12 5v14M5 12h14"/></svg> Разместить заявку</button>
      <button class="btn-ghost"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="m10 9 5 3-5 3z"/></svg> Как это работает?</button>
    </div>

    <div class="filters">
      <div class="fgrid">
        <div class="ffield"><span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg></span><div class="fl"><div class="k">Дата</div><div class="v">28 мая, ср</div></div><span class="chev"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span></div>
        <div class="ffield"><span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span><div class="fl"><div class="k">Время</div><div class="v">Весь день</div></div><span class="chev"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span></div>
        <div class="ffield"><span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M6 20v-6M12 20V8M18 20v-9"/></svg></span><div class="fl"><div class="k">Уровень</div><div class="v">Все уровни</div></div><span class="chev"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span></div>
        <div class="ffield"><span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg></span><div class="fl"><div class="k">Клуб / локация</div><div class="v">Все клубы</div></div><span class="chev"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span></div>
      </div>
      <div class="frow">
        <button class="btn-green"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg> Поиск</button>
        <button class="btn-ghost"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="M4 12a8 8 0 1 1 2.3 5.6"/><path d="M4 20v-5h5"/></svg> Сбросить</button>
      </div>
    </div>

    <div class="tabs">
      <div class="tabx active">Доступные</div>
      <div class="tabx">Мои заявки</div>
      <div class="tabx">Мои отклики</div>
    </div>

    <div class="days">
      <div class="day"><span class="dw">ПН</span><span class="dd">26</span><span class="dc">2</span></div>
      <div class="day"><span class="dw">ВТ</span><span class="dd">27</span><span class="dc">1</span></div>
      <div class="day sel"><span class="dw">СР</span><span class="dd">28</span><span class="dc">3</span></div>
      <div class="day"><span class="dw">ЧТ</span><span class="dd">29</span><span class="dot"></span></div>
      <div class="day"><span class="dw">ПТ</span><span class="dd">30</span><span class="dc">2</span></div>
      <div class="day we"><span class="dw">СБ</span><span class="dd">31</span><span class="dc">1</span></div>
      <div class="day we"><span class="dw">ВС</span><span class="dd">1</span><span class="dc">1</span></div>
    </div>

    <div class="cards">
      <!-- 1 -->
      <div class="pcard">
        <span class="pc-new">Новое</span>
        <div class="pc-top">
          <span class="pc-av"><span class="ph-ava">МП</span></span>
          <div class="pc-id">
            <div class="pc-name">Мария П.</div>
            <div class="pc-meta"><span class="lvl lvl-mid">Средний</span><span class="ttw">TTW: отсутствует</span></div>
            <div class="pc-loc"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Топ-Спин, Новосибирск</div>
          </div>
        </div>
        <div class="pc-status"><span class="sdot ok"></span><span class="stxt">Есть свободный стол. Автоматическое бронирование</span></div>
        <div class="pc-desc">Свободна в указанное время, опыт парных игр.</div>
        <div class="pc-foot">
          <span class="pc-time">15:00–16:00</span>
          <button class="pc-act">Откликнуться</button>
          <button class="pc-chat"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></button>
        </div>
      </div>
      <!-- 2 -->
      <div class="pcard">
        <div class="pc-top">
          <span class="pc-av"><span class="ph-ava">ИН</span></span>
          <div class="pc-id">
            <div class="pc-name">Илья Н.</div>
            <div class="pc-meta"><span class="lvl lvl-adv">Продвинутый</span><span class="ttw">TTW: 760</span></div>
            <div class="pc-loc"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Топ-Спин, Новосибирск</div>
          </div>
        </div>
        <div class="pc-status"><span class="sdot busy"></span><span class="stxt">Время занято</span></div>
        <div class="pc-desc">Свободен в указанное время, ищу игру.</div>
        <div class="pc-foot">
          <span class="pc-time">15:00–16:00</span>
          <button class="pc-act done">Вы уже откликнулись</button>
          <button class="pc-chat"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></button>
        </div>
      </div>
      <!-- 3 -->
      <div class="pcard">
        <div class="pc-top">
          <span class="pc-av"><span class="ph-ava">ОР</span></span>
          <div class="pc-id">
            <div class="pc-name">Олег Р.</div>
            <div class="pc-meta"><span class="lvl lvl-adv">Продвинутый</span><span class="ttw">TTW: 350</span></div>
            <div class="pc-loc"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Топ-Спин, Новосибирск</div>
          </div>
        </div>
        <div class="pc-status"><span class="sdot off"></span><span class="stxt">Автоматическое бронирование недоступно. Клуб не подключён к системе онлайн-бронирования.</span></div>
        <div class="pc-desc">Люблю быструю атаку и активные розыгрыши.</div>
        <div class="pc-foot">
          <span class="pc-time">15:00–16:00</span>
          <button class="pc-act">Откликнуться</button>
          <button class="pc-chat"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></button>
        </div>
      </div>
      <!-- 4 -->
      <div class="pcard">
        <div class="pc-top">
          <span class="pc-av"><span class="ph-ava">АК</span></span>
          <div class="pc-id">
            <div class="pc-name">Анна К.</div>
            <div class="pc-meta"><span class="lvl lvl-mid">Средний</span><span class="ttw">TTW: отсутствует</span></div>
            <div class="pc-loc"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Арена, Новосибирск</div>
          </div>
        </div>
        <div class="pc-status"><span class="sdot ok"></span><span class="stxt">Есть свободный стол. Автоматическое бронирование</span></div>
        <div class="pc-desc">Играю в паре регулярно.</div>
        <div class="pc-foot">
          <span class="pc-time">18:00–19:00</span>
          <button class="pc-act done">Партнёр найден</button>
          <button class="pc-chat"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></button>
        </div>
      </div>
    </div>
  </div>

  <!-- TABBAR -->
  <nav class="tabbar">
    <a class="tab" href="/"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><path d="M4 11 12 5l8 6"/><path d="M6 10v9h12v-9"/></svg>Главная</a>
    <a class="tab" href="/schedule"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><rect x="3" y="9" width="18" height="6" rx="1"/><path d="M5 15v3M19 15v3M12 9v6"/></svg>Аренда</a>
    <a class="tab" href="#"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg>События</a>
    <button class="tab active"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><circle cx="9" cy="9" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 7a3 3 0 0 1 0 5M17 19a6 6 0 0 0-3-5"/></svg>Партнёр</button>
    <a class="tab" href="/coaches"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>Тренеры</a>
  </nav>

</div>
<script>
  document.querySelectorAll('.tabx').forEach(t=>t.addEventListener('click',()=>{
    document.querySelectorAll('.tabx').forEach(x=>x.classList.remove('active'));t.classList.add('active');
  }));
  document.querySelectorAll('.day').forEach(d=>d.addEventListener('click',()=>{
    document.querySelectorAll('.day').forEach(x=>x.classList.remove('sel'));d.classList.add('sel');
  }));
  document.querySelectorAll('.pc-act:not(.done)').forEach(b=>b.addEventListener('click',()=>{
    b.textContent='Отклик отправлен';b.classList.add('done');
  }));
</script>
</body>
</html>
