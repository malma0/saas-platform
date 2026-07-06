<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Теннис Клуб НСК — мобильная версия</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="{{ asset('js/device.js') }}"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  /* Аватар-заглушка вместо фото (инициалы на градиенте) */
  .ph-ava{display:grid;place-items:center;width:100%;height:100%;background:linear-gradient(135deg,#222a1e,#2e3a26);color:#c6e21a;font-weight:800;}
  :root{
    --bg:#0a0b09;
    --panel:#101210;
    --panel-2:#15171400;
    --field:#16181430;
    --line:rgba(255,255,255,.08);
    --line-soft:rgba(255,255,255,.05);
    --txt:#f0f1ec;
    --muted:#8c8f86;
    --muted-2:#6f726a;
    --green:#c6e21a;
    --green-deep:#9fb50f;
    --blue:#6fa8ec;
    --radius:16px;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  html,body{height:100%;}
  body{background:#000;font-family:'Manrope',system-ui,sans-serif;-webkit-font-smoothing:antialiased;
       display:flex;justify-content:center;}
  a{color:inherit;text-decoration:none;}
  .green{color:var(--green);}
  svg{display:block;}
  .ico{stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round;}

  /* ── app frame (центрируем как телефон на десктопе) ── */
  .app{position:relative;width:100%;max-width:430px;background:var(--bg);color:var(--txt);
       height:100vh;display:flex;flex-direction:column;overflow:hidden;}
  @media (min-width:480px){
    .app{height:min(920px,96vh);margin:auto;border-radius:30px;border:1px solid rgba(255,255,255,.1);
         box-shadow:0 30px 80px rgba(0,0,0,.6);}
  }
  .screen{flex:1;overflow-y:auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;
          padding:0 16px calc(86px + env(safe-area-inset-bottom));}
  .screen::-webkit-scrollbar{width:0;}

  /* ── header ── */
  header{display:flex;align-items:center;gap:12px;padding:18px 2px 14px;position:sticky;top:0;z-index:20;
         background:linear-gradient(180deg,var(--bg) 72%,rgba(10,11,9,0));}
  .logo{display:flex;align-items:center;gap:11px;flex:1;min-width:0;}
  .logo-mark{width:40px;height:40px;flex:none;}
  .brand{font-size:17px;font-weight:800;letter-spacing:.3px;line-height:1.05;}
  .sub{font-size:10.5px;color:var(--muted);margin-top:3px;line-height:1.3;}
  .head-actions{display:flex;gap:9px;flex:none;}
  .hbtn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;
        width:62px;height:54px;border-radius:14px;background:rgba(255,255,255,.04);
        border:1px solid var(--line);color:var(--txt);}
  .hbtn svg{color:var(--green);}
  .hbtn span{font-size:11px;font-weight:600;}

  /* ── booking card ── */
  .booking{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);
           padding:20px 18px;margin-top:4px;}
  .booking h2{font-size:25px;font-weight:800;letter-spacing:-.3px;}
  .booking .bsub{font-size:13px;color:var(--muted);margin-top:6px;}
  .field{display:flex;align-items:center;gap:13px;background:rgba(255,255,255,.04);
         border:1px solid var(--line);border-radius:12px;padding:13px 15px;margin-top:12px;cursor:pointer;
         transition:border-color .15s;}
  .field:active{border-color:rgba(198,226,26,.5);}
  .field .fi{color:var(--green);flex:none;}
  .field .ft{flex:1;min-width:0;}
  .field .ft .k{font-size:11px;color:var(--muted);}
  .field .ft .v{font-size:15.5px;font-weight:600;margin-top:2px;}
  .field .chev{color:var(--muted-2);flex:none;}
  .btn-green{display:flex;align-items:center;justify-content:center;width:100%;background:var(--green);
             color:#13160a;font-weight:800;font-size:15px;border:none;border-radius:12px;padding:16px;
             margin-top:16px;cursor:pointer;letter-spacing:.1px;font-family:inherit;}
  .btn-green:active{filter:brightness(.94);}
  .note{display:flex;align-items:center;justify-content:center;gap:8px;font-size:12.5px;color:var(--muted);margin-top:14px;}
  .note .d{width:6px;height:6px;border-radius:50%;background:var(--green);flex:none;}

  /* ── stats row ── */
  .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:14px;}
  .stat{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:14px 10px;
        display:flex;flex-direction:column;align-items:center;text-align:center;gap:7px;}
  .stat .sico{color:var(--green);}
  .stat b{font-size:19px;font-weight:800;line-height:1;}
  .stat .ssm{font-size:14px;font-weight:800;line-height:1.1;}
  .stat span{font-size:11px;color:var(--muted);line-height:1.2;}

  /* ── section block ── */
  .block{margin-top:22px;}
  .block-head{display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:12px;padding:0 2px;}
  .block-head .ht{display:flex;flex-direction:column;gap:3px;}
  .block-head h3{font-size:19px;font-weight:800;letter-spacing:-.2px;}
  .block-head .hsub{font-size:12px;color:var(--muted);}
  .link-arrow{display:inline-flex;align-items:center;gap:5px;color:var(--green);font-size:13px;font-weight:700;white-space:nowrap;flex:none;}

  .list{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;}
  .list > * + *{border-top:1px solid var(--line-soft);}

  /* event row */
  .ev{display:flex;align-items:stretch;gap:13px;padding:15px 15px;}
  .ev .day{flex:none;width:48px;display:flex;flex-direction:column;align-items:flex-start;gap:2px;
           border-right:1px solid var(--line);padding-right:12px;}
  .ev .day .wd{font-size:15px;font-weight:800;letter-spacing:.5px;}
  .ev .day .dt{font-size:10.5px;color:var(--muted);}
  .ev .day .tm{font-size:13px;font-weight:700;color:var(--green);margin-top:2px;}
  .ev .ei{flex:1;min-width:0;}
  .ev .ei .en{font-size:14.5px;font-weight:600;line-height:1.25;}
  .ev .ei .loc{display:flex;align-items:center;gap:5px;font-size:11.5px;color:var(--muted);margin-top:6px;}
  .ev .ei .loc svg{color:var(--green-deep);flex:none;}
  .ev .er{flex:none;display:flex;flex-direction:column;align-items:flex-end;gap:4px;justify-content:center;}
  .ev .er .pr{font-size:14px;font-weight:800;}
  .ev .er .pt{font-size:11.5px;color:var(--muted);}
  .ev .er .ch{color:var(--muted-2);margin-top:2px;}

  /* partner row */
  .pr-row{display:flex;align-items:center;gap:12px;padding:12px 15px;}
  .pr-row .av{width:46px;height:46px;border-radius:50%;flex:none;overflow:hidden;border:1px solid var(--line);}
  .pr-row .pi{flex:1;min-width:0;}
  .pr-row .pi .pn{font-size:15px;font-weight:700;}
  .pr-row .pi .pl{display:flex;align-items:center;gap:5px;font-size:11.5px;color:var(--muted);margin-top:4px;}
  .pr-row .pi .pl svg{color:var(--green-deep);flex:none;}
  .pr-row .pright{flex:none;display:flex;flex-direction:column;align-items:flex-end;gap:6px;}
  .pr-row .pright .ptime{font-size:12.5px;font-weight:700;}
  .pr-row .pright .pday{font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:7px;
        background:rgba(198,226,26,.15);color:var(--green);border:1px solid rgba(198,226,26,.4);white-space:nowrap;}
  .pr-row .pright .pday.dim{background:rgba(255,255,255,.05);color:var(--muted);border-color:var(--line);}
  .pr-row .ch{color:var(--muted-2);flex:none;}

  .add-btn{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;margin-top:12px;
           background:rgba(255,255,255,.04);border:1px solid var(--line);border-radius:13px;padding:14px;
           color:var(--txt);font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;}
  .add-btn svg{color:var(--green);}

  /* coach card */
  .coach{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:16px;}
  .coach-top{display:flex;gap:14px;}
  .coach .cphoto{width:96px;height:104px;border-radius:13px;flex:none;overflow:hidden;border:1px solid var(--line);}
  .coach .cinfo{flex:1;min-width:0;display:flex;flex-direction:column;}
  .coach .cname-row{display:flex;align-items:center;gap:9px;}
  .coach .cname{font-size:18px;font-weight:800;}
  .coach .ms{font-size:11px;font-weight:800;padding:3px 9px;border-radius:7px;
        background:rgba(198,226,26,.15);color:var(--green);border:1px solid rgba(198,226,26,.4);}
  .coach ul{list-style:none;margin-top:11px;display:flex;flex-direction:column;gap:7px;}
  .coach ul li{display:flex;align-items:center;gap:8px;font-size:13px;color:#c8cabf;}
  .coach ul li svg{color:var(--green);flex:none;}
  .coach .price{margin-top:12px;font-size:18px;font-weight:800;}
  .coach .price span{font-size:13px;font-weight:500;color:var(--muted);}
  .coach .btn-green{margin-top:14px;}

  /* ── bottom tabbar ── */
  .tabbar{position:fixed;left:0;right:0;bottom:0;z-index:100;display:flex;
          background:rgba(13,14,12,.96);-webkit-backdrop-filter:blur(14px);backdrop-filter:blur(14px);
          border-top:1px solid var(--line);padding:9px 4px calc(9px + env(safe-area-inset-bottom));}
  .tab{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;color:var(--muted);
       font-size:9.5px;font-weight:600;padding:2px 0;background:none;border:none;font-family:inherit;cursor:pointer;
       white-space:nowrap;overflow:hidden;}
  .tab.active{color:var(--green);}
  .tab svg{display:block;flex:none;}
</style>
</head>
<body>
<div class="app">

  <div class="screen" data-screen-label="Главная (мобильная)">

    <!-- HEADER -->
    <header>
      <div class="logo">
        <svg class="logo-mark" viewBox="0 0 48 48">
          <g transform="rotate(-32 24 24)">
            <ellipse cx="18" cy="17" rx="13" ry="14" fill="#1d1f1a" stroke="rgba(255,255,255,.18)" stroke-width="1"/>
            <rect x="15.5" y="29" width="5" height="13" rx="2.5" fill="#1d1f1a"/>
          </g>
          <g transform="rotate(28 30 22)">
            <ellipse cx="30" cy="17" rx="13" ry="14" fill="#c6e21a"/>
            <rect x="27.5" y="29" width="5" height="13" rx="2.5" fill="#c6e21a"/>
          </g>
          <circle cx="14" cy="11" r="3.4" fill="#f0f1ec"/>
        </svg>
        <div>
          <div class="brand">ТЕННИС КЛУБ <span class="green">НСК</span></div>
          <div class="sub">Премиальный клуб<br>настольного тенниса</div>
        </div>
      </div>
      <div class="head-actions">
        <a class="hbtn" href="#">
          <svg width="20" height="20" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg>
          <span>Адрес</span>
        </a>
        <a class="hbtn" href="tel:+73832078620">
          <svg width="20" height="20" viewBox="0 0 24 24" class="ico"><path d="M6.5 4h3l1.5 4-2 1.5a11 11 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2A16 16 0 0 1 4.5 6a2 2 0 0 1 2-2Z"/></svg>
          <span>Позвонить</span>
        </a>
      </div>
    </header>

    <!-- BOOKING -->
    <section class="booking">
      <h2>Аренда столов</h2>
      <div class="bsub">Найдите и забронируйте стол онлайн за 1 минуту</div>
      <div class="field">
        <span class="fi"><svg width="22" height="22" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg></span>
        <div class="ft"><div class="k">Дата</div><div class="v">24 мая, сб</div></div>
        <span class="chev"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span>
      </div>
      <div class="field">
        <span class="fi"><svg width="22" height="22" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>
        <div class="ft"><div class="k">Время</div><div class="v">18:00</div></div>
        <span class="chev"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span>
      </div>
      <div class="field">
        <span class="fi"><svg width="22" height="22" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="13" r="8"/><path d="M12 9v4M9 2h6"/></svg></span>
        <div class="ft"><div class="k">Длительность</div><div class="v">1 час</div></div>
        <span class="chev"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span>
      </div>
      <button class="btn-green" onclick="location.href='/schedule'">Найти и забронировать стол онлайн</button>
      <div class="note"><span class="d"></span> Онлайн-бронирование без звонка</div>
    </section>

    <!-- STATS -->
    <div class="stats">
      <div class="stat">
        <span class="sico"><svg width="26" height="26" viewBox="0 0 24 24" class="ico"><path d="M4 11 12 5l8 6"/><path d="M6 10v9h12v-9"/></svg></span>
        <b>3</b><span>клуба</span>
      </div>
      <div class="stat">
        <span class="sico"><svg width="26" height="26" viewBox="0 0 24 24" class="ico"><rect x="3" y="8" width="18" height="6" rx="1"/><path d="M5 14v4M19 14v4M12 8v6"/></svg></span>
        <b>20</b><span>столов</span>
      </div>
      <div class="stat">
        <span class="sico"><svg width="26" height="26" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>
        <b class="ssm">10:00–22:00</b><span>ежедневно</span>
      </div>
    </div>

    <!-- EVENTS -->
    <section class="block">
      <div class="block-head">
        <div class="ht"><h3>Ближайшие события</h3></div>
        <a class="link-arrow" href="#">Все события <svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
      </div>
      <div class="list">
        <a class="ev" href="#">
          <div class="day"><span class="wd">СБ</span><span class="dt">24 мая</span><span class="tm">11:00</span></div>
          <div class="ei">
            <div class="en">Кубок клуба. Весна 2024</div>
            <div class="loc"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Центральный клуб · Красный пр-т, 2/1</div>
          </div>
          <div class="er"><span class="pr">800 ₽</span><span class="pt">24 / 32</span><span class="ch"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span></div>
        </a>
        <a class="ev" href="#">
          <div class="day"><span class="wd">ВС</span><span class="dt">25 мая</span><span class="tm">18:00</span></div>
          <div class="ei">
            <div class="en">Лига выходного дня</div>
            <div class="loc"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Клуб на Красном · Красный пр-т, 2/1</div>
          </div>
          <div class="er"><span class="pr">600 ₽</span><span class="pt">10 / 16</span><span class="ch"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span></div>
        </a>
        <a class="ev" href="#">
          <div class="day"><span class="wd">ВТ</span><span class="dt">27 мая</span><span class="tm">19:00</span></div>
          <div class="ei">
            <div class="en">Тренировка с тренером</div>
            <div class="loc"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Северный клуб · ул. Гагаринская, 18</div>
          </div>
          <div class="er"><span class="pr">1 500 ₽</span><span class="pt">6 / 8</span><span class="ch"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span></div>
        </a>
      </div>
    </section>

    <!-- PARTNERS -->
    <section class="block">
      <div class="block-head">
        <div class="ht"><h3>Поиск партнёра</h3><div class="hsub">12 игроков ищут напарника</div></div>
        <a class="link-arrow" href="#">Все <svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
      </div>
      <div class="list">
        <a class="pr-row" href="#">
          <span class="av"><span class="ph-ava" style="font-size:15px">АП</span></span>
          <div class="pi"><div class="pn">Алексей П.</div><div class="pl"><svg width="12" height="12" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Клуб на Красном</div></div>
          <div class="pright"><span class="ptime">19:00–20:00</span><span class="pday">Сегодня</span></div>
          <span class="ch"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span>
        </a>
        <a class="pr-row" href="#">
          <span class="av"><span class="ph-ava" style="font-size:15px">МС</span></span>
          <div class="pi"><div class="pn">Мария С.</div><div class="pl"><svg width="12" height="12" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Центральный клуб</div></div>
          <div class="pright"><span class="ptime">18:00–19:30</span><span class="pday">Завтра</span></div>
          <span class="ch"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span>
        </a>
        <a class="pr-row" href="#">
          <span class="av"><span class="ph-ava" style="font-size:15px">ДК</span></span>
          <div class="pi"><div class="pn">Дмитрий К.</div><div class="pl"><svg width="12" height="12" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Северный клуб</div></div>
          <div class="pright"><span class="ptime">17:00–18:30</span><span class="pday dim">26 мая, пн</span></div>
          <span class="ch"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span>
        </a>
        <a class="pr-row" href="#">
          <span class="av"><span class="ph-ava" style="font-size:15px">ЕЛ</span></span>
          <div class="pi"><div class="pn">Екатерина Л.</div><div class="pl"><svg width="12" height="12" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg> Клуб на Красном</div></div>
          <div class="pright"><span class="ptime">20:00–21:30</span><span class="pday dim">27 мая, вт</span></div>
          <span class="ch"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m9 6 6 6-6 6"/></svg></span>
        </a>
      </div>
      <button class="add-btn"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg> Разместить своё объявление</button>
    </section>

    <!-- COACH -->
    <section class="block">
      <div class="block-head">
        <div class="ht"><h3>Персональная тренировка</h3></div>
        <a class="link-arrow" href="#">Все тренеры <svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
      </div>
      <div class="coach">
        <div class="coach-top">
          <span class="cphoto"><span class="ph-ava" style="font-size:22px;border-radius:13px">ИП</span></span>
          <div class="cinfo">
            <div class="cname-row"><span class="cname">Иван П.</span><span class="ms">МС</span></div>
            <ul>
              <li><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg> Стаж 10+ лет</li>
              <li><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg> Техника и тактика</li>
            </ul>
            <div class="price">1 800 ₽ <span>/ 60 мин</span></div>
          </div>
        </div>
        <button class="btn-green" onclick="location.href='/coaches'">Записаться на тренировку</button>
      </div>
    </section>

  </div>

  <!-- TABBAR -->
  <nav class="tabbar">
    <button class="tab active">
      <svg width="24" height="24" viewBox="0 0 24 24" class="ico"><path d="M4 11 12 5l8 6"/><path d="M6 10v9h12v-9"/></svg>
      Главная
    </button>
    <a class="tab" href="/schedule">
      <svg width="24" height="24" viewBox="0 0 24 24" class="ico"><rect x="3" y="9" width="18" height="6" rx="1"/><path d="M5 15v3M19 15v3M12 9v6"/></svg>
      Аренда
    </a>
    <a class="tab" href="#">
      <svg width="24" height="24" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg>
      События
    </a>
    <a class="tab" href="/partners">
      <svg width="24" height="24" viewBox="0 0 24 24" class="ico"><circle cx="9" cy="9" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 7a3 3 0 0 1 0 5M17 19a6 6 0 0 0-3-5"/></svg>
      Партнёр
    </a>
    <a class="tab" href="/coaches">
      <svg width="24" height="24" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
      Тренеры
    </a>
  </nav>

</div>
</body>
</html>
