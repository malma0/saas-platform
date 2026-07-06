@php
  $coachData = $coaches->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'rate' => $c->hourlyRubles()])->values();
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Тренеры — Теннис Клуб НСК</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="{{ asset('js/device.js') }}"></script>
<style>
  :root{--bg:#0a0b09;--panel:#101210;--field:rgba(255,255,255,.04);--line:rgba(255,255,255,.08);
    --txt:#f0f1ec;--muted:#8c8f86;--muted-2:#6f726a;--green:#c6e21a;--green-deep:#9fb50f;--radius:16px;}
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
  .ptitle{font-size:25px;font-weight:800;letter-spacing:-.3px;margin-top:6px;}
  .psub{font-size:13px;color:var(--muted);margin-top:6px;}
  .clist{margin-top:18px;display:flex;flex-direction:column;gap:12px;}
  .ccard{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:16px;}
  .ctop{display:flex;align-items:center;gap:13px;}
  .cav{width:56px;height:56px;border-radius:14px;flex:none;display:grid;place-items:center;font-size:19px;font-weight:800;color:#13160a;background:linear-gradient(135deg,#c6e21a,#9fb50f);}
  .cname{font-size:16px;font-weight:800;}
  .crank{display:inline-block;margin-top:4px;font-size:11px;font-weight:700;color:var(--green);background:rgba(198,226,26,.13);border:1px solid rgba(198,226,26,.4);border-radius:6px;padding:2px 8px;}
  .cspec{font-size:13px;color:#cfd1ca;margin-top:13px;line-height:1.4;}
  .cmeta{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);margin-top:9px;}
  .cmeta svg{color:var(--green);flex:none;}
  .cfoot{display:flex;align-items:center;justify-content:space-between;margin-top:15px;}
  .cprice{font-size:18px;font-weight:800;}
  .cprice span{font-size:12px;color:var(--muted);font-weight:600;}
  .cbtn{background:var(--green);color:#13160a;font-weight:800;font-size:13.5px;border:none;border-radius:10px;padding:11px 18px;cursor:pointer;font-family:inherit;}
  .empty{margin-top:18px;background:var(--panel);border:1px dashed rgba(255,255,255,.14);border-radius:var(--radius);padding:34px;text-align:center;color:var(--muted);font-size:13.5px;}
  /* tabbar */
  .tabbar{position:fixed;left:0;right:0;bottom:0;z-index:100;display:flex;background:rgba(13,14,12,.96);-webkit-backdrop-filter:blur(14px);backdrop-filter:blur(14px);border-top:1px solid var(--line);padding:9px 4px calc(9px + env(safe-area-inset-bottom));}
  .tab{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;font-size:9.5px;font-weight:600;color:var(--muted);background:none;border:none;font-family:inherit;cursor:pointer;white-space:nowrap;overflow:hidden;}
  .tab.active{color:var(--green);}
  .tab svg{width:24px;height:24px;flex:none;}
  /* sheet */
  .sheet-overlay{position:absolute;inset:0;z-index:40;background:rgba(6,7,5,.6);display:none;}
  .sheet-overlay.open{display:block;}
  .sheet{position:absolute;left:0;right:0;bottom:0;z-index:41;background:#13150f;border-top-left-radius:22px;border-top-right-radius:22px;border:1px solid var(--line);border-bottom:none;padding:8px 18px calc(20px + env(safe-area-inset-bottom));transform:translateY(100%);transition:transform .25s;max-height:88%;overflow-y:auto;}
  .sheet.open{transform:translateY(0);}
  .sheet-grab{width:40px;height:4px;border-radius:2px;background:rgba(255,255,255,.2);margin:6px auto 12px;}
  .sheet h3{font-size:19px;font-weight:800;}
  .sheet .ssub{font-size:13px;color:var(--muted);margin:4px 0 14px;}
  .srow{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-top:1px solid var(--line);}
  .srow .k{font-size:13px;color:var(--muted);}
  .step{display:flex;align-items:center;gap:9px;}
  .step button{width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:var(--field);color:#fff;font-size:18px;cursor:pointer;}
  .step .sv{min-width:84px;text-align:center;font-weight:700;}
  .sfield{margin-top:12px;}
  .sfield label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px;}
  .sfield input{width:100%;background:var(--field);border:1px solid var(--line);border-radius:11px;padding:13px 14px;color:var(--txt);font-size:15px;font-family:inherit;outline:none;}
  .serr{display:none;color:#f0a090;font-size:13px;font-weight:600;margin-top:10px;}
  .stotal{display:flex;align-items:center;justify-content:space-between;margin:14px 0;padding-top:13px;border-top:1px solid var(--line);}
  .stotal .v{font-size:21px;font-weight:800;color:var(--green);}
  .sbook{width:100%;background:var(--green);color:#13160a;font-weight:800;font-size:16px;border:none;border-radius:12px;padding:15px;cursor:pointer;font-family:inherit;}
  .asuser{display:none;align-items:center;gap:8px;background:rgba(198,226,26,.08);border:1px solid rgba(198,226,26,.25);border-radius:10px;padding:9px 12px;margin-top:12px;font-size:12.5px;color:#cfe08a;}
  /* confirm */
  .confirm{position:absolute;inset:0;z-index:60;background:var(--bg);display:none;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:30px;}
  .confirm.open{display:flex;}
  .cf-ico{width:88px;height:88px;border-radius:50%;background:rgba(198,226,26,.12);border:2px solid var(--green);display:grid;place-items:center;color:var(--green);margin-bottom:22px;}
  .confirm h2{font-size:24px;font-weight:800;}
  .cf-card{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:16px 22px;margin:18px 0 24px;}
  .cf-card .cft{font-size:17px;font-weight:800;}
  .cf-card .cfd{font-size:13.5px;color:var(--muted);margin-top:6px;}
  .cf-actions{display:flex;flex-direction:column;gap:10px;width:100%;max-width:300px;}
  .btn-ghost{background:var(--field);border:1px solid var(--line);color:var(--txt);font-weight:700;font-size:14.5px;border-radius:12px;padding:14px;cursor:pointer;font-family:inherit;}
</style>
</head>
<body>
<div class="app">
  <div class="screen">
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

    <h1 class="ptitle">Тренеры</h1>
    <p class="psub">Запишитесь на индивидуальную тренировку онлайн</p>

    <div class="clist">
      @forelse($coaches as $c)
        <div class="ccard">
          <div class="ctop">
            <div class="cav">{{ $c->initials() }}</div>
            <div><div class="cname">{{ $c->name }}</div>@if($c->rank)<span class="crank">{{ $c->rank }}</span>@endif</div>
          </div>
          @if($c->specialization)<div class="cspec">{{ $c->specialization }}</div>@endif
          @if($c->experience_years)<div class="cmeta"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg> Опыт {{ $c->experience_years }}+ лет</div>@endif
          <div class="cfoot">
            <div class="cprice">{{ number_format($c->hourlyRubles(),0,'',' ') }} ₽ <span>/ 60 мин</span></div>
            <button class="cbtn" data-coach="{{ $c->id }}" data-name="{{ $c->name }}" data-rate="{{ $c->hourlyRubles() }}">Записаться</button>
          </div>
        </div>
      @empty
        <div class="empty">Тренеры пока не добавлены.</div>
      @endforelse
    </div>
  </div>

  <nav class="tabbar">
    <a class="tab" href="/"><svg viewBox="0 0 24 24" class="ico"><path d="M4 11 12 5l8 6"/><path d="M6 10v9h12v-9"/></svg>Главная</a>
    <a class="tab" href="/schedule"><svg viewBox="0 0 24 24" class="ico"><rect x="3" y="9" width="18" height="6" rx="1"/><path d="M5 15v3M19 15v3M12 9v6"/></svg>Аренда</a>
    <a class="tab" href="#"><svg viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg>События</a>
    <a class="tab" href="/partners"><svg viewBox="0 0 24 24" class="ico"><circle cx="9" cy="9" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 7a3 3 0 0 1 0 5M17 19a6 6 0 0 0-3-5"/></svg>Партнёр</a>
    <button class="tab active"><svg viewBox="0 0 24 24" class="ico"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>Тренеры</button>
  </nav>

  <div class="sheet-overlay" id="sheet-overlay"></div>
  <div class="sheet" id="sheet">
    <div class="sheet-grab"></div>
    <h3 id="sh-name">Тренер</h3>
    <div class="ssub" id="sh-rate">— ₽/час</div>
    <div class="sfield"><label>Дата</label><input type="date" id="sh-date" value="{{ $bookDate }}"></div>
    <div class="srow"><span class="k">Время начала</span><div class="step"><button data-st="s-1">−</button><span class="sv" id="sh-start">18:00</span><button data-st="s1">+</button></div></div>
    <div class="srow"><span class="k">Длительность</span><div class="step"><button data-st="d-1">−</button><span class="sv" id="sh-dur">1 час</span><button data-st="d1">+</button></div></div>
    <div class="asuser" id="sh-asuser">Вы вошли как <span style="font-weight:700"></span></div>
    <div class="sfield" id="sh-namef"><label>Ваше имя</label><input type="text" id="sh-cname" placeholder="Как к вам обращаться"></div>
    <div class="sfield" id="sh-phonef"><label>Телефон</label><input type="tel" id="sh-phone" placeholder="+7 ___ ___-__-__"></div>
    <div class="serr" id="sh-err"></div>
    <div class="stotal"><span class="k">Итого</span><span class="v" id="sh-total">— ₽</span></div>
    <button class="sbook" id="sh-book">Записаться</button>
  </div>

  <div class="confirm" id="confirm">
    <div class="cf-ico"><svg width="44" height="44" viewBox="0 0 24 24" class="ico" style="stroke-width:2"><path d="m5 12 5 5 9-11"/></svg></div>
    <h2>Вы записаны!</h2>
    <div class="cf-card"><div class="cft" id="cf-name">Тренер</div><div class="cfd" id="cf-info">—</div></div>
    <div class="cf-actions">
      <a class="sbook" id="cf-mine" href="/account" style="text-align:center;text-decoration:none;display:block;">Мои записи</a>
      <button class="btn-ghost" id="cf-more">Записаться ещё</button>
    </div>
  </div>
</div>

<script>
(function(){
  const COACHES=@json($coachData);
  const MAX_BOOK_DATE=@json($maxBookDate ?? null);
  const ADMIN_PHONE=@json($adminPhone ?? '');
  const CSRF=(document.querySelector('meta[name="csrf-token"]')||{}).content||'';
  const OPEN=9*60, CLOSE=22*60;
  const $=id=>document.getElementById(id);
  const pad=n=>(n<10?'0':'')+n, toStr=m=>pad(Math.floor(m/60))+':'+pad(m%60);
  const fmtDur=h=>h===Math.floor(h)?(h+' ч'):(String(h).replace('.',',')+' ч');
  let sel={coachId:null,name:'',rate:0,start:18*60,dur:60};

  function refresh(){
    if(sel.start+sel.dur>CLOSE) sel.dur=CLOSE-sel.start;
    if(sel.dur<60) sel.dur=60;
    $('sh-start').textContent=toStr(sel.start);
    $('sh-dur').textContent=fmtDur(sel.dur/60);
    $('sh-total').textContent=Math.round(sel.rate*sel.dur/60).toLocaleString('ru-RU')+' ₽';
  }
  function prefill(){
    const u=(window.ttAuth&&ttAuth.get())||null;
    const nf=$('sh-namef'),pf=$('sh-phonef'),as=$('sh-asuser');
    if(u&&u.phone){ $('sh-cname').value=u.name||''; $('sh-phone').value=u.phone;
      nf.style.display=u.name?'none':'block'; pf.style.display='none';
      as.style.display='flex'; as.querySelector('span').textContent=(u.name?u.name+' · ':'')+u.phone;
    } else { nf.style.display='block'; pf.style.display='block'; as.style.display='none'; }
  }
  function openSheet(coachId,name,rate){
    sel={coachId,name,rate,start:18*60,dur:60};
    $('sh-name').textContent=name; $('sh-rate').textContent=rate.toLocaleString('ru-RU')+' ₽/час';
    err(''); prefill(); refresh();
    $('sheet-overlay').classList.add('open'); $('sheet').classList.add('open');
  }
  function closeSheet(){ $('sheet-overlay').classList.remove('open'); $('sheet').classList.remove('open'); }
  function err(m){ const e=$('sh-err'); e.textContent=m||''; e.style.display=m?'block':'none'; }

  let pending=null;
  function gate(coachId,name,rate){
    if(!window.ttAuth||!ttAuth.isLoggedIn()){ pending={coachId,name,rate}; if(window.openAuth) openAuth('login'); return; }
    openSheet(coachId,name,rate);
  }
  window.ttOnLogin=function(){ if(pending){ const p=pending; pending=null; setTimeout(()=>openSheet(p.coachId,p.name,p.rate),40); return true; } return false; };

  document.querySelectorAll('.cbtn').forEach(b=>b.addEventListener('click',()=>gate(+b.dataset.coach,b.dataset.name,+b.dataset.rate)));
  document.querySelectorAll('[data-st]').forEach(b=>b.addEventListener('click',()=>{
    const k=b.dataset.st;
    if(k==='s-1') sel.start=Math.max(OPEN,sel.start-30);
    if(k==='s1')  sel.start=Math.min(CLOSE-60,sel.start+30);
    if(k==='d-1') sel.dur=Math.max(60,sel.dur-30);
    if(k==='d1')  sel.dur=Math.min(180,sel.dur+30);
    refresh();
  }));
  $('sheet-overlay').addEventListener('click',closeSheet);
  $('cf-more').addEventListener('click',()=>$('confirm').classList.remove('open'));

  $('sh-book').addEventListener('click',async ()=>{
    err('');
    const date=$('sh-date').value;
    const u=(window.ttAuth&&ttAuth.get())||null;
    const name=(u&&u.name)||$('sh-cname').value.trim();
    const phone=(u&&u.phone)||$('sh-phone').value.trim();
    if(name.length<2){ err('Укажите имя.'); return; }
    if(phone.length<5){ err('Укажите телефон.'); return; }
    if(MAX_BOOK_DATE && date>MAX_BOOK_DATE){ err('Онлайн-запись доступна на неделю вперёд. На более поздний срок — звоните: '+ADMIN_PHONE); return; }
    const btn=$('sh-book'); btn.disabled=true; const lbl=btn.textContent; btn.textContent='Записываем…';
    try{
      const res=await fetch('/coaches/book',{method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({coach_id:sel.coachId,name,phone,date,start:toStr(sel.start),end:toStr(sel.start+sel.dur)})});
      const data=await res.json().catch(()=>({}));
      if(!res.ok||!data.ok){ err(data.message||'Не удалось записаться.'); return; }
      closeSheet();
      $('cf-name').textContent=sel.name;
      $('cf-info').textContent=toStr(sel.start)+' – '+toStr(sel.start+sel.dur)+' · '+Math.round(data.amount).toLocaleString('ru-RU')+' ₽';
      if(data.account_url) $('cf-mine').href=data.account_url;
      $('confirm').classList.add('open');
    }catch(e){ err('Сеть недоступна, попробуйте ещё раз.'); }
    finally{ btn.disabled=false; btn.textContent=lbl; }
  });
})();
</script>
<script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
