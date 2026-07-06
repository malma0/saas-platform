@php
  // Брони десктоп-формата {row,type,title,start,end} → мобильный {col,type,title,s,e}
  $mobileEvents = collect($events ?? [])->map(fn ($e) => [
    'col'   => $e['row'],
    'type'  => $e['type'],
    'title' => $e['title'],
    's'     => $e['start'],
    'e'     => $e['end'],
  ])->values();

  // Подпись даты (как на десктопе)
  $mm = ['', 'янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
  $dw = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
  $ts = strtotime($scheduleDate ?? 'today');
  $mobileDateLabel = date('j', $ts) . ' ' . $mm[(int) date('n', $ts)] . ', ' . $dw[(int) date('w', $ts)];
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Аренда столов — Теннис Клуб НСК</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="{{ asset('js/device.js') }}"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  :root{
    --bg:#0a0b09;--panel:#101210;--field:rgba(255,255,255,.04);
    --line:rgba(255,255,255,.08);--line-soft:rgba(255,255,255,.05);
    --txt:#f0f1ec;--muted:#8c8f86;--muted-2:#6f726a;
    --green:#c6e21a;--green-deep:#9fb50f;--radius:16px;
    --tw:52px;--cw:96px;
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

  /* header */
  header{display:flex;align-items:center;gap:12px;padding:16px 2px 12px;position:sticky;top:0;z-index:20;background:linear-gradient(180deg,var(--bg) 76%,rgba(10,11,9,0));}
  .logo{display:flex;align-items:center;gap:11px;flex:1;min-width:0;}
  .logo-mark{width:38px;height:38px;flex:none;}
  .brand{font-size:16px;font-weight:800;letter-spacing:.3px;line-height:1.05;}
  .sub{font-size:10px;color:var(--muted);margin-top:3px;}
  .hicon{width:46px;height:46px;border-radius:13px;background:var(--field);border:1px solid var(--line);display:grid;place-items:center;color:var(--txt);flex:none;}
  .hicon svg{color:var(--green);}

  .ptitle{font-size:25px;font-weight:800;letter-spacing:-.3px;margin-top:6px;}
  .psub{font-size:13px;color:var(--muted);margin-top:6px;}

  /* controls */
  .controls{display:flex;gap:9px;margin-top:16px;}
  .ctrl{flex:1;display:flex;align-items:center;gap:10px;background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:11px 13px;cursor:pointer;min-width:0;}
  .ctrl .ci{color:var(--green);flex:none;}
  .ctrl .cl{flex:1;min-width:0;}
  .ctrl .cl .k{font-size:10px;color:var(--muted);}
  .ctrl .cl .v{font-size:14px;font-weight:700;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .ctrl .chev{color:var(--muted-2);flex:none;}
  .vtoggle{display:flex;flex:none;background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:4px;gap:2px;}
  .vt{padding:8px 12px;border-radius:9px;font-size:13px;font-weight:700;color:var(--muted);cursor:pointer;}
  .vt.active{background:rgba(255,255,255,.09);color:var(--txt);}
  .filter-btn{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;margin-top:10px;background:var(--field);border:1px solid var(--line);border-radius:12px;padding:13px;color:var(--txt);font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;}
  .filter-btn svg{color:var(--green);}

  /* grid (horizontally scrollable — 12 столов) */
  .gridwrap{margin-top:14px;background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;}
  .gscroll{overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;}
  .gscroll::-webkit-scrollbar{height:6px;}
  .gscroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.18);border-radius:3px;}
  .gscroll::-webkit-scrollbar-track{background:transparent;}
  .gtable{position:relative;}
  .ghead{display:grid;grid-template-columns:var(--tw) repeat(12,var(--cw));border-bottom:1px solid var(--line);}
  .ghead .gh{padding:11px 4px;text-align:center;font-size:11.5px;font-weight:700;color:var(--muted);display:flex;align-items:center;justify-content:center;gap:4px;white-space:nowrap;}
  .ghead .gh:first-child{justify-content:flex-start;padding-left:10px;position:sticky;left:0;z-index:7;background:var(--panel);}
  .ghead .gh svg{color:var(--muted-2);flex:none;}
  .gbody{position:relative;}
  .gcols{position:absolute;inset:0;display:grid;grid-template-columns:var(--tw) repeat(12,var(--cw));pointer-events:none;}
  .gcols .gc{border-right:1px solid var(--line-soft);}
  .gcols .gc:first-child{border-right:1px solid var(--line);}
  .gcols .gc:last-child{border-right:none;}
  .grows{position:relative;}
  .grow{display:grid;grid-template-columns:var(--tw) repeat(12,var(--cw));}
  .grow .gt{height:46px;display:flex;align-items:flex-start;justify-content:flex-end;padding:4px 8px 0 0;font-size:11px;color:var(--muted);border-top:1px solid var(--line-soft);position:sticky;left:0;z-index:6;background:var(--panel);}
  .grow:first-child .gt{border-top:none;}
  .grow .gcell{border-top:1px solid var(--line-soft);}
  .grow:first-child .gcell{border-top:none;}
  .gevents{position:absolute;top:0;left:var(--tw);right:0;bottom:0;pointer-events:none;}
  .swipe-hint{display:flex;align-items:center;justify-content:center;gap:8px;font-size:11.5px;color:var(--muted-2);padding:9px 0 11px;border-top:1px solid var(--line-soft);}
  .swipe-hint svg{color:var(--green-deep);}
  .ev{position:absolute;border-radius:9px;padding:6px 8px;overflow:hidden;pointer-events:auto;cursor:pointer;display:flex;flex-direction:column;gap:3px;}
  .ev .et{font-size:11px;font-weight:700;line-height:1.15;}
  .ev .etm{font-size:10px;font-weight:500;opacity:.8;margin-top:auto;}
  .ev .pic{align-self:flex-end;margin-top:auto;opacity:.8;}
  .ev-ind   {background:rgba(42,90,175,.5);border:1px solid rgba(80,144,224,.4);color:#bcd3f5;}
  .ev-group {background:rgba(80,46,148,.54);border:1px solid rgba(118,82,198,.42);color:#d0c4f8;}
  .ev-club  {background:rgba(32,90,46,.6);border:1px solid rgba(55,132,72,.44);color:#aee8be;}
  .ev-tournament{background:rgba(112,56,14,.64);border:1px solid rgba(164,92,32,.5);color:#f0c090;}
  .ev-private{background:rgba(52,55,50,.72);border:1px solid rgba(76,80,74,.5);color:#b2b5ae;}
  .ev-partner{background:rgba(12,18,8,.82);border:1.5px dashed rgba(198,226,26,.6);color:#d0e285;align-items:center;justify-content:center;}
  .ev-partner .et{text-align:center;}
  .ev-sel{background:rgba(198,226,26,.08);border:1.5px solid var(--green);}
  .nowline{position:absolute;left:0;right:0;height:0;border-top:1.5px solid var(--green);z-index:5;pointer-events:none;}
  .nowline .nl-lbl{position:sticky;left:4px;display:inline-block;margin-top:-8px;font-size:10px;font-weight:700;color:var(--green);background:var(--panel);padding:0 3px;z-index:8;}
  .nowline .nl-dot{position:absolute;left:calc(var(--tw) - 2px);top:-4px;width:8px;height:8px;border-radius:50%;background:var(--green);}

  /* booking form */
  .bform{margin-top:18px;background:var(--panel);border:1px solid rgba(198,226,26,.3);border-radius:var(--radius);padding:18px;}
  .bform h3{font-size:18px;font-weight:800;margin-bottom:14px;}
  .bsummary{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;}
  .bs-cell{background:var(--field);border:1px solid var(--line);border-radius:11px;padding:10px 12px;display:flex;align-items:center;gap:9px;}
  .bs-cell svg{color:var(--green);flex:none;}
  .bs-cell .bv{font-size:14px;font-weight:700;}
  .bs-cell .bv.price{color:var(--green);}
  .binput{width:100%;background:var(--field);border:1px solid var(--line);border-radius:11px;padding:13px 14px;color:var(--txt);font-size:15px;font-family:inherit;margin-bottom:10px;}
  .binput::placeholder{color:var(--muted-2);}
  .binput-row{display:flex;align-items:center;gap:10px;background:var(--field);border:1px solid var(--line);border-radius:11px;padding:0 14px;margin-bottom:10px;}
  .binput-row svg{color:var(--muted);flex:none;}
  .binput-row input{flex:1;background:none;border:none;padding:13px 0;color:var(--txt);font-size:15px;font-family:inherit;outline:none;}
  .binput-row input::placeholder{color:var(--muted-2);}
  .btn-green{display:flex;align-items:center;justify-content:center;width:100%;background:var(--green);color:#13160a;font-weight:800;font-size:15px;border:none;border-radius:12px;padding:15px;cursor:pointer;font-family:inherit;}
  .btn-green:active{filter:brightness(.94);}
  .bnote{display:flex;align-items:center;justify-content:center;gap:7px;font-size:11.5px;color:var(--muted);margin-top:12px;}

  /* legend */
  .legend{margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:9px 14px;padding:0 2px;}
  .lg{display:flex;align-items:center;gap:8px;font-size:12px;color:#a6a8a1;}
  .ld{width:15px;height:15px;border-radius:4px;flex:none;}
  .ld-free{border:1.5px solid rgba(255,255,255,.22);}
  .ld-part{border:1.5px dashed rgba(198,226,26,.55);}
  .ld-ind{background:rgba(42,90,175,.5);border:1px solid rgba(80,144,224,.4);}
  .ld-grp{background:rgba(80,46,148,.54);border:1px solid rgba(118,82,198,.42);}
  .ld-club{background:rgba(32,90,46,.6);border:1px solid rgba(55,132,72,.44);}
  .ld-tourn{background:rgba(112,56,14,.64);border:1px solid rgba(164,92,32,.5);}
  .ld-priv{background:rgba(52,55,50,.72);border:1px solid rgba(76,80,74,.5);}

  /* tabbar */
  .tabbar{position:fixed;left:0;right:0;bottom:0;z-index:100;display:flex;background:rgba(13,14,12,.96);-webkit-backdrop-filter:blur(14px);backdrop-filter:blur(14px);border-top:1px solid var(--line);padding:9px 4px calc(9px + env(safe-area-inset-bottom));}
  .tab{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;color:var(--muted);font-size:9.5px;font-weight:600;padding:2px 0;background:none;border:none;font-family:inherit;cursor:pointer;white-space:nowrap;overflow:hidden;}
  .tab svg{flex:none;}
  .tab.active{color:var(--green);}

  /* bottom sheet */
  .sheet-overlay{position:absolute;inset:0;z-index:40;background:rgba(4,5,3,.6);display:none;}
  .sheet-overlay.open{display:block;}
  .sheet{position:absolute;left:0;right:0;bottom:0;z-index:41;background:#13150f;border-top:1px solid var(--line);border-radius:22px 22px 0 0;padding:20px 18px calc(20px + env(safe-area-inset-bottom));transform:translateY(100%);transition:transform .26s cubic-bezier(.2,.7,.3,1);max-height:90%;overflow-y:auto;}
  .sheet.open{transform:none;}
  .sheet-grab{width:38px;height:4px;border-radius:2px;background:rgba(255,255,255,.2);margin:0 auto 14px;}
  .sheet-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;}
  .sheet-head h3{font-size:19px;font-weight:800;}
  .sheet-x{width:34px;height:34px;border-radius:10px;border:1px solid var(--line);background:var(--field);color:var(--muted);display:grid;place-items:center;flex:none;cursor:pointer;}
  .srow{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--line-soft);}
  .srow .sk{font-size:13.5px;color:var(--muted);}
  .srow .sv{font-size:15px;font-weight:700;}
  .srow .sv.price{color:var(--green);}
  .sblk{padding:14px 0;border-bottom:1px solid var(--line-soft);}
  .sblk .slbl{font-size:12.5px;color:var(--muted);margin-bottom:10px;}
  .durs{display:flex;gap:8px;}
  .dur{flex:1;text-align:center;padding:11px 0;border-radius:10px;background:var(--field);border:1px solid var(--line);font-size:14px;font-weight:700;color:var(--muted);cursor:pointer;}
  .dur.active{background:rgba(198,226,26,.14);border-color:var(--green);color:var(--green);}
  .dur.plus{flex:0 0 50px;color:var(--green);font-size:20px;}
  .stotal{display:flex;align-items:center;justify-content:space-between;padding:16px 0;}
  .stotal .stk{font-size:16px;font-weight:600;}
  .stotal .stv{font-size:23px;font-weight:800;color:var(--green);}

  /* confirmation */
  .confirm{position:absolute;inset:0;z-index:50;background:var(--bg);display:none;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:30px 26px calc(30px + env(safe-area-inset-bottom));}
  .confirm.open{display:flex;}
  .cf-ico{width:96px;height:96px;border-radius:50%;background:rgba(198,226,26,.12);border:2px solid var(--green);display:grid;place-items:center;color:var(--green);margin-bottom:24px;}
  .confirm h2{font-size:26px;font-weight:800;}
  .cf-card{margin-top:18px;background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:16px 20px;width:100%;}
  .cf-card .cft{font-size:17px;font-weight:800;}
  .cf-card .cfd{font-size:14px;color:var(--muted);margin-top:6px;}
  .cf-sms{font-size:13px;color:var(--muted);margin-top:18px;line-height:1.5;}
  .cf-actions{margin-top:24px;width:100%;display:flex;flex-direction:column;gap:11px;}
  .btn-ghost{width:100%;background:transparent;border:1.5px solid rgba(198,226,26,.5);color:var(--green);font-weight:700;font-size:15px;border-radius:12px;padding:14px;cursor:pointer;font-family:inherit;}
</style>
</head>
<body>
<div class="app">

  <div class="screen" data-screen-label="Аренда столов (мобильная)">
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

    <h1 class="ptitle">Бронирование стола</h1>
    <p class="psub">Выберите удобное время и забронируйте стол для игры</p>

    <div class="controls">
      <div class="ctrl" style="position:relative;">
        <span class="ci"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg></span>
        <div class="cl"><div class="k">Дата</div><div class="v">{{ $mobileDateLabel }}</div></div>
        <span class="chev"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span>
        <input type="date" id="m-date" value="{{ $scheduleDate }}" style="position:absolute;inset:0;opacity:0;width:100%;height:100%;border:none;">
      </div>
      <div class="ctrl" style="flex:0 0 132px;">
        <span class="ci"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>
        <div class="cl"><div class="k">Время с</div><div class="v">09:00</div></div>
        <span class="chev"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span>
      </div>
    </div>
    <div class="controls" style="margin-top:10px;align-items:center;">
      <span style="font-size:12px;color:var(--muted);padding-left:2px;">Вид</span>
      <div class="vtoggle" style="margin-left:auto;"><div class="vt active">Сетка</div><div class="vt">Список</div></div>
    </div>
    <button class="filter-btn"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><path d="M4 6h16M7 12h10M10 18h4"/></svg> Фильтры</button>

    <!-- GRID -->
    <div class="gridwrap">
      <div class="gscroll" id="gscroll">
        <div class="gtable" id="gtable">
          <div class="ghead" id="ghead">
            <div class="gh">Время</div>
          </div>
          <div class="gbody" id="gbody">
            <div class="gcols" id="gcols"></div>
            <div class="grows" id="grows"></div>
            <div class="gevents" id="gevents"></div>
          </div>
        </div>
      </div>
      <div class="swipe-hint"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="M9 6 3 12l6 6M15 6l6 6-6 6"/></svg> Смахните, чтобы увидеть все 12 столов</div>
    </div>

    <!-- FORM -->
    <div class="bform">
      <h3>Оформление бронирования</h3>
      <div class="bsummary">
        <div class="bs-cell"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><rect x="3" y="9" width="18" height="5" rx="1"/><path d="M6 14v4M18 14v4"/></svg><span class="bv" id="f-table">Стол 3</span></div>
        <div class="bs-cell"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg><span class="bv" id="f-time">19:30 – 20:30</span></div>
        <div class="bs-cell"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="13" r="8"/><path d="M12 9v4M9 2h6"/></svg><span class="bv" id="f-dur">1 час</span></div>
        <div class="bs-cell"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/></svg><span class="bv price" id="f-price">500 ₽</span></div>
      </div>
      <div class="binput-row"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg><input type="text" placeholder="Ваше имя"></div>
      <div class="binput-row"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M6.5 4h3l1.5 4-2 1.5a11 11 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2A16 16 0 0 1 4.5 6a2 2 0 0 1 2-2Z"/></svg><input type="tel" placeholder="Телефон"></div>
      <button class="btn-green" id="form-book">Забронировать</button>
      <div class="bnote"><svg width="13" height="13" viewBox="0 0 24 24" class="ico"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg> Мы не передаём ваши данные третьим лицам</div>
    </div>

    <!-- LEGEND -->
    <div class="legend">
      <div class="lg"><span class="ld ld-free"></span>Свободно</div>
      <div class="lg"><span class="ld ld-part"></span>Ищу партнёра</div>
      <div class="lg"><span class="ld ld-ind"></span>Инд. тренировка</div>
      <div class="lg"><span class="ld ld-grp"></span>Групповая тренировка</div>
      <div class="lg"><span class="ld ld-club"></span>Клубное мероприятие</div>
      <div class="lg"><span class="ld ld-tourn"></span>Турнир / матч</div>
      <div class="lg"><span class="ld ld-priv"></span>Частное бронирование</div>
    </div>
  </div>

  <!-- TABBAR -->
  <nav class="tabbar">
    <a class="tab" href="/"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><path d="M4 11 12 5l8 6"/><path d="M6 10v9h12v-9"/></svg>Главная</a>
    <button class="tab active"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><rect x="3" y="9" width="18" height="6" rx="1"/><path d="M5 15v3M19 15v3M12 9v6"/></svg>Аренда</button>
    <a class="tab" href="#"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg>События</a>
    <a class="tab" href="/partners"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><circle cx="9" cy="9" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 7a3 3 0 0 1 0 5M17 19a6 6 0 0 0-3-5"/></svg>Партнёр</a>
    <a class="tab" href="/coaches"><svg width="24" height="24" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>Тренеры</a>
  </nav>

  <!-- BOTTOM SHEET -->
  <div class="sheet-overlay" id="sheet-overlay"></div>
  <div class="sheet" id="sheet">
    <div class="sheet-grab"></div>
    <div class="sheet-head"><h3>Вы выбрали время</h3><button class="sheet-x" id="sheet-x"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div>
    <div class="srow"><span class="sk" id="sh-table">Стол 3</span><span class="sv price" id="sh-price">500 ₽</span></div>
    <div class="srow"><span class="sk">{{ $mobileDateLabel }}</span><span class="sv" id="sh-time">19:30 – 20:30 (1 час)</span></div>
    <div class="sblk">
      <div class="slbl">Длительность</div>
      <div class="durs">
        <div class="dur active" data-d="60">1 час</div>
        <div class="dur" data-d="90">1.5 ч</div>
        <div class="dur" data-d="120">2 ч</div>
        <div class="dur plus" data-d="plus">+</div>
      </div>
    </div>
    <div class="sblk" style="border:none;">
      <div class="slbl">Ваши данные</div>
      <input class="binput" type="text" placeholder="Введите ваше имя">
      <div class="binput-row" style="margin-bottom:0;"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M6.5 4h3l1.5 4-2 1.5a11 11 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2A16 16 0 0 1 4.5 6a2 2 0 0 1 2-2Z"/></svg><input type="tel" placeholder="+7 (___) ___-__-__"></div>
    </div>
    <div class="stotal"><span class="stk">Итого</span><span class="stv" id="sh-total">500 ₽</span></div>
    <button class="btn-green" id="sheet-book">Забронировать</button>
    <div class="bnote" style="margin-top:12px;">Минимальная бронь — 1 час. Бесплатная отмена за 3 часа.</div>
  </div>

  <!-- CONFIRMATION -->
  <div class="confirm" id="confirm">
    <div class="cf-ico"><svg width="48" height="48" viewBox="0 0 24 24" class="ico" style="stroke-width:2"><path d="m5 12 5 5 9-11"/></svg></div>
    <h2>Бронь подтверждена!</h2>
    <div class="cf-card"><div class="cft" id="cf-table">Стол 3</div><div class="cfd" id="cf-info">24 мая, сб · 19:30 – 20:30 (1 час)</div></div>
    <div class="cf-sms">На ваш телефон отправлено SMS<br>с подтверждением брони</div>
    <div class="cf-actions">
      <button class="btn-green">Перейти к моим броням</button>
      <button class="btn-ghost" id="cf-continue">Продолжить бронирование</button>
    </div>
  </div>

</div>

<script>
(function(){
  // ── Реальные данные из бэкенда ──
  const RATE=@json($ratePerHour ?? 500), START=9, END=23, ROW=46, CW=96, TW=52;
  const TABLE_IDS   = @json($tableIds ?? []);
  const TABLE_NAMES = @json($tableNames ?? []);
  const NCOL = Math.max(TABLE_NAMES.length, 1);
  const BRANCH_ID   = @json($branchId ?? null);
  const SERVICE_ID  = @json($serviceId ?? null);
  const BOOK_DATE   = @json($scheduleDate ?? null);
  const MAX_BOOK_DATE = @json($maxBookDate ?? null);
  const ADMIN_PHONE = @json($adminPhone ?? '');
  const DATE_LABEL  = @json($mobileDateLabel ?? '');
  const TZ          = @json($branch?->timezone ?? 'UTC');

  // Текущие дата и минуты в часовом поясе филиала (для линии «сейчас»).
  function branchNow(){
    try{
      const p = new Intl.DateTimeFormat('en-CA',{timeZone:TZ,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date());
      const g = t => (p.find(x=>x.type===t)||{}).value;
      return { date:`${g('year')}-${g('month')}-${g('day')}`, min:(+g('hour'))*60 + (+g('minute')) };
    }catch(e){ const d=new Date(); return { date:'', min:d.getHours()*60+d.getMinutes() }; }
  }
  const CSRF = (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
  const esc = s => String(s==null?'':s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

  const toMin=t=>{const[h,m]=t.split(':').map(Number);return h*60+m;};
  const toStr=m=>String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0');
  const top=t=>((toMin(t)-START*60)/30)*ROW;
  const plural=(n,a,b,c)=>{const x=n%10,y=n%100;if(x===1&&y!==11)return a;if(x>=2&&x<=4&&(y<10||y>=20))return b;return c;};
  const fmtDur=h=>h===Math.floor(h)?h+' '+plural(h,'час','часа','часов'):h.toFixed(1).replace('.',',')+' часа';

  const EVENTS = @json($mobileEvents ?? []);
  const ICON_PARTNER='<svg width="13" height="13" viewBox="0 0 24 24" style="stroke:#d0e285;stroke-width:2;fill:none;stroke-linecap:round;stroke-linejoin:round"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>';
  const TABLE_SVG='<svg width="14" height="14" viewBox="0 0 24 24" class="ico"><rect x="3" y="9" width="18" height="5" rx="1"/><path d="M6 14v4M18 14v4"/></svg>';

  let sel={col:2,s:'19:30',dur:60};

  // build header + columns + rows for NCOL tables
  const gtable=document.getElementById('gtable');
  gtable.style.width=(TW+NCOL*CW)+'px';
  // CSS-сетка захардкожена на 12 колонок — переопределяем под реальное число столов.
  const GCOLS=`var(--tw) repeat(${NCOL}, var(--cw))`;
  let hh='<div class="gh">Время</div>';
  for(let c=0;c<NCOL;c++) hh+=`<div class="gh">${TABLE_SVG}${esc(TABLE_NAMES[c]||('Стол '+(c+1)))}</div>`;
  const gheadEl=document.getElementById('ghead');
  gheadEl.innerHTML=hh; gheadEl.style.gridTemplateColumns=GCOLS;
  let ch='<div class="gc"></div>';
  for(let c=0;c<NCOL;c++) ch+='<div class="gc"></div>';
  const gcolsEl=document.getElementById('gcols');
  gcolsEl.innerHTML=ch; gcolsEl.style.gridTemplateColumns=GCOLS;

  const ROWS=(END-START)*2;
  let rh='';
  for(let i=0;i<ROWS;i++){const m=START*60+i*30;let cells='';for(let c=0;c<NCOL;c++)cells+=`<div class="gcell" data-col="${c}" data-min="${m}"></div>`;rh+=`<div class="grow" style="grid-template-columns:${GCOLS}"><div class="gt">${m%60===0?toStr(m):''}</div>${cells}</div>`;}
  document.getElementById('grows').innerHTML=rh;
  document.getElementById('gbody').style.height=(ROWS*ROW)+'px';

  function renderEvents(){
    const layer=document.getElementById('gevents');
    let html='';
    EVENTS.forEach(ev=>{
      const t=top(ev.s),h=top(ev.e)-top(ev.s);
      const body=ev.type==='partner'
        ? `<div class="et">${ICON_PARTNER}</div><div class="etm">${ev.s} – ${ev.e}</div>`
        : `<div class="et">${ev.title}</div><div class="etm">${ev.s} – ${ev.e}</div>`;
      html+=`<div class="ev ev-${ev.type}" data-col="${ev.col}" data-s="${ev.s}" style="left:${ev.col*CW+3}px;width:${CW-6}px;top:${t+3}px;height:${h-6}px;">${body}</div>`;
    });
    const st=top(sel.s),sh=(sel.dur/30)*ROW;
    html+=`<div class="ev ev-sel" style="left:${sel.col*CW+3}px;width:${CW-6}px;top:${st+3}px;height:${sh-6}px;"></div>`;
    layer.innerHTML=html;
    // Линия «сейчас» — только на сегодняшний день и в пределах сетки.
    const bn=branchNow();
    if(BOOK_DATE===bn.date && bn.min>=START*60 && bn.min<=END*60){
      const nl=document.createElement('div');nl.className='nowline';
      nl.style.top=((bn.min-START*60)/30*ROW)+'px';
      nl.innerHTML=`<span class="nl-lbl">${toStr(bn.min)}</span><span class="nl-dot"></span>`;
      layer.appendChild(nl);
    }
  }

  function occupied(col,startMin){
    return EVENTS.some(ev=>ev.col===col && startMin>=toMin(ev.s) && startMin<toMin(ev.e));
  }

  function updateForm(){
    const price=Math.round(sel.dur/60*RATE);
    const endStr=toStr(toMin(sel.s)+sel.dur);
    document.getElementById('f-table').textContent=(TABLE_NAMES[sel.col]||('Стол '+(sel.col+1)));
    document.getElementById('f-time').textContent=sel.s+' – '+endStr;
    document.getElementById('f-dur').textContent=fmtDur(sel.dur/60);
    document.getElementById('f-price').textContent=price+' ₽';
  }

  // tap empty cell or partner slot → select + open sheet
  document.getElementById('gbody').addEventListener('click',e=>{
    const cell=e.target.closest('.gcell');
    const evEl=e.target.closest('.ev');
    let col,startMin;
    if(evEl && evEl.classList.contains('ev-partner')){
      col=+evEl.dataset.col;startMin=toMin(evEl.dataset.s);
    } else if(evEl && !evEl.classList.contains('ev-sel')){
      return; // busy event
    } else if(cell){
      col=+cell.dataset.col;startMin=+cell.dataset.min;
    } else return;
    if(occupied(col,startMin)) return;
    sel={col,s:toStr(startMin),dur:60};
    renderEvents();updateForm();openSheet();
  });

  // sheet
  const overlay=document.getElementById('sheet-overlay'),sheet=document.getElementById('sheet');
  function refreshSheet(){
    const price=Math.round(sel.dur/60*RATE);
    const endStr=toStr(toMin(sel.s)+sel.dur);
    document.getElementById('sh-table').textContent=(TABLE_NAMES[sel.col]||('Стол '+(sel.col+1)));
    document.getElementById('sh-price').textContent=price+' ₽';
    document.getElementById('sh-time').textContent=sel.s+' – '+endStr+' ('+fmtDur(sel.dur/60)+')';
    document.getElementById('sh-total').textContent=price+' ₽';
    document.querySelectorAll('.dur').forEach(d=>d.classList.toggle('active',d.dataset.d==String(sel.dur)));
  }
  function openSheet(){refreshSheet();overlay.classList.add('open');sheet.classList.add('open');}
  function closeSheet(){overlay.classList.remove('open');sheet.classList.remove('open');}
  overlay.addEventListener('click',closeSheet);
  document.getElementById('sheet-x').addEventListener('click',closeSheet);
  document.querySelectorAll('.dur').forEach(d=>d.addEventListener('click',()=>{
    if(d.dataset.d==='plus'){sel.dur=Math.min(180,sel.dur+30);}
    else sel.dur=+d.dataset.d;
    renderEvents();updateForm();refreshSheet();
  }));

  // confirm
  const confirm=document.getElementById('confirm');
  function showConfirm(){
    const endStr=toStr(toMin(sel.s)+sel.dur);
    document.getElementById('cf-table').textContent=(TABLE_NAMES[sel.col]||('Стол '+(sel.col+1)));
    document.getElementById('cf-info').textContent=DATE_LABEL+' · '+sel.s+' – '+endStr+' ('+fmtDur(sel.dur/60)+')';
    closeSheet();confirm.classList.add('open');
  }
  document.getElementById('cf-continue').addEventListener('click',()=>{ confirm.classList.remove('open'); location.reload(); });
  const cfMine=document.querySelector('#confirm .btn-green');
  if(cfMine) cfMine.addEventListener('click',()=>{ const u=window.ttAuth&&ttAuth.get(); location.href='/account'+(u&&u.phone?('?phone='+encodeURIComponent(u.phone)):''); });

  // Тост для сообщений/ошибок
  function toast(msg, ok){
    let t=document.getElementById('m-toast');
    if(!t){ t=document.createElement('div'); t.id='m-toast'; t.style.cssText='position:fixed;left:50%;bottom:96px;transform:translateX(-50%);max-width:88%;z-index:10060;background:#1a1d14;border:1px solid rgba(198,226,26,.4);color:#e8f4b0;font-weight:600;font-size:13.5px;padding:12px 18px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.5);text-align:center;transition:opacity .25s;'; document.body.appendChild(t); }
    t.style.borderColor = ok ? 'rgba(198,226,26,.4)' : 'rgba(240,160,144,.5)';
    t.style.color = ok ? '#e8f4b0' : '#f0a090';
    t.textContent=msg; t.style.opacity='1';
    clearTimeout(t._h); t._h=setTimeout(()=>{ t.style.opacity='0'; }, 4500);
  }

  // Бронирование: вход (по телефону) → POST /book → подтверждение.
  // Лимит недели и рабочие часы проверяет бэкенд — его сообщение показываем тостом.
  let pendingBook=false;
  async function doBooking(){
    const u=(window.ttAuth&&ttAuth.get())||null;
    if(!u||!u.phone){ pendingBook=true; if(window.openAuth) openAuth('login'); return; }
    const start=sel.s, end=toStr(toMin(sel.s)+sel.dur);
    const resourceId=TABLE_IDS[sel.col];
    if(!resourceId||!BRANCH_ID||!SERVICE_ID||!BOOK_DATE){ toast('Бронирование пока не настроено для этого клуба.'); return; }
    try{
      const res=await fetch('/book',{method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({name:u.name||'Гость',phone:u.phone,branch_id:BRANCH_ID,service_offering_id:SERVICE_ID,resource_id:resourceId,date:BOOK_DATE,start,end})});
      const data=await res.json().catch(()=>({}));
      if(!res.ok||!data.ok){ closeSheet(); toast(data.message||'Не удалось забронировать.'); return; }
      showConfirm();
    }catch(e){ toast('Сеть недоступна, попробуйте ещё раз.'); }
  }
  // После входа продолжаем отложенную бронь.
  window.ttOnLogin=function(user){ if(pendingBook){ pendingBook=false; setTimeout(doBooking,40); return true; } return false; };

  document.getElementById('sheet-book').addEventListener('click',doBooking);
  document.getElementById('form-book').addEventListener('click',doBooking);

  // выбор даты → перезагрузка расписания на этот день
  const mdate=document.getElementById('m-date');
  if(mdate) mdate.addEventListener('change',()=>{ if(mdate.value) location.href='/schedule?date='+mdate.value; });

  renderEvents();updateForm();
  setInterval(renderEvents, 60000); // двигаем линию «сейчас» раз в минуту
  // центрируем на выбранном столе
  const gscroll=document.getElementById('gscroll');
  if(gscroll) gscroll.scrollLeft=Math.max(0, sel.col*CW - CW);
})();
</script>
<script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
