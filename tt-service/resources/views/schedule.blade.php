@php
  $months = ['','января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
  $days   = ['вс','пн','вт','ср','чт','пт','сб'];
  $fmt = function(int $offset = 0) use ($months, $days): string {
    $ts = strtotime("+{$offset} days");
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ', ' . $days[(int)date('w', $ts)];
  };
  // Подпись выбранной даты расписания (для пикера/сайдбара после перехода ?date=)
  $schedTs    = strtotime($scheduleDate ?? 'today');
  $schedLabel = date('j', $schedTs) . ' ' . $months[(int)date('n', $schedTs)] . ', ' . $days[(int)date('w', $schedTs)];
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Расписание — Теннис Клуб НСК</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="{{ asset('js/device.js') }}"></script>
<style>
:root{
  --bg:#0a0b09;--panel:#101210;--panel-2:#151714;
  --line:rgba(255,255,255,.08);--line-soft:rgba(255,255,255,.05);
  --txt:#f0f1ec;--muted:#8c8f86;--muted-2:#6f726a;
  --green:#c6e21a;--radius:14px;
  --slots:30;
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{background:var(--bg);color:var(--txt);font-family:'Manrope',system-ui,sans-serif;-webkit-font-smoothing:antialiased;}
html{overflow-x:hidden;}
body{padding:0 22px 30px;overflow-x:hidden;}
.wrap{max-width:1660px;margin:0 auto;}
a{color:inherit;text-decoration:none;}
.green{color:var(--green);}
svg{display:block;}
.ico{stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round;}

/* ── HEADER ─────────────────────────────────────── */
header{display:flex;align-items:center;gap:22px;padding:18px 4px 22px;}
.logo{display:flex;align-items:center;gap:14px;flex:none;}
.logo-mark{width:48px;height:48px;flex:none;}
.logo-txt .brand{font-size:23px;font-weight:800;letter-spacing:.5px;line-height:1;white-space:nowrap;}
.logo-txt .sub{font-size:12px;color:var(--muted);margin-top:5px;}
nav{display:flex;align-items:center;gap:26px;margin:0 auto 0 22px;}
nav a{font-size:15px;color:#cfd1ca;font-weight:500;position:relative;padding-bottom:6px;}
nav a:hover{color:#fff;}
nav a.active{color:var(--green);}
nav a.active::after{content:"";position:absolute;left:0;right:0;bottom:0;height:2px;background:var(--green);border-radius:2px;}
.head-info{display:flex;align-items:flex-start;gap:10px;flex:none;}
.head-info .ico-box{color:var(--green);margin-top:2px;}
.head-info .lbl b{font-weight:700;font-size:15px;display:block;color:#e7e8e3;}
.head-info .lbl .l2{color:var(--muted);font-size:12.5px;line-height:1.4;}
.head-phone{margin-left:14px;flex:none;}
.head-phone b{font-size:18px;letter-spacing:.3px;white-space:nowrap;}
.btn-cabinet{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.06);border:1px solid var(--line);border-radius:10px;padding:10px 16px;font-size:14px;font-weight:600;color:#e7e8e3;cursor:pointer;margin-left:14px;white-space:nowrap;flex:none;transition:background .14s;}
.btn-cabinet:hover{background:rgba(255,255,255,.1);}
/* Шапка с входом и телефоном: на средних экранах постепенно убираем
   второстепенное, чтобы кнопка «Войти» не уезжала за край (ниже 900 — бургер). */
@media(max-width:1500px){
  header{gap:16px;}
  nav{gap:18px;margin-left:16px;}
  .logo-txt .sub{display:none;}
}
@media(max-width:1300px){
  nav{gap:16px;margin-left:12px;}
  nav a{font-size:14px;}
  .head-info:not(.head-phone){display:none;}
}
@media(max-width:1120px){
  .head-phone{display:none;}
  nav{gap:13px;}
  nav a{font-size:13.5px;}
}

/* ── PAGE HERO ───────────────────────────────────── */
.page-hero{position:relative;border-radius:var(--radius);overflow:hidden;height:122px;border:1px solid var(--line-soft);margin-bottom:16px;background:var(--panel);}
#hero-schedule{position:absolute;right:0;top:0;width:52%;height:100%;z-index:0;object-fit:cover;}
.page-hero .veil{position:absolute;inset:0;z-index:1;pointer-events:none;
  background:linear-gradient(90deg,var(--panel) 0%,var(--panel) 30%,rgba(16,18,16,.92) 46%,rgba(16,18,16,.32) 65%,rgba(16,18,16,.55) 100%);}
.hero-txt{position:relative;z-index:2;padding:24px 46px;}
.hero-txt h1{font-size:34px;font-weight:800;letter-spacing:-.3px;line-height:1.1;}
.hero-txt p{font-size:14.5px;color:#9ea09a;margin-top:8px;}

/* ── CONTROLS BAR ────────────────────────────────── */
.controls-bar{display:flex;align-items:flex-end;gap:10px;margin-bottom:16px;flex-wrap:wrap;}
.ctrl-pick{display:flex;align-items:center;gap:12px;background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:11px 15px;cursor:pointer;min-width:188px;transition:border-color .14s;}
.ctrl-pick:hover{border-color:rgba(198,226,26,.38);}
.ctrl-pick .cp-ico{color:var(--green);flex:none;}
.ctrl-pick .cp-lbl{flex:1;}
.ctrl-pick .cp-lbl .k{font-size:11px;color:var(--muted);}
.ctrl-pick .cp-lbl .v{font-size:15.5px;font-weight:700;margin-top:2px;}
.ctrl-pick .chev{color:var(--muted);}
.ctrl-sep{width:1px;height:44px;background:var(--line);margin:0 4px;}
.vt-wrap{display:flex;flex-direction:column;gap:4px;}
.vt-wrap .vt-lbl{font-size:11px;color:var(--muted);font-weight:500;padding-left:2px;}
.view-toggle{display:flex;align-items:center;gap:2px;background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:4px 5px;}
.vt{display:flex;align-items:center;gap:7px;padding:8px 16px;border-radius:7px;font-size:14px;font-weight:600;color:var(--muted);cursor:pointer;white-space:nowrap;transition:all .12s;}
.vt:hover{color:#dfe0db;}
.vt.active{background:rgba(255,255,255,.09);color:var(--txt);}
.ctrl-filter{display:flex;align-items:center;gap:9px;background:var(--green);border:1px solid var(--green);border-radius:10px;padding:12px 22px;font-size:14.5px;font-weight:700;color:#13160a;cursor:pointer;margin-left:auto;transition:filter .14s;}
.ctrl-filter:hover{filter:brightness(1.07);}

/* ── LAYOUT ──────────────────────────────────────── */
.main-layout{display:grid;grid-template-columns:1fr 292px;gap:18px;align-items:start;}

/* ── SCHEDULE PANEL ──────────────────────────────── */
.sched-panel{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;}
.sched-scroll{overflow-x:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.2) transparent;}
.sched-scroll::-webkit-scrollbar{height:10px;}
.sched-scroll::-webkit-scrollbar-track{background:rgba(255,255,255,.03);}
.sched-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.16);border-radius:5px;border:2px solid var(--panel);}
.sched{min-width:2180px;width:100%;}
.sched-head{display:flex;border-bottom:1px solid rgba(255,255,255,.09);position:sticky;top:0;}
.sh-label{width:140px;flex:none;padding:11px 14px;font-size:12.5px;font-weight:600;color:var(--muted);border-right:1px solid rgba(255,255,255,.07);background:var(--panel);position:sticky;left:0;z-index:3;}
.sh-times{flex:1;position:relative;height:40px;}
.sh-times .t{position:absolute;top:50%;transform:translate(-50%,-50%);font-size:12px;font-weight:600;color:var(--muted);white-space:nowrap;}
.sched-row{display:flex;border-bottom:1px solid var(--line-soft);}
.sched-row:last-child{border-bottom:none;}
.sched-row:nth-child(even) .row-body{background:rgba(255,255,255,.01);}
.row-label{width:140px;flex:none;border-right:1px solid rgba(255,255,255,.07);padding:0 14px;display:flex;align-items:center;gap:9px;font-size:14px;font-weight:600;height:60px;white-space:nowrap;background:var(--panel);position:sticky;left:0;z-index:2;}
.row-label .rl-ico{color:var(--muted-2);flex:none;}
.row-body{flex:1;position:relative;height:60px;cursor:crosshair;}
.row-body::before{content:"";position:absolute;inset:0;z-index:0;pointer-events:none;
  background:repeating-linear-gradient(to right,transparent 0,transparent calc(100%/var(--slots) - 1px),rgba(255,255,255,.05) calc(100%/var(--slots) - 1px),rgba(255,255,255,.05) calc(100%/var(--slots)));}
.row-body .hover-ghost{position:absolute;top:8px;bottom:8px;border-radius:7px;border:1.5px dashed rgba(198,226,26,.5);background:rgba(198,226,26,.06);display:none;align-items:center;justify-content:center;color:var(--green);font-size:11.5px;font-weight:700;pointer-events:none;z-index:3;}
.ev{position:absolute;top:8px;bottom:8px;border-radius:7px;padding:3px 9px;font-size:12.5px;font-weight:600;display:flex;flex-direction:column;justify-content:center;overflow:hidden;cursor:pointer;z-index:1;transition:filter .12s;}
.ev:hover{filter:brightness(1.1);}
.ev-title{line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ev-time{font-size:11px;font-weight:500;opacity:.72;margin-top:2px;white-space:nowrap;}
.ev-training-ind  {background:rgba(42,90,175,.5);border:1px solid rgba(80,144,224,.4);color:#bcd3f5;}
.ev-training-group{background:rgba(80,46,148,.54);border:1px solid rgba(118,82,198,.42);color:#d0c4f8;}
.ev-club-event    {background:rgba(32,90,46,.6); border:1px solid rgba(55,132,72,.44);color:#aee8be;}
.ev-tournament    {background:rgba(112,56,14,.64);border:1px solid rgba(164,92,32,.5);color:#f0c090;}
.ev-private       {background:rgba(52,55,50,.72);border:1px solid rgba(76,80,74,.5); color:#b2b5ae;}
.ev-mybooking     {background:rgba(120,150,20,.4);border:1.5px solid var(--green);color:#e8f4b0;}
.ev-free-dashed   {background:rgba(198,226,26,.04);border:1.5px dashed rgba(198,226,26,.42);color:rgba(198,226,26,.75);align-items:center;justify-content:center;}
.ev-find-partner  {background:rgba(12,18,8,.82);border:1.5px dashed rgba(198,226,26,.6);color:#d0e285;padding:4px 10px;gap:4px;}
.ev-find-partner.sel{border-color:var(--green);background:rgba(198,226,26,.07);}
.sched-list{padding:8px 6px;}
.sl-row{display:grid;grid-template-columns:150px 150px 1fr auto;align-items:center;gap:18px;padding:15px 18px;border-bottom:1px solid var(--line-soft);transition:background .12s;}
.sl-row:last-child{border-bottom:none;}
.sl-row:hover{background:rgba(255,255,255,.015);}
.sl-time{display:flex;align-items:center;gap:10px;font-size:14.5px;font-weight:700;}
.sl-time .si{color:var(--muted);flex:none;}
.sl-table{font-size:14px;color:#cfd1ca;font-weight:600;display:flex;align-items:center;gap:8px;}
.sl-table .si{color:var(--muted-2);flex:none;}
.sl-name{display:flex;align-items:center;gap:11px;min-width:0;}
.sl-tag{font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:6px;white-space:nowrap;border:1px solid;flex:none;}
.sl-title{font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.tag-training-ind  {color:#bcd3f5;border-color:rgba(80,144,224,.5);background:rgba(42,90,175,.18);}
.tag-training-group{color:#d0c4f8;border-color:rgba(118,82,198,.5);background:rgba(80,46,148,.18);}
.tag-club-event    {color:#aee8be;border-color:rgba(55,132,72,.5);background:rgba(32,90,46,.2);}
.tag-tournament    {color:#f0c090;border-color:rgba(164,92,32,.5);background:rgba(112,56,14,.2);}
.tag-private       {color:#b2b5ae;border-color:rgba(76,80,74,.6);background:rgba(52,55,50,.3);}
.tag-find-partner  {color:#d0e285;border-color:rgba(198,226,26,.5);background:rgba(198,226,26,.08);}
.tag-free-dashed   {color:rgba(198,226,26,.85);border-color:rgba(198,226,26,.45);background:rgba(198,226,26,.05);}
.sl-act{font-size:13px;font-weight:700;color:var(--green);cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;}
.sl-act:hover{filter:brightness(1.15);}
.sl-act.muted{color:var(--muted);cursor:default;}
#partner-popup{position:fixed;z-index:9999;display:none;background:rgba(24,29,18,.62);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(198,226,26,.34);border-radius:12px;padding:15px 17px;width:276px;box-shadow:0 12px 36px rgba(0,0,0,.5);pointer-events:none;}
#partner-popup .pp-row{display:flex;align-items:center;gap:9px;margin-bottom:7px;}
#partner-popup .pp-title{font-size:14px;font-weight:700;line-height:1.2;}
#partner-popup .pp-level{display:flex;align-items:center;gap:7px;font-size:13px;color:#dcc96e;font-weight:600;}
#partner-popup .pp-desc{font-size:12.5px;color:#b6bca6;line-height:1.45;margin-top:9px;}
#partner-popup::after{content:"";position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);width:14px;height:8px;background:rgba(24,29,18,.62);backdrop-filter:blur(12px);clip-path:polygon(0 0,100% 0,50% 100%);}
.sidebar{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:26px 24px;}
.sb-title{font-size:20px;font-weight:800;margin-bottom:24px;}
.sb-field{margin-bottom:18px;}
.sb-field .k{font-size:13px;color:var(--muted);margin-bottom:5px;font-weight:500;}
.sb-field .v{font-size:16px;font-weight:700;}
.sb-divider{height:1px;background:var(--line);margin:18px 0;}
.sb-total{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.sb-total .tl{font-size:15.5px;font-weight:600;}
.sb-total .tv{font-size:22px;font-weight:800;color:var(--green);}
.btn-book{width:100%;background:var(--green);color:#13160a;font-weight:800;font-size:16px;border:none;border-radius:10px;padding:15px;cursor:pointer;margin-bottom:9px;display:block;text-align:center;transition:filter .12s;}
.btn-book:hover{filter:brightness(1.07);}
.btn-ol{width:100%;background:transparent;border:1.5px solid rgba(198,226,26,.5);color:var(--green);font-weight:700;font-size:14px;border-radius:10px;padding:12px;cursor:pointer;margin-bottom:9px;display:flex;align-items:center;justify-content:center;gap:9px;transition:all .12s;}
.btn-ol:hover{border-color:var(--green);background:rgba(198,226,26,.07);}
.sb-note{font-size:12px;color:var(--muted);line-height:1.45;display:flex;align-items:flex-start;gap:8px;margin-top:14px;}
.sb-note a{color:var(--green);text-decoration:underline;text-underline-offset:2px;}
.legend{display:flex;align-items:center;gap:20px;padding:16px 2px 0;flex-wrap:wrap;}
.legend-item{display:flex;align-items:center;gap:8px;font-size:13px;color:#a6a8a1;}
.ld{width:16px;height:16px;border-radius:4px;flex:none;}
.ld-free  {border:1.5px solid rgba(255,255,255,.22);}
.ld-part  {border:1.5px dashed rgba(198,226,26,.52);}
.ld-ind   {background:rgba(42,90,175,.5); border:1px solid rgba(80,144,224,.4);}
.ld-grp   {background:rgba(80,46,148,.54);border:1px solid rgba(118,82,198,.42);}
.ld-club  {background:rgba(32,90,46,.6);  border:1px solid rgba(55,132,72,.44);}
.ld-tourn {background:rgba(112,56,14,.64);border:1px solid rgba(164,92,32,.5);}
.ld-priv  {background:rgba(52,55,50,.72); border:1px solid rgba(76,80,74,.5);}
.modal-overlay{position:fixed;inset:0;z-index:9998;background:rgba(6,7,5,.72);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;animation:ovIn .15s ease;}
@keyframes ovIn{from{opacity:0}to{opacity:1}}
.modal{background:#13150f;border:1px solid var(--line);border-radius:18px;width:420px;max-width:100%;padding:28px 28px 26px;box-shadow:0 24px 70px rgba(0,0,0,.6);animation:mdIn .18s cubic-bezier(.2,.7,.3,1);}
@keyframes mdIn{from{opacity:0;transform:translateY(12px) scale(.98)}to{opacity:1;transform:none}}
.modal-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;}
.modal-head h3{font-size:21px;font-weight:800;}
.modal-head .mh-sub{font-size:13px;color:var(--muted);margin-top:4px;}
.modal-x{width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:var(--muted);cursor:pointer;display:grid;place-items:center;flex:none;transition:all .12s;}
.modal-x:hover{background:rgba(255,255,255,.1);color:#fff;}
.bk-row{display:flex;align-items:center;justify-content:space-between;padding:13px 0;border-bottom:1px solid var(--line-soft);}
.bk-row .bk-k{font-size:13.5px;color:var(--muted);}
.bk-row .bk-v{font-size:15px;font-weight:700;}
.bk-field{padding:15px 0;border-bottom:1px solid var(--line-soft);}
.bk-field .bk-lbl{font-size:13px;color:var(--muted);margin-bottom:10px;font-weight:500;}
.stepper{display:flex;align-items:center;justify-content:space-between;background:#0c0d0b;border:1px solid var(--line);border-radius:10px;padding:6px;}
.stepper button{width:40px;height:38px;border-radius:8px;border:none;background:rgba(255,255,255,.06);color:var(--green);font-size:22px;font-weight:700;cursor:pointer;display:grid;place-items:center;line-height:1;transition:background .12s;}
.stepper button:hover{background:rgba(198,226,26,.16);}
.stepper .step-v{font-size:16px;font-weight:700;}
.bk-total{display:flex;align-items:center;justify-content:space-between;padding:18px 0 20px;}
.bk-total .bt-k{font-size:16px;font-weight:600;}
.bk-total .bt-v{font-size:24px;font-weight:800;color:var(--green);}
.bk-ok{width:100%;background:var(--green);color:#13160a;font-weight:800;font-size:16px;border:none;border-radius:11px;padding:15px;cursor:pointer;transition:filter .12s;}
.bk-ok:hover{filter:brightness(1.07);}
.bk-done{display:flex;flex-direction:column;align-items:center;text-align:center;padding:8px 0 4px;}
.bk-done .bd-ico{width:64px;height:64px;border-radius:50%;background:rgba(198,226,26,.14);border:1.5px solid var(--green);display:grid;place-items:center;color:var(--green);margin-bottom:18px;}
.bk-done h4{font-size:20px;font-weight:800;margin-bottom:8px;}
.bk-done p{font-size:14px;color:var(--muted);line-height:1.5;margin-bottom:22px;}
#toast{position:fixed;bottom:26px;left:50%;transform:translateX(-50%) translateY(20px);z-index:10001;background:#1a1d14;border:1px solid rgba(198,226,26,.4);color:#e8f4b0;font-weight:600;font-size:14px;padding:13px 22px;border-radius:11px;box-shadow:0 10px 30px rgba(0,0,0,.5);opacity:0;pointer-events:none;transition:opacity .25s,transform .25s;display:flex;align-items:center;gap:10px;}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
</style>
</head>
<body>
<div class="wrap">

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
    <div class="logo-txt">
      <div class="brand">ТЕННИС КЛУБ <span class="green">НСК</span></div>
      <div class="sub">Премиальный клуб настольного тенниса</div>
    </div>
  </div>
  <nav>
    <a href="/">Главная</a>
    <a href="#">Клуб</a>
    <a href="/schedule" class="active">Расписание</a>
    <a href="#">Турниры</a>
    <a href="/coaches">Тренеры</a>
    <a href="/partners">Партнёры</a>
    <a href="#">Контакты</a>
  </nav>
  <div class="head-info">
    <span class="ico-box"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg></span>
    <div class="lbl">
      <b>Красный проспект 2/1,</b>
      <div class="l2">3 этаж</div>
    </div>
  </div>
  <div class="head-info head-phone">
    <span class="ico-box"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><path d="M6.5 4h3l1.5 4-2 1.5a11 11 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2A16 16 0 0 1 4.5 6a2 2 0 0 1 2-2Z"/></svg></span>
    <div class="lbl">
      <b>207-86-20</b>
      <div class="l2">Ежедневно 8:00 – 23:00</div>
    </div>
  </div>
  <button class="btn-cabinet" data-auth-open>
    <svg width="16" height="16" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
    <span data-au-label>Войти</span>
    <svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg>
  </button>
</header>

<!-- PAGE HERO -->
<div class="page-hero">
  <img id="hero-schedule" src="{{ asset('images/hero-player.jpg') }}" alt="">
  <div class="veil"></div>
  <div class="hero-txt">
    <h1>Бронирование стола</h1>
    <p>Выберите удобное время и забронируйте стол для игры</p>
  </div>
</div>

<!-- CONTROLS -->
<div class="controls-bar">
  <div class="ctrl-pick" data-dd data-dd-type="calendar" data-dd-callback="onScheduleDate">
    <span class="cp-ico"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg></span>
    <div class="cp-lbl"><div class="k">Дата</div><div class="v" data-dd-val>{{ $schedLabel }}</div></div>
    <span class="chev"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span>
  </div>
  <div class="ctrl-pick" data-dd data-dd-options="Всё время|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|19:00|20:00|21:00|22:00">
    <span class="cp-ico"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>
    <div class="cp-lbl"><div class="k">Время с</div><div class="v" data-dd-val>Всё время</div></div>
    <span class="chev"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span>
  </div>
  <div class="ctrl-sep"></div>
  <div class="vt-wrap">
    <div class="vt-lbl">Вид отображения</div>
    <div class="view-toggle">
      <div class="vt active" id="vt-grid">
        <svg width="15" height="15" viewBox="0 0 24 24" class="ico"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Сетка
      </div>
      <div class="vt" id="vt-list">
        <svg width="15" height="15" viewBox="0 0 24 24" class="ico"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
        Список
      </div>
    </div>
  </div>
  <button class="ctrl-filter">
    <svg width="17" height="17" viewBox="0 0 24 24" class="ico"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
    Фильтры
  </button>
</div>

<!-- Уведомление: дата дальше окна онлайн-брони -->
<div id="faraway-banner" style="display:none;margin-bottom:14px;padding:13px 16px;border:1px solid rgba(226,193,74,.4);background:rgba(226,193,74,.08);border-radius:12px;color:#e6d27a;font-size:13.5px;font-weight:600;align-items:center;gap:10px;">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
  <span>Онлайн-бронь доступна только на ближайшую неделю. На эту дату забронирует администратор — <a id="faraway-banner-phone" href="#" style="color:var(--green);text-decoration:underline;text-underline-offset:2px;">позвоните нам</a>.</span>
</div>

<!-- MAIN -->
<div class="main-layout">
  <div class="sched-panel">
    <div class="sched-scroll" id="sched-scroll">
      <div class="sched" id="sched-root"></div>
    </div>
    <div class="sched-list" id="sched-list" style="display:none"></div>
  </div>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sb-title">Ваше бронирование</div>
    <div class="sb-field"><div class="k">Стол</div><div class="v" id="sb-table">Стол 4</div></div>
    <div class="sb-field"><div class="k">Дата</div><div class="v" id="sb-date">{{ $schedLabel }}</div></div>
    <div class="sb-field"><div class="k">Время</div><div class="v" id="sb-time">18:00 – 19:30</div></div>
    <div class="sb-field"><div class="k">Длительность</div><div class="v" id="sb-dur">1.5 часа</div></div>
    <div class="sb-divider"></div>
    <div class="sb-total">
      <span class="tl">Итого</span>
      <span class="tv" id="sb-price">750 ₽</span>
    </div>
    <button class="btn-book" id="sb-book">Забронировать</button>
    <button class="btn-ol">
      <svg width="16" height="16" viewBox="0 0 24 24" class="ico"><circle cx="9" cy="9" r="4"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 7a3 3 0 0 1 0 5M17 19a6 6 0 0 0-3-5"/></svg>
      Ищу напарника
    </button>
    <button class="btn-ol">
      <svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
      Поделиться
    </button>
    <div class="sb-note">
      <svg width="14" height="14" viewBox="0 0 24 24" class="ico" style="flex:none;margin-top:1px;color:var(--muted)"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16v.5"/></svg>
      Нажмите на свободную ячейку в сетке, чтобы <a href="#">забронировать стол</a>.
    </div>
  </div>
</div>

<!-- LEGEND -->
<div class="legend">
  <div class="legend-item"><span class="ld ld-free"></span>Свободно</div>
  <div class="legend-item"><span class="ld ld-part"></span>Есть игрок, ищет напарника</div>
  <div class="legend-item"><span class="ld ld-ind"></span>Индивидуальная тренировка</div>
  <div class="legend-item"><span class="ld ld-grp"></span>Групповая тренировка</div>
  <div class="legend-item"><span class="ld ld-club"></span>Клубное мероприятие</div>
  <div class="legend-item"><span class="ld ld-tourn"></span>Турнир</div>
  <div class="legend-item"><span class="ld ld-priv"></span>Частное бронирование</div>
</div>

</div><!-- /wrap -->

<!-- PARTNER POPUP -->
<div id="partner-popup">
  <div class="pp-row">
    <svg width="17" height="17" viewBox="0 0 24 24" class="ico" style="color:var(--green);flex:none"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
    <div class="pp-title">Есть игрок, ищет напарника</div>
  </div>
  <div class="pp-level">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="#dcc96e" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    Уровень: 350 TTW
  </div>
  <div class="pp-desc">Вы можете подтвердить совместную игру и поделить аренду</div>
</div>

<!-- BOOKING MODAL -->
<div class="modal-overlay" id="book-modal">
  <div class="modal">
    <div id="bk-form">
      <div class="modal-head">
        <div><h3>Бронирование стола</h3><div class="mh-sub">Выберите точное время и подтвердите</div></div>
        <button class="modal-x" id="bk-close"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      </div>
      <div class="bk-row"><span class="bk-k">Стол</span><span class="bk-v" id="bk-table">Стол 4</span></div>
      <div class="bk-row"><span class="bk-k">Дата</span><span class="bk-v" id="bk-date">{{ $schedLabel }}</span></div>
      <div id="bk-asuser" style="display:none;align-items:center;gap:8px;background:rgba(198,226,26,.08);border:1px solid rgba(198,226,26,.25);border-radius:10px;padding:9px 12px;margin:4px 0 2px;font-size:13px;color:#cfe08a;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
        Вы вошли как <span style="font-weight:700;color:#dff0a0"></span>
      </div>
      <div class="bk-field">
        <div class="bk-lbl">Время начала</div>
        <div class="stepper"><button data-step="start-1">−</button><span class="step-v" id="bk-start">18:00</span><button data-step="start1">+</button></div>
      </div>
      <div class="bk-field">
        <div class="bk-lbl">Длительность</div>
        <div class="stepper"><button data-step="dur-1">−</button><span class="step-v" id="bk-durv">1 час</span><button data-step="dur1">+</button></div>
      </div>
      <div class="bk-field">
        <div class="bk-lbl">Ваше имя</div>
        <input id="bk-name" type="text" autocomplete="name" placeholder="Как к вам обращаться"
          style="width:100%;background:#0c0d0b;border:1px solid var(--line);border-radius:10px;padding:13px 14px;color:var(--txt);font-size:15px;font-family:inherit;outline:none;">
      </div>
      <div class="bk-field">
        <div class="bk-lbl">Телефон</div>
        <input id="bk-phone" type="tel" autocomplete="tel" placeholder="+7 ___ ___-__-__"
          style="width:100%;background:#0c0d0b;border:1px solid var(--line);border-radius:10px;padding:13px 14px;color:var(--txt);font-size:15px;font-family:inherit;outline:none;">
      </div>
      <div id="bk-error" style="display:none;color:#f0a090;font-size:13px;font-weight:600;padding:4px 2px 0;"></div>
      <div class="bk-total"><span class="bt-k">Итого</span><span class="bt-v" id="bk-price">500 ₽</span></div>
      <button class="bk-ok" id="bk-confirm">Забронировать</button>
    </div>
    <div id="bk-success" style="display:none">
      <div class="bk-done">
        <div class="bd-ico"><svg width="32" height="32" viewBox="0 0 24 24" class="ico" style="stroke-width:2"><path d="m5 12 5 5 9-11"/></svg></div>
        <h4>Стол забронирован!</h4>
        <p id="bk-success-text">Стол 4 · {{ $fmt(0) }}<br>18:00 – 19:00</p>
        <button class="bk-ok" id="bk-success-ok">Отлично</button>
      </div>
    </div>
    <div id="bk-faraway" style="display:none">
      <div class="bk-done">
        <div class="bd-ico" style="border-color:#e2c14a;color:#e2c14a;background:rgba(226,193,74,.12)">
          <svg width="30" height="30" viewBox="0 0 24 24" class="ico" style="stroke-width:2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>
        </div>
        <h4>Бронь на этот срок — через администратора</h4>
        <p>Онлайн можно забронировать только на ближайшую неделю — дальше расписание ещё формируется (турниры, тренировки).<br>Позвоните, и мы забронируем вручную:</p>
        <a id="bk-admin-phone" href="#" style="display:inline-block;margin:2px 0 16px;font-size:20px;font-weight:800;color:var(--green);text-decoration:none;">—</a>
        <button class="bk-ok" id="bk-faraway-ok">Понятно</button>
      </div>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
(function(){
  const RATE_PER_HOUR = {{ (int)($ratePerHour ?: 500) }};
  const TABLE_IDS  = @json($tableIds ?? []);
  const BRANCH_ID  = @json($branchId ?? null);
  const SERVICE_ID = @json($serviceId ?? null);
  const BOOK_DATE  = @json($scheduleDate ?? null);
  const MAX_BOOK_DATE = @json($maxBookDate ?? null);   // дальше — только через администратора
  const ADMIN_PHONE   = @json($adminPhone ?? '');
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const START_HOUR = 8, END_HOUR = 23;
  const START_MIN = START_HOUR*60, SPAN_MIN = (END_HOUR-START_HOUR)*60;
  const SLOTS = (END_HOUR-START_HOUR)*2;

  const toMin = t => { const [h,m]=t.split(':').map(Number); return h*60+m; };
  const toStr = m => String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0');
  const tp = t => ((toMin(t)-START_MIN)/SPAN_MIN)*100;
  const diffH = (s,e)=> (toMin(e)-toMin(s))/60;
  const fmtDur = h => h===Math.floor(h) ? (h+' '+plural(h,'час','часа','часов')) : (h.toFixed(1).replace('.',',')+' часа');
  function plural(n,a,b,c){const m10=n%10,m100=n%100;if(m10===1&&m100!==11)return a;if(m10>=2&&m10<=4&&(m100<10||m100>=20))return b;return c;}

  const HOURS=[]; for(let h=START_HOUR;h<=END_HOUR;h++) HOURS.push(toStr(h*60));
  // Столы и брони — реальные, из БД (то, что админ ведёт в MoonShine).
  const TABLES = @json($tableNames ?? []);
  const TYPE_NAME={'training-ind':'Индивидуальная тренировка','training-group':'Групповая тренировка',
    'club-event':'Клубное мероприятие','tournament':'Турнир','private':'Частное бронирование',
    'find-partner':'Есть игрок, ищет напарника','free-dashed':'Свободно','mybooking':'Моя бронь'};

  let EVENTS = @json($events ?? []);

  const PADDLE = `<svg width="16" height="16" viewBox="0 0 24 24" class="ico rl-ico" style="stroke:var(--muted-2)"><ellipse cx="12" cy="11" rx="8" ry="9"/><rect x="10.5" y="19" width="3" height="5" rx="1.5"/></svg>`;
  const PERSON = `<svg width="11" height="11" viewBox="0 0 24 24" style="flex:none;stroke:#d0e285;stroke-width:2;fill:none;stroke-linecap:round;stroke-linejoin:round"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>`;

  let sel = {row:0, start:'18:00', end:'19:00', table:(TABLES[0]||'')};
  let curDate = '{{ $schedLabel }}';

  function renderGrid(){
    const root = document.getElementById('sched-root');
    let html = '<div class="sched-head"><div class="sh-label">Время</div><div class="sh-times">';
    HOURS.forEach((t,i)=>{ html += `<span class="t" style="left:${(i/(HOURS.length-1)*100).toFixed(3)}%">${t}</span>`; });
    html += '</div></div>';
    TABLES.forEach((tbl, ri)=>{
      const rowEvs = EVENTS.filter(e=>e.row===ri);
      html += `<div class="sched-row" data-row="${ri}"><div class="row-label">${PADDLE}${tbl}</div><div class="row-body" data-row="${ri}"><div class="hover-ghost"></div>`;
      rowEvs.forEach(ev=>{
        const l = tp(ev.start).toFixed(3), w = (tp(ev.end)-tp(ev.start)).toFixed(3);
        const isSel = sel && sel.row===ri && sel.start===ev.start;
        if(ev.type==='find-partner'){
          html += `<div class="ev ev-find-partner${isSel?' sel':''}" style="left:${l}%;width:${w}%;" data-ev="1" data-row="${ri}" data-start="${ev.start}" data-end="${ev.end}" data-table="${tbl}" data-partner="1">
            <div class="ev-title" style="display:flex;align-items:center;gap:5px;">${PERSON}${ev.title}</div></div>`;
        } else if(ev.type==='free-dashed'){
          html += `<div class="ev ev-free-dashed" style="left:${l}%;width:${w}%;" data-ev="1" data-row="${ri}" data-start="${ev.start}" data-end="${ev.end}" data-table="${tbl}">${PERSON}</div>`;
        } else {
          html += `<div class="ev ev-${ev.type}" style="left:${l}%;width:${w}%;" data-ev="1" data-readonly="1">
            <div class="ev-title">${ev.title||''}</div><div class="ev-time">${ev.start} – ${ev.end}</div></div>`;
        }
      });
      html += `</div></div>`;
    });
    root.innerHTML = html;
  }

  function renderList(){
    const list = document.getElementById('sched-list');
    const sorted = EVENTS.slice().sort((a,b)=> toMin(a.start)-toMin(b.start) || a.row-b.row);
    let html='';
    sorted.forEach(ev=>{
      const tbl = TABLES[ev.row];
      const isFree = ev.type==='free-dashed';
      const isPartner = ev.type==='find-partner';
      const tag = TYPE_NAME[ev.type];
      html += `<div class="sl-row">
        <div class="sl-time"><span class="si"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>${ev.start} – ${ev.end}</div>
        <div class="sl-table"><span class="si">${PADDLE}</span>${tbl}</div>
        <div class="sl-name"><span class="sl-tag tag-${ev.type}">${tag}</span><span class="sl-title">${ev.title||(isFree?'Свободный слот':'')}</span></div>`;
      if(isFree){
        html += `<div class="sl-act" data-book-row="${ev.row}" data-book-start="${ev.start}">Забронировать <svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>`;
      } else if(isPartner){
        html += `<div class="sl-act" data-book-row="${ev.row}" data-book-start="${ev.start}">Присоединиться <svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>`;
      } else {
        html += `<div class="sl-act muted">Занято</div>`;
      }
      html += `</div>`;
    });
    list.innerHTML = html;
  }

  function rerender(){
    if(viewMode==='grid'){ const s=document.getElementById('sched-scroll'); const sl=s.scrollLeft; renderGrid(); s.scrollLeft=sl; }
    else renderList();
  }

  function updateSidebar(row, start, end, table){
    const h = diffH(start,end);
    document.getElementById('sb-table').textContent = table;
    document.getElementById('sb-date').textContent  = curDate;
    document.getElementById('sb-time').textContent  = start+' – '+end;
    document.getElementById('sb-dur').textContent   = fmtDur(h);
    document.getElementById('sb-price').textContent = Math.round(h*RATE_PER_HOUR)+' ₽';
    sel = {row, start, end, table};
  }

  function showPopup(targetEl){
    const popup=document.getElementById('partner-popup'); popup.style.display='block';
    const r=targetEl.getBoundingClientRect(), pw=276, ph=popup.offsetHeight;
    let left=r.left+r.width/2-pw/2, top=r.top-ph-14;
    left=Math.max(8,Math.min(left,window.innerWidth-pw-8));
    if(top<8) top=r.bottom+14;
    popup.style.left=left+'px'; popup.style.top=top+'px';
  }
  function hidePopup(){ document.getElementById('partner-popup').style.display='none'; }

  let bk = {row:0, table:(TABLES[0]||''), start:480+10*60, dur:60};
  const clampStart = m => Math.max(START_MIN, Math.min(m, END_HOUR*60-30));
  function bkRefresh(){
    document.getElementById('bk-table').textContent = bk.table;
    document.getElementById('bk-date').textContent  = curDate;
    document.getElementById('bk-start').textContent = toStr(bk.start);
    document.getElementById('bk-durv').textContent  = fmtDur(bk.dur/60);
    document.getElementById('bk-price').textContent = Math.round(bk.dur/60*RATE_PER_HOUR)+' ₽';
  }
  let pendingBooking = null;
  function openBooking(row, startMin){
    // Гейт входа: бронировать может только вошедший клиент.
    // Не вошёл → запоминаем намерение и открываем окно входа.
    if(!window.ttAuth || !ttAuth.isLoggedIn()){
      pendingBooking = {row, startMin};
      if(window.openAuth) openAuth('login');
      return;
    }
    // Бронь дальше недели — только через администратора (даты сравниваем как строки Y-m-d).
    if(MAX_BOOK_DATE && BOOK_DATE && BOOK_DATE > MAX_BOOK_DATE){
      showFaraway();
      return;
    }
    bk.row=row; bk.table=TABLES[row]; bk.start=clampStart(Math.floor(startMin/30)*30); bk.dur=60;
    document.getElementById('bk-form').style.display='block';
    document.getElementById('bk-success').style.display='none';
    document.getElementById('bk-error').style.display='none';
    prefillIdentity();
    bkRefresh();
    document.getElementById('book-modal').classList.add('open');
  }
  function closeBooking(){ document.getElementById('book-modal').classList.remove('open'); }

  // Экран «бронь дальше недели — через администратора»
  function showFaraway(){
    document.getElementById('bk-form').style.display='none';
    document.getElementById('bk-success').style.display='none';
    document.getElementById('bk-faraway').style.display='block';
    var ph = document.getElementById('bk-admin-phone');
    ph.textContent = ADMIN_PHONE || 'администратору';
    ph.href = ADMIN_PHONE ? ('tel:' + ADMIN_PHONE.replace(/[^\d+]/g,'')) : '#';
    document.getElementById('book-modal').classList.add('open');
  }

  // Подставляем имя/телефон вошедшего клиента и прячем эти поля.
  function prefillIdentity(){
    const u = (window.ttAuth && ttAuth.get()) || null;
    const nameEl  = document.getElementById('bk-name');
    const phoneEl = document.getElementById('bk-phone');
    const asEl    = document.getElementById('bk-asuser');
    if(u && u.phone){
      nameEl.value  = u.name || '';
      phoneEl.value = u.phone;
      nameEl.closest('.bk-field').style.display  = u.name ? 'none' : 'block';
      phoneEl.closest('.bk-field').style.display = 'none';
      if(asEl){ asEl.style.display='flex'; asEl.querySelector('span').textContent = (u.name ? u.name+' · ' : '') + u.phone; }
    } else {
      nameEl.closest('.bk-field').style.display  = 'block';
      phoneEl.closest('.bk-field').style.display = 'block';
      if(asEl) asEl.style.display='none';
    }
  }

  // Колбэк из auth.js после успешного входа: продолжаем отложенную бронь.
  // Возврат true → auth.js закроет окно входа без общего экрана «Вы вошли».
  window.ttOnLogin = function(user){
    if(pendingBooking){
      const p = pendingBooking; pendingBooking = null;
      setTimeout(()=>openBooking(p.row, p.startMin), 40);
      return true;
    }
    return false;
  };

  document.querySelectorAll('[data-step]').forEach(b=>{
    b.addEventListener('click',()=>{
      const s=b.dataset.step;
      if(s==='start-1') bk.start=clampStart(bk.start-30);
      if(s==='start1')  bk.start=clampStart(bk.start+30);
      if(s==='dur-1')   bk.dur=Math.max(30,bk.dur-30);
      if(s==='dur1')    bk.dur=Math.min(180,bk.dur+30);
      if(bk.start+bk.dur>END_HOUR*60) bk.dur=END_HOUR*60-bk.start;
      bkRefresh();
    });
  });
  document.getElementById('bk-close').addEventListener('click',closeBooking);
  document.getElementById('book-modal').addEventListener('click',e=>{ if(e.target.id==='book-modal') closeBooking(); });
  function showBkError(msg){
    const el=document.getElementById('bk-error');
    el.textContent=msg; el.style.display=msg?'block':'none';
  }
  document.getElementById('bk-confirm').addEventListener('click', async ()=>{
    const start=toStr(bk.start), end=toStr(bk.start+bk.dur);
    const name=document.getElementById('bk-name').value.trim();
    const phone=document.getElementById('bk-phone').value.trim();
    showBkError('');

    if(name.length<2){ showBkError('Укажите имя.'); return; }
    if(phone.length<5){ showBkError('Укажите телефон.'); return; }
    const resourceId = TABLE_IDS[bk.row];
    if(!resourceId || !BRANCH_ID || !SERVICE_ID || !BOOK_DATE){
      showBkError('Бронирование с сайта пока не настроено для этого клуба.'); return;
    }

    const btn=document.getElementById('bk-confirm');
    btn.disabled=true; const label=btn.textContent; btn.textContent='Бронируем…';
    try{
      const res=await fetch('/book',{
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({
          name, phone,
          branch_id:BRANCH_ID, service_offering_id:SERVICE_ID, resource_id:resourceId,
          date:BOOK_DATE, start, end
        })
      });
      const data=await res.json().catch(()=>({}));
      if(!res.ok || !data.ok){
        showBkError(data.message || 'Не удалось забронировать. Попробуйте другое время.');
        return;
      }
      // Успех: показываем бронь в сетке и экран подтверждения
      EVENTS.push({row:bk.row, type:'mybooking', title:'Моя бронь', start, end});
      updateSidebar(bk.row, start, end, bk.table);
      rerender(); hidePopup();
      document.getElementById('bk-form').style.display='none';
      document.getElementById('bk-success').style.display='block';
      const acc = data.account_url ? `<br><a href="${data.account_url}" style="color:var(--green);text-decoration:underline;text-underline-offset:2px;">Мои брони</a>` : '';
      document.getElementById('bk-success-text').innerHTML = `${bk.table} · ${curDate}<br>${start} – ${end}${acc}`;
    }catch(e){
      showBkError('Сеть недоступна. Попробуйте ещё раз.');
    }finally{
      btn.disabled=false; btn.textContent=label;
    }
  });
  document.getElementById('bk-success-ok').addEventListener('click',()=>{ closeBooking(); toast('Бронь добавлена в расписание'); });
  document.getElementById('bk-faraway-ok').addEventListener('click', closeBooking);
  document.getElementById('sb-book').addEventListener('click',()=>{ openBooking(sel.row, toMin(sel.start)); });

  document.getElementById('sched-root').addEventListener('click', e=>{
    const evEl = e.target.closest('.ev');
    if(evEl){
      if(evEl.dataset.readonly) return;
      const row=+evEl.dataset.row, start=evEl.dataset.start, end=evEl.dataset.end, table=evEl.dataset.table;
      sel={row,start,end,table}; renderGrid(); updateSidebar(row,start,end,table);
      if(evEl.dataset.partner){ const t=document.querySelector('.ev-find-partner.sel'); if(t) showPopup(t); }
      else hidePopup();
      return;
    }
    const body=e.target.closest('.row-body');
    if(body){
      const row=+body.dataset.row;
      const r=body.getBoundingClientRect();
      const frac=Math.max(0,Math.min(1,(e.clientX-r.left)/r.width));
      const mins=START_MIN+frac*SPAN_MIN;
      hidePopup(); openBooking(row, mins);
    }
  });
  document.getElementById('sched-root').addEventListener('mousemove', e=>{
    const body=e.target.closest('.row-body');
    document.querySelectorAll('.hover-ghost').forEach(g=>{ if(!body || g.parentElement!==body) g.style.display='none'; });
    if(!body) return;
    const ghost=body.querySelector('.hover-ghost');
    if(e.target.closest('.ev')){ ghost.style.display='none'; return; }
    const r=body.getBoundingClientRect();
    const frac=Math.max(0,Math.min(1,(e.clientX-r.left)/r.width));
    let mins=START_MIN+frac*SPAN_MIN; mins=Math.floor(mins/30)*30; mins=clampStart(mins);
    ghost.style.left=tp(toStr(mins)).toFixed(3)+'%';
    ghost.style.width=(60/SPAN_MIN*100).toFixed(3)+'%';
    ghost.style.display='flex';
    ghost.textContent=toStr(mins);
  });
  document.getElementById('sched-root').addEventListener('mouseleave', ()=>{
    document.querySelectorAll('.hover-ghost').forEach(g=>g.style.display='none');
  });
  document.getElementById('sched-list').addEventListener('click', e=>{
    const a=e.target.closest('[data-book-row]'); if(!a) return;
    openBooking(+a.dataset.bookRow, toMin(a.dataset.bookStart));
  });
  document.addEventListener('click', e=>{
    if(!e.target.closest('.ev-find-partner') && !e.target.closest('#partner-popup') && !e.target.closest('.row-body')) hidePopup();
  });

  let viewMode='grid';
  function setView(mode){
    viewMode=mode;
    document.querySelectorAll('.vt').forEach(v=>v.classList.remove('active'));
    document.getElementById(mode==='grid'?'vt-grid':'vt-list').classList.add('active');
    document.getElementById('sched-scroll').style.display = mode==='grid'?'block':'none';
    document.getElementById('sched-list').style.display   = mode==='grid'?'none':'block';
    hidePopup();
    rerender();
    if(mode==='grid') requestAnimationFrame(()=>{ document.getElementById('sched-scroll').scrollLeft=(toMin('15:00')-START_MIN)/SPAN_MIN*2040; maybePopup(); });
  }
  document.getElementById('vt-grid').addEventListener('click',()=>setView('grid'));
  document.getElementById('vt-list').addEventListener('click',()=>setView('list'));

  // Выбор даты в календаре → перезагрузка расписания на этот день (?date=YYYY-MM-DD).
  // Сервер отрендерит брони выбранного дня, и новые брони пойдут на ту же дату.
  window.onScheduleDate = function(val){
    // val вида «25 июня, ср» — год календарь не передаёт, берём из текущей даты.
    const MON = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
    const m = String(val).match(/(\d{1,2})\s+([а-яё]+)/i);
    if(!m) return;
    const day = parseInt(m[1], 10);
    const mon = MON.indexOf(m[2].toLowerCase());
    if(mon < 0) return;
    const year = (BOOK_DATE && BOOK_DATE.slice(0,4)) || String(new Date().getFullYear());
    const iso = year + '-' + String(mon+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
    window.location.href = '/schedule?date=' + iso;
  };

  let toastT;
  function toast(msg){
    const t=document.getElementById('toast');
    t.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" class="ico" style="stroke:var(--green)"><path d="m5 12 5 5 9-11"/></svg>'+msg;
    t.classList.add('show'); clearTimeout(toastT); toastT=setTimeout(()=>t.classList.remove('show'),2600);
  }

  function maybePopup(){
    const fp=document.querySelector('.ev-find-partner.sel'); if(!fp) return;
    const r=fp.getBoundingClientRect();
    if(r.left>=120 && r.right<=window.innerWidth) showPopup(fp); else hidePopup();
  }
  renderGrid();
  updateSidebar(sel.row, sel.start, sel.end, sel.table);
  // Баннер «дальше недели — через администратора»
  (function(){
    var far = MAX_BOOK_DATE && BOOK_DATE && BOOK_DATE > MAX_BOOK_DATE;
    var banner = document.getElementById('faraway-banner');
    if(!banner) return;
    banner.style.display = far ? 'flex' : 'none';
    var ph = document.getElementById('faraway-banner-phone');
    if(ph && ADMIN_PHONE){ ph.textContent = ADMIN_PHONE; ph.href = 'tel:' + ADMIN_PHONE.replace(/[^\d+]/g,''); }
  })();
  const scr=document.getElementById('sched-scroll');
  function gotoAfternoon(){ scr.scrollLeft = (toMin('15:00')-START_MIN)/SPAN_MIN * 2040; }
  gotoAfternoon();
  requestAnimationFrame(gotoAfternoon);
  if(document.fonts && document.fonts.ready) document.fonts.ready.then(gotoAfternoon);
  setTimeout(()=>{ gotoAfternoon(); maybePopup();
    setTimeout(()=> window.addEventListener('scroll', hidePopup, true), 350);
  }, 240);
})();
</script>
<script src="{{ asset('js/dropdowns.js') }}"></script>
<script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
