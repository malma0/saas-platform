<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Поиск партнёра — Теннис Клуб НСК</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/mobile.css') }}">
<style>
:root{
  --bg:#0a0b09;--panel:#101210;--panel-2:#151714;--panel-3:#191c16;
  --line:rgba(255,255,255,.08);--line-soft:rgba(255,255,255,.05);
  --txt:#f0f1ec;--muted:#8c8f86;--muted-2:#6f726a;
  --green:#c6e21a;--green-deep:#a8c012;
  --blue:#4a90e2;--orange:#e2954a;--purple:#9a7ce2;--red:#e26a5a;
  --st-active:#c6e21a;--st-resp:#4a90e2;--st-wait:#e2954a;--st-conf:#9a7ce2;--st-past:#71746b;
  --radius:14px;
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{background:var(--bg);color:var(--txt);font-family:'Manrope',system-ui,sans-serif;-webkit-font-smoothing:antialiased;}
body{padding:0 22px 34px;}
.wrap{max-width:1660px;margin:0 auto;}
a{color:inherit;text-decoration:none;}
.green{color:var(--green);}
svg{display:block;}
.ico{stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round;}

/* ── HEADER ─────────────────────────── */
header{display:flex;align-items:center;gap:22px;padding:18px 4px 22px;}
.logo{display:flex;align-items:center;gap:14px;flex:none;}
.logo-mark{width:48px;height:48px;flex:none;}
.logo-txt .brand{font-size:23px;font-weight:800;letter-spacing:.5px;line-height:1;}
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
.head-phone{margin-left:8px;}
.head-phone b{font-size:18px;letter-spacing:.3px;}
.btn-cabinet{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.06);border:1px solid var(--line);border-radius:10px;padding:10px 16px;font-size:14px;font-weight:600;color:#e7e8e3;cursor:pointer;margin-left:14px;white-space:nowrap;flex:none;transition:background .14s;}
.btn-cabinet:hover{background:rgba(255,255,255,.1);}

/* ── PAGE HERO ──────────────────────── */
.phero{position:relative;border-radius:var(--radius);overflow:hidden;min-height:168px;border:1px solid var(--line-soft);margin-bottom:18px;background:var(--panel);display:flex;align-items:center;}
.phero-img{position:absolute;right:0;top:0;width:58%;height:100%;z-index:0;object-fit:cover;}
.phero .veil{position:absolute;inset:0;z-index:1;pointer-events:none;
  background:linear-gradient(90deg,var(--panel) 0%,var(--panel) 28%,rgba(16,18,16,.94) 42%,rgba(16,18,16,.5) 60%,rgba(16,18,16,.35) 78%,rgba(16,18,16,.7) 100%);}
.phero-txt{position:relative;z-index:2;padding:34px 46px;max-width:560px;}
.phero-txt h1{font-size:42px;font-weight:800;letter-spacing:-.6px;line-height:1.02;}
.phero-txt p{font-size:15px;color:#a9aba4;margin-top:14px;line-height:1.5;max-width:470px;}
.phero-actions{position:absolute;z-index:3;right:44px;top:50%;transform:translateY(-50%);display:flex;gap:14px;}
.btn-hero{display:flex;align-items:center;gap:10px;border-radius:11px;padding:17px 28px;font-size:15.5px;font-weight:700;cursor:pointer;border:none;white-space:nowrap;transition:filter .14s,background .14s;}
.btn-hero.lime{background:var(--green);color:#13160a;}
.btn-hero.lime:hover{filter:brightness(1.07);}
.btn-hero.dark{background:rgba(255,255,255,.05);border:1px solid var(--line);color:#e7e8e3;}
.btn-hero.dark:hover{background:rgba(255,255,255,.1);}

/* ── FILTERS ────────────────────────── */
.filters{display:flex;align-items:flex-end;gap:12px;background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:16px 18px;margin-bottom:18px;flex-wrap:wrap;}
.f-field{display:flex;flex-direction:column;gap:7px;min-width:170px;flex:1;}
.f-field .fl{font-size:11px;font-weight:600;letter-spacing:.6px;color:var(--muted);text-transform:uppercase;}
.f-sel{display:flex;align-items:center;gap:10px;background:#0c0d0b;border:1px solid var(--line);border-radius:10px;padding:11px 13px;cursor:pointer;transition:border-color .14s;}
.f-sel:hover{border-color:rgba(198,226,26,.35);}
.f-sel .fi{color:var(--green);flex:none;}
.f-sel .fv{flex:1;font-size:14.5px;font-weight:600;white-space:nowrap;}
.f-sel .chev{color:var(--muted);}
.f-btn{display:flex;align-items:center;gap:8px;border-radius:10px;padding:12px 20px;font-size:14.5px;font-weight:700;cursor:pointer;white-space:nowrap;border:1px solid var(--line);background:#0c0d0b;transition:all .14s;height:44px;}
.f-btn.search{color:var(--green);border-color:rgba(198,226,26,.4);}
.f-btn.search:hover{background:rgba(198,226,26,.08);}
.f-btn.reset{color:var(--muted);}
.f-btn.reset:hover{color:#dadcd5;border-color:rgba(255,255,255,.18);}

/* ── LAYOUT ─────────────────────────── */
.pl{display:grid;grid-template-columns:1fr 340px;gap:18px;align-items:start;}
.pl-main{min-width:0;}

/* ── TABS ───────────────────────────── */
.ptabs{display:flex;gap:4px;border-bottom:1px solid var(--line);margin-bottom:20px;}
.ptab{padding:13px 22px;font-size:15px;font-weight:600;color:var(--muted);cursor:pointer;position:relative;background:none;border:none;transition:color .14s;}
.ptab:hover{color:#dadcd5;}
.ptab.active{color:var(--green);}
.ptab.active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:var(--green);border-radius:2px;}
.tab-content{display:none;}
.tab-content.active{display:block;}

/* ── AVATARS ────────────────────────── */
.av{border-radius:50%;flex:none;display:grid;place-items:center;font-weight:700;color:#fff;overflow:hidden;position:relative;}
.av::after{content:"";position:absolute;inset:0;background:linear-gradient(150deg,rgba(255,255,255,.18),rgba(0,0,0,.15));}
.av span{position:relative;z-index:1;}

/* ── BADGES / DOTS ──────────────────── */
.dot{width:9px;height:9px;border-radius:50%;flex:none;display:inline-block;}
.d-active{background:var(--st-active);}
.d-resp{background:var(--st-resp);}
.d-wait{background:var(--st-wait);}
.d-conf{background:var(--st-conf);}
.d-past{background:var(--st-past);}
.lvl{display:inline-flex;align-items:center;gap:7px;font-size:13.5px;font-weight:600;}
.lvl-intermediate{color:var(--purple);}
.lvl-advanced{color:#e0c14a;}
.status-txt{display:inline-flex;align-items:center;gap:8px;font-size:13.5px;font-weight:600;}
.s-active{color:var(--st-active);}
.s-resp{color:var(--st-resp);}
.s-wait{color:var(--st-wait);}
.s-conf{color:var(--st-conf);}
.s-past{color:var(--st-past);}
.cnt-badge{font-size:12px;font-weight:700;padding:5px 11px;border-radius:7px;white-space:nowrap;}
.cnt-on{color:var(--green);background:rgba(198,226,26,.1);border:1px solid rgba(198,226,26,.4);}
.cnt-off{color:var(--muted);background:rgba(255,255,255,.04);border:1px solid var(--line);}
.new-badge{font-size:11px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#13160a;background:var(--red);padding:3px 9px;border-radius:6px;}

/* ── CALENDAR ────────────────────────── */
.cal-legend{display:flex;align-items:center;gap:22px;margin-bottom:16px;flex-wrap:wrap;}
.cal-legend .li{display:flex;align-items:center;gap:8px;font-size:13px;color:#aaaca5;}
.cal-legend .li-info{margin-left:auto;display:flex;align-items:flex-start;gap:8px;font-size:12px;color:var(--muted);max-width:330px;line-height:1.4;}
.cal-panel{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;}
.cal-scroll{overflow-x:auto;}
.cal{display:grid;grid-template-columns:64px repeat(7,minmax(132px,1fr));min-width:980px;}
.cal-head{display:contents;}
.cal-hcell{padding:13px 8px;text-align:center;font-size:13px;font-weight:700;border-bottom:1px solid var(--line);border-left:1px solid var(--line-soft);}
.cal-hcell .d-day{color:#e7e8e3;}
.cal-hcell .d-date{font-size:11.5px;font-weight:500;color:var(--muted);margin-top:3px;}
.cal-hcell.weekend .d-day{color:var(--red);}
.cal-hcell.weekend .d-date{color:#b06a5e;}
.cal-hcell.tcorner{border-left:none;text-align:left;padding-left:14px;color:var(--muted);font-size:11px;letter-spacing:.5px;}
.cal-timecol{position:relative;border-right:1px solid var(--line-soft);}
.cal-timecol .th{position:absolute;left:0;right:0;text-align:center;font-size:11.5px;color:var(--muted-2);transform:translateY(-50%);}
.cal-col{position:relative;border-left:1px solid var(--line-soft);}
.cal-col .hline{position:absolute;left:0;right:0;height:1px;background:rgba(255,255,255,.035);}
.cal-card{position:absolute;left:6px;right:6px;border-radius:10px;padding:9px 10px;cursor:pointer;border:1px solid;overflow:hidden;transition:filter .12s,transform .12s;z-index:2;}
.cal-card:hover{filter:brightness(1.1);transform:translateY(-1px);}
.cal-card .cc-top{display:flex;align-items:center;gap:8px;}
.cal-card .cc-name{font-size:13px;font-weight:700;line-height:1.1;}
.cal-card .cc-lvl{font-size:11.5px;font-weight:600;margin-top:2px;}
.cal-card .cc-loc{font-size:11px;color:var(--muted);margin-top:6px;}
.cal-card .cc-status{display:flex;align-items:center;gap:6px;font-size:11px;margin-top:5px;font-weight:600;}
.cal-card.c-active{background:rgba(50,70,30,.4);border-color:rgba(198,226,26,.35);}
.cal-card.c-resp{background:rgba(28,52,82,.42);border-color:rgba(74,144,226,.38);}
.cal-card.c-conf{background:rgba(58,42,86,.42);border-color:rgba(154,124,226,.38);}
.cal-card.c-past{background:rgba(40,42,38,.5);border-color:var(--line);opacity:.65;}
.cal-card.c-multi{background:linear-gradient(135deg,#5a3410,#7a4514);border:1.5px solid var(--orange);box-shadow:0 0 0 1px rgba(226,149,74,.3),0 8px 26px rgba(226,149,74,.25);display:flex;flex-direction:column;gap:4px;justify-content:center;z-index:5;}
.cal-card.c-multi .m-count{font-size:16px;font-weight:800;color:#fff;}
.cal-card.c-multi .m-time{font-size:12px;color:#f0d6b8;font-weight:600;}
.cal-card.c-multi .m-hint{font-size:11px;color:#e8c49a;margin-top:2px;}
.cal-card.c-multi.sel{box-shadow:0 0 0 2px var(--orange),0 8px 30px rgba(226,149,74,.4);}

/* ── REQUEST / RESPONSE LISTS ───────── */
.list-meta{font-size:13.5px;color:var(--muted);margin-bottom:14px;}
.list-meta b{color:#dadcd5;font-weight:700;}
.rcard{background:var(--panel);border:1px solid var(--line);border-radius:12px;margin-bottom:12px;overflow:hidden;transition:border-color .14s;}
.rcard.open{border-color:rgba(198,226,26,.3);}
.rrow{display:grid;grid-template-columns:150px 1fr auto auto auto 28px;align-items:center;gap:18px;padding:16px 18px;cursor:pointer;}
.rrow:hover{background:rgba(255,255,255,.015);}
.rcell-date{display:flex;align-items:flex-start;gap:9px;}
.rcell-date .ri{color:var(--muted);flex:none;margin-top:1px;}
.rcell-date .rd-1{font-size:14px;font-weight:700;}
.rcell-date .rd-2{font-size:13px;color:var(--muted);margin-top:2px;}
.rcell-loc{display:flex;align-items:flex-start;gap:9px;min-width:0;}
.rcell-loc .ri{color:var(--muted);flex:none;margin-top:1px;}
.rcell-loc .rl-1{font-size:14px;font-weight:600;}
.rcell-loc .rl-2{font-size:12.5px;color:var(--muted);margin-top:2px;}
.rcell-player{display:flex;align-items:center;gap:11px;}
.rcell-player .rp-name{font-size:14px;font-weight:700;}
.rcell-player .rp-ttw{font-size:12.5px;color:var(--muted);margin-top:1px;}
.r-chev{color:var(--muted);transition:transform .2s;display:grid;place-items:center;}
.rcard.open .r-chev{transform:rotate(180deg);}
.rbody{border-top:1px solid var(--line);padding:20px 18px;display:none;}
.rcard.open .rbody{display:block;}
.rb-grid{display:grid;grid-template-columns:230px 1fr;gap:24px;}
.author-card .ac-top{display:flex;align-items:center;gap:13px;margin-bottom:16px;}
.author-card .ac-role{font-size:11.5px;color:var(--muted);}
.author-card .ac-name{font-size:16px;font-weight:700;margin-top:2px;}
.author-card .ac-ttw{font-size:13px;color:var(--green);font-weight:600;margin-top:2px;}
.author-card .ac-line{display:flex;align-items:flex-start;gap:9px;font-size:13px;color:#c2c4bd;margin-bottom:9px;}
.author-card .ac-line .ai{color:var(--muted);flex:none;margin-top:1px;}
.author-card .ac-note{font-size:13px;color:#b6b8b1;line-height:1.5;margin-top:12px;font-style:italic;}
.resp-info{display:flex;align-items:flex-start;gap:11px;background:rgba(74,144,226,.08);border:1px solid rgba(74,144,226,.25);border-radius:10px;padding:13px 15px;margin-bottom:18px;}
.resp-info .ii{color:var(--blue);flex:none;margin-top:1px;}
.resp-info p{font-size:13px;color:#bcc4cf;line-height:1.45;}
.resp-title{font-size:14.5px;font-weight:700;margin-bottom:13px;}
.responder{display:flex;align-items:center;gap:13px;padding:13px 14px;border-radius:11px;border:1px solid var(--line);margin-bottom:10px;background:var(--panel-2);}
.responder.best{border-color:rgba(198,226,26,.45);background:rgba(198,226,26,.05);}
.responder .rs-name{font-size:14.5px;font-weight:700;}
.responder .rs-sub{font-size:12px;color:var(--muted);margin-top:2px;}
.responder .rs-mid{flex:1;min-width:0;}
.responder .rs-match{font-size:13px;color:#c2c4bd;}
.responder .rs-when{font-size:12px;color:var(--muted);margin-top:2px;}
.responder .rs-ttw{font-size:12.5px;color:var(--green);font-weight:700;}
.rb-author{display:grid;grid-template-columns:230px 1fr;gap:24px;}
.about-req{display:flex;align-items:center;gap:9px;font-size:14px;font-weight:700;margin-bottom:11px;}
.about-req .ab-ico{width:22px;height:22px;border-radius:6px;background:rgba(255,255,255,.06);display:grid;place-items:center;color:var(--muted);}
.about-text{font-size:13.5px;color:#bdbfb8;line-height:1.55;margin-bottom:16px;}
.status-banner{display:flex;align-items:flex-start;gap:11px;border-radius:10px;padding:14px 16px;margin-bottom:16px;}
.status-banner.wait{background:rgba(226,149,74,.08);border:1px solid rgba(226,149,74,.3);}
.status-banner .sb-ico{flex:none;margin-top:1px;}
.status-banner .sb-1{font-size:13.5px;font-weight:700;color:var(--orange);}
.status-banner .sb-2{font-size:12.5px;color:#bdbfb8;margin-top:4px;line-height:1.4;}
.status-banner .sb-3{font-size:12px;color:var(--green);margin-top:8px;display:flex;align-items:center;gap:6px;}

/* buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:9px;font-weight:700;font-size:13.5px;cursor:pointer;border:1px solid transparent;padding:11px 18px;white-space:nowrap;transition:all .13s;}
.btn-lime{background:var(--green);color:#13160a;}
.btn-lime:hover{filter:brightness(1.07);}
.btn-red{background:transparent;border-color:rgba(226,106,90,.5);color:var(--red);}
.btn-red:hover{background:rgba(226,106,90,.1);}
.btn-dark{background:rgba(255,255,255,.05);border-color:var(--line);color:#e0e1db;}
.btn-dark:hover{background:rgba(255,255,255,.1);}
.btn-ico{width:42px;height:42px;padding:0;background:rgba(255,255,255,.05);border-color:var(--line);color:var(--muted);}
.btn-ico:hover{background:rgba(255,255,255,.1);color:#dadcd5;}

/* ── SIDEBAR ────────────────────────── */
.side-card{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:24px 22px;}
.side-card + .side-card{margin-top:16px;}
.side-card h3{font-size:18px;font-weight:800;margin-bottom:20px;}
.step{display:flex;gap:14px;margin-bottom:18px;}
.step:last-child{margin-bottom:0;}
.step .sn{width:30px;height:30px;border-radius:50%;border:1.5px solid;display:grid;place-items:center;font-size:14px;font-weight:700;flex:none;}
.step:nth-child(2) .sn{border-color:var(--st-active);color:var(--st-active);}
.step:nth-child(3) .sn{border-color:var(--st-resp);color:var(--st-resp);}
.step:nth-child(4) .sn{border-color:#e0c14a;color:#e0c14a;}
.step:nth-child(5) .sn{border-color:var(--st-conf);color:var(--st-conf);}
.step .st-1{font-size:14.5px;font-weight:700;}
.step .st-2{font-size:12.5px;color:var(--muted);margin-top:4px;line-height:1.45;}
.sl{display:flex;gap:11px;margin-bottom:15px;}
.sl:last-child{margin-bottom:0;}
.sl .sl-dot{width:11px;height:11px;border-radius:50%;flex:none;margin-top:4px;}
.sl .sl-1{font-size:13.5px;font-weight:700;}
.sl .sl-2{font-size:12px;color:var(--muted);margin-top:3px;line-height:1.4;}
.slot-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:6px;}
.slot-head .sh-title{font-size:17px;font-weight:800;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.slot-close{color:var(--muted);cursor:pointer;flex:none;}
.slot-close:hover{color:#dadcd5;}
.slot-sub{font-size:13px;color:var(--muted);margin-bottom:8px;}
.slot-note{display:flex;align-items:flex-start;gap:8px;font-size:12px;color:var(--muted);line-height:1.4;padding-bottom:16px;margin-bottom:4px;border-bottom:1px solid var(--line);}
.slot-note .ni{color:var(--green);flex:none;margin-top:1px;}
.offer{border:1px solid var(--line);border-radius:12px;padding:14px;margin-top:14px;background:var(--panel-2);transition:border-color .14s;}
.offer.hot{border-color:rgba(198,226,26,.4);background:rgba(198,226,26,.04);}
.offer .of-top{display:flex;align-items:flex-start;gap:11px;}
.offer .of-name{font-size:14.5px;font-weight:700;}
.offer .of-lvl{font-size:12.5px;font-weight:600;margin-top:2px;}
.offer .of-pub{font-size:11.5px;color:var(--muted);text-align:right;flex:none;line-height:1.4;}
.offer .of-loc{font-size:12.5px;color:var(--muted);margin-top:3px;}
.offer .of-desc{font-size:12.5px;color:#b6b8b1;line-height:1.45;margin:10px 0 12px;}
.offer .of-actions{display:flex;gap:8px;}
.offer .of-actions .btn{flex:1;padding:10px 12px;}
.offer-done{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px;border-radius:9px;font-size:13px;font-weight:600;color:var(--muted);background:rgba(255,255,255,.03);border:1px solid var(--line);}
.offer-replied{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px;border-radius:9px;font-size:13px;font-weight:700;color:var(--green);background:rgba(198,226,26,.06);border:1px solid rgba(198,226,26,.3);}
.slot-foot{display:flex;align-items:center;justify-content:center;gap:8px;font-size:12px;color:var(--muted);margin-top:16px;}

@media(max-width:1200px){
  .pl{grid-template-columns:1fr;}
  .phero-actions{position:static;transform:none;padding:0 46px 30px;}
}
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
    <a href="/schedule">Расписание</a>
    <a href="#">Турниры</a>
    <a href="#">Тренеры</a>
    <a href="/partners" class="active">Партнёры</a>
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
    Войти
    <svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg>
  </button>
</header>

<!-- PAGE HERO -->
<div class="phero">
  <img class="phero-img" src="{{ asset('images/hero-player.jpg') }}" alt="">
  <div class="veil"></div>
  <div class="phero-txt">
    <h1>Поиск партнёра для игры</h1>
    <p>Найдите партнёра по уровню и времени. Откликайтесь на заявки других игроков или создайте свою и ждите откликов!</p>
  </div>
  <div class="phero-actions">
    <button class="btn-hero lime"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M12 5v14M5 12h14"/></svg> Разместить заявку</button>
    <button class="btn-hero dark"><svg width="17" height="17" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="m10 9 5 3-5 3z" fill="currentColor" stroke="none"/></svg> Как это работает?</button>
  </div>
</div>

<!-- FILTERS -->
<div class="filters">
  <div class="f-field">
    <span class="fl">Неделя / дата</span>
    <div class="f-sel" data-dd data-dd-callback="onPartnerFilter" data-dd-options="Эта неделя|Следующая неделя|Через 2 недели|Через 3 недели"><span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg></span><span class="fv" data-dd-val>Эта неделя</span><span class="chev"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span></div>
  </div>
  <div class="f-field">
    <span class="fl">Время</span>
    <div class="f-sel" data-dd data-dd-callback="onPartnerFilter" data-dd-options="Весь день|Утро (08–12)|День (12–17)|Вечер (17–23)"><span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span><span class="fv" data-dd-val>Весь день</span><span class="chev"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span></div>
  </div>
  <div class="f-field">
    <span class="fl">Уровень</span>
    <div class="f-sel" data-dd data-dd-callback="onPartnerFilter" data-dd-options="Все уровни|Beginner|Intermediate|Advanced|Pro"><span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M6 20V10M12 20V4M18 20v-7"/></svg></span><span class="fv" data-dd-val>Все уровни</span><span class="chev"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span></div>
  </div>
  <div class="f-field">
    <span class="fl">Клуб / локация</span>
    <div class="f-sel" data-dd data-dd-callback="onPartnerFilter" data-dd-options="Все клубы и локации|Топ-Спин, Новосибирск|Арена, Новосибирск|Красный проспект 2/1"><span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg></span><span class="fv" data-dd-val>Все клубы и локации</span><span class="chev"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span></div>
  </div>
  <button class="f-btn search"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg> Поиск</button>
  <button class="f-btn reset"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg> Сбросить фильтры</button>
</div>

<!-- MAIN LAYOUT -->
<div class="pl">
  <div class="pl-main">
    <div class="ptabs">
      <button class="ptab active" data-tab="available">Доступные заявки</button>
      <button class="ptab" data-tab="mine">Мои заявки</button>
      <button class="ptab" data-tab="responses">Мои отклики</button>
    </div>
    <div class="tab-content active" id="tab-available"></div>
    <div class="tab-content" id="tab-mine"></div>
    <div class="tab-content" id="tab-responses"></div>
  </div>
  <aside class="pl-side" id="pl-side"></aside>
</div>

</div><!-- /wrap -->
<script src="{{ asset('js/partners.js') }}"></script>
<script src="{{ asset('js/dropdowns.js') }}"></script>
<script src="{{ asset('js/auth.js') }}"></script>
<script src="{{ asset('js/mobile.js') }}"></script>
</body>
</html>
