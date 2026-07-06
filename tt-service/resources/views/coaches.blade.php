@php
  $coachData = $coaches->map(fn ($c) => [
    'id' => $c->id, 'name' => $c->name, 'rate' => $c->hourlyRubles(),
  ])->values();
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Тренеры — Теннис Клуб НСК</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="{{ asset('js/device.js') }}"></script>
<style>
  :root{--bg:#0a0b09;--panel:#101210;--panel-2:#151714;--line:rgba(255,255,255,.08);
    --txt:#f0f1ec;--muted:#8c8f86;--muted-2:#6f726a;--green:#c6e21a;--radius:14px;}
  *{box-sizing:border-box;margin:0;padding:0;}
  html,body{background:var(--bg);color:var(--txt);font-family:'Manrope',system-ui,sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden;}
  body{padding:0 22px 50px;}
  .wrap{max-width:1100px;margin:0 auto;}
  a{color:inherit;text-decoration:none;}
  .green{color:var(--green);}
  .ico{stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round;}
  header{display:flex;align-items:center;gap:18px;padding:18px 4px 22px;border-bottom:1px solid rgba(255,255,255,.06);}
  .brand{font-size:19px;font-weight:800;letter-spacing:.4px;}
  nav{display:flex;gap:20px;margin-left:14px;font-size:14px;color:#cfd1ca;}
  nav a.active{color:var(--green);font-weight:600;}
  .spacer{flex:1;}
  .btn-cabinet{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.06);border:1px solid var(--line);border-radius:10px;padding:9px 15px;font-size:14px;font-weight:600;color:#e7e8e3;cursor:pointer;}
  h1{font-size:32px;font-weight:800;letter-spacing:-.4px;margin:30px 0 6px;}
  .sub{font-size:14.5px;color:#9ea09a;margin-bottom:28px;}
  .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;}
  @media(max-width:900px){.grid{grid-template-columns:1fr;}}
  .card{background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:22px;display:flex;flex-direction:column;}
  .ctop{display:flex;align-items:center;gap:15px;}
  .ava{width:64px;height:64px;border-radius:16px;flex:none;display:grid;place-items:center;font-size:22px;font-weight:800;color:#13160a;background:linear-gradient(135deg,#c6e21a,#9fb50f);}
  .cname{font-size:18px;font-weight:800;}
  .crank{display:inline-block;margin-top:5px;font-size:11.5px;font-weight:700;color:var(--green);background:rgba(198,226,26,.13);border:1px solid rgba(198,226,26,.4);border-radius:7px;padding:2px 9px;}
  .cspec{font-size:14px;color:#cfd1ca;margin-top:16px;line-height:1.45;}
  .cmeta{display:flex;align-items:center;gap:7px;font-size:13px;color:var(--muted);margin-top:10px;}
  .cmeta svg{color:var(--green);flex:none;}
  .cfoot{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:18px;}
  .cprice{font-size:20px;font-weight:800;}
  .cprice span{font-size:13px;color:var(--muted);font-weight:600;}
  .btn-book{background:var(--green);color:#13160a;font-weight:800;font-size:14px;border:none;border-radius:10px;padding:12px 20px;cursor:pointer;font-family:inherit;}
  .btn-book:hover{filter:brightness(1.06);}
  .empty{background:var(--panel);border:1px dashed rgba(255,255,255,.14);border-radius:var(--radius);padding:40px;text-align:center;color:var(--muted);}

  /* modal */
  .mbg{position:fixed;inset:0;z-index:10050;background:rgba(6,7,5,.74);backdrop-filter:blur(5px);display:none;align-items:center;justify-content:center;padding:20px;}
  .mbg.open{display:flex;}
  .modal{background:#13150f;border:1px solid var(--line);border-radius:20px;width:430px;max-width:100%;padding:26px;}
  .modal h3{font-size:21px;font-weight:800;margin-bottom:4px;}
  .modal .msub{font-size:13.5px;color:var(--muted);margin-bottom:18px;}
  .mrow{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-top:1px solid var(--line);}
  .mrow .k{font-size:13px;color:var(--muted);}
  .mrow .v{font-size:15px;font-weight:700;}
  .step{display:flex;align-items:center;gap:10px;}
  .step button{width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:#fff;font-size:17px;cursor:pointer;}
  .step .sv{min-width:92px;text-align:center;font-weight:700;}
  .mfield{margin-top:12px;}
  .mfield label{display:block;font-size:12.5px;color:var(--muted);margin-bottom:6px;}
  .mfield input{width:100%;background:#0c0d0b;border:1px solid var(--line);border-radius:10px;padding:12px 14px;color:var(--txt);font-size:15px;font-family:inherit;outline:none;}
  .merr{display:none;color:#f0a090;font-size:13px;font-weight:600;margin-top:10px;}
  .mtotal{display:flex;align-items:center;justify-content:space-between;margin:16px 0;padding-top:14px;border-top:1px solid var(--line);}
  .mtotal .v{font-size:22px;font-weight:800;color:var(--green);}
  .mok{width:100%;background:var(--green);color:#13160a;font-weight:800;font-size:16px;border:none;border-radius:12px;padding:15px;cursor:pointer;font-family:inherit;}
  .mdone{text-align:center;padding:8px 0;}
  .mdone .dico{width:64px;height:64px;border-radius:50%;background:rgba(198,226,26,.14);border:1.5px solid var(--green);display:grid;place-items:center;color:var(--green);margin:0 auto 16px;}
  .mdone h4{font-size:20px;font-weight:800;margin-bottom:8px;}
  .mdone p{font-size:14px;color:var(--muted);line-height:1.5;margin-bottom:20px;}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <span class="brand">ТЕННИС КЛУБ <span class="green">НСК</span></span>
    <nav>
      <a href="/">Главная</a>
      <a href="/schedule">Расписание</a>
      <a href="/coaches" class="active">Тренеры</a>
      <a href="/partners">Партнёры</a>
    </nav>
    <div class="spacer"></div>
    <button class="btn-cabinet" data-auth-open>
      <svg width="16" height="16" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
      <span data-au-label>Войти</span>
    </button>
  </header>

  <h1>Персональные тренировки</h1>
  <div class="sub">Выберите тренера и запишитесь на индивидуальное занятие онлайн</div>

  @forelse($coaches as $c)
    @if($loop->first)<div class="grid">@endif
      <div class="card">
        <div class="ctop">
          <div class="ava">{{ $c->initials() }}</div>
          <div>
            <div class="cname">{{ $c->name }}</div>
            @if($c->rank)<span class="crank">{{ $c->rank }}</span>@endif
          </div>
        </div>
        @if($c->specialization)<div class="cspec">{{ $c->specialization }}</div>@endif
        @if($c->experience_years)
          <div class="cmeta">
            <svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg>
            Опыт {{ $c->experience_years }}+ лет
          </div>
        @endif
        <div class="cfoot">
          <div class="cprice">{{ number_format($c->hourlyRubles(), 0, '', ' ') }} ₽ <span>/ 60 мин</span></div>
          <button class="btn-book" data-coach="{{ $c->id }}" data-name="{{ $c->name }}" data-rate="{{ $c->hourlyRubles() }}">Записаться</button>
        </div>
      </div>
    @if($loop->last)</div>@endif
  @empty
    <div class="empty">Тренеры пока не добавлены.</div>
  @endforelse
</div>

{{-- Модалка записи --}}
<div class="mbg" id="mbg">
  <div class="modal">
    <div id="m-form">
      <h3 id="m-title">Запись к тренеру</h3>
      <div class="msub" id="m-coach">Тренер</div>
      <div class="mfield">
        <label>Дата</label>
        <input type="date" id="m-date" value="{{ $bookDate }}">
      </div>
      <div class="mrow"><span class="k">Время начала</span>
        <div class="step"><button data-st="s-1">−</button><span class="sv" id="m-start">18:00</span><button data-st="s1">+</button></div>
      </div>
      <div class="mrow"><span class="k">Длительность</span>
        <div class="step"><button data-st="d-1">−</button><span class="sv" id="m-dur">1 час</span><button data-st="d1">+</button></div>
      </div>
      <div id="m-asuser" style="display:none;align-items:center;gap:8px;background:rgba(198,226,26,.08);border:1px solid rgba(198,226,26,.25);border-radius:10px;padding:9px 12px;margin-top:12px;font-size:13px;color:#cfe08a;">Вы вошли как <span style="font-weight:700"></span></div>
      <div class="mfield" id="m-namef"><label>Ваше имя</label><input type="text" id="m-name" placeholder="Как к вам обращаться"></div>
      <div class="mfield" id="m-phonef"><label>Телефон</label><input type="tel" id="m-phone" placeholder="+7 ___ ___-__-__"></div>
      <div class="merr" id="m-err"></div>
      <div class="mtotal"><span class="k">Итого</span><span class="v" id="m-price">— ₽</span></div>
      <button class="mok" id="m-ok">Записаться</button>
    </div>
    <div id="m-done" style="display:none">
      <div class="mdone">
        <div class="dico"><svg width="32" height="32" viewBox="0 0 24 24" class="ico" style="stroke-width:2"><path d="m5 12 5 5 9-11"/></svg></div>
        <h4>Вы записаны!</h4>
        <p id="m-done-text"></p>
        <button class="mok" id="m-done-ok">Отлично</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const COACHES = @json($coachData);
  const MAX_BOOK_DATE = @json($maxBookDate ?? null);
  const ADMIN_PHONE = @json($adminPhone ?? '');
  const CSRF = (document.querySelector('meta[name="csrf-token"]')||{}).content||'';
  const OPEN=9*60, CLOSE=22*60;
  const $=id=>document.getElementById(id);
  const pad=n=>(n<10?'0':'')+n, toStr=m=>pad(Math.floor(m/60))+':'+pad(m%60);
  const fmtDur=h=>h===Math.floor(h)?(h+' ч'):(String(h).replace('.',',')+' ч');

  let sel={coachId:null, name:'', rate:0, start:18*60, dur:60};

  function refresh(){
    if(sel.start+sel.dur>CLOSE) sel.dur=CLOSE-sel.start;
    if(sel.dur<60) sel.dur=60;
    $('m-start').textContent=toStr(sel.start);
    $('m-dur').textContent=fmtDur(sel.dur/60);
    $('m-price').textContent=Math.round(sel.rate*sel.dur/60).toLocaleString('ru-RU')+' ₽';
  }
  function prefill(){
    const u=(window.ttAuth&&ttAuth.get())||null;
    const nf=$('m-namef'), pf=$('m-phonef'), as=$('m-asuser');
    if(u&&u.phone){ $('m-name').value=u.name||''; $('m-phone').value=u.phone;
      nf.style.display=u.name?'none':'block'; pf.style.display='none';
      as.style.display='flex'; as.querySelector('span').textContent=(u.name?u.name+' · ':'')+u.phone;
    } else { nf.style.display='block'; pf.style.display='block'; as.style.display='none'; }
  }
  function openModal(coachId,name,rate){
    sel={coachId,name,rate,start:18*60,dur:60};
    $('m-coach').textContent=name+' · '+rate.toLocaleString('ru-RU')+' ₽/час';
    $('m-form').style.display='block'; $('m-done').style.display='none'; $('m-err').style.display='none';
    prefill(); refresh(); $('mbg').classList.add('open');
  }
  function closeModal(){ $('mbg').classList.remove('open'); }
  function err(m){ const e=$('m-err'); e.textContent=m||''; e.style.display=m?'block':'none'; }

  document.querySelectorAll('.btn-book').forEach(b=>b.addEventListener('click',()=>{
    openModalGate(+b.dataset.coach, b.dataset.name, +b.dataset.rate);
  }));

  let pending=null;
  function openModalGate(coachId,name,rate){
    if(!window.ttAuth||!ttAuth.isLoggedIn()){ pending={coachId,name,rate}; if(window.openAuth) openAuth('login'); return; }
    openModal(coachId,name,rate);
  }
  window.ttOnLogin=function(){ if(pending){ const p=pending; pending=null; setTimeout(()=>openModal(p.coachId,p.name,p.rate),40); return true; } return false; };

  document.querySelectorAll('[data-st]').forEach(b=>b.addEventListener('click',()=>{
    const k=b.dataset.st;
    if(k==='s-1') sel.start=Math.max(OPEN,sel.start-30);
    if(k==='s1')  sel.start=Math.min(CLOSE-60,sel.start+30);
    if(k==='d-1') sel.dur=Math.max(60,sel.dur-30);
    if(k==='d1')  sel.dur=Math.min(180,sel.dur+30);
    refresh();
  }));
  $('mbg').addEventListener('click',e=>{ if(e.target===$('mbg')) closeModal(); });
  $('m-done-ok').addEventListener('click',closeModal);

  $('m-ok').addEventListener('click',async ()=>{
    err('');
    const date=$('m-date').value;
    const u=(window.ttAuth&&ttAuth.get())||null;
    const name=(u&&u.name)||$('m-name').value.trim();
    const phone=(u&&u.phone)||$('m-phone').value.trim();
    if(name.length<2){ err('Укажите имя.'); return; }
    if(phone.length<5){ err('Укажите телефон.'); return; }
    if(MAX_BOOK_DATE && date>MAX_BOOK_DATE){ err('Онлайн-запись доступна на неделю вперёд. На более поздний срок — звоните: '+ADMIN_PHONE); return; }
    const btn=$('m-ok'); btn.disabled=true; const lbl=btn.textContent; btn.textContent='Записываем…';
    try{
      const res=await fetch('/coaches/book',{method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({coach_id:sel.coachId,name,phone,date,start:toStr(sel.start),end:toStr(sel.start+sel.dur)})});
      const data=await res.json().catch(()=>({}));
      if(!res.ok||!data.ok){ err(data.message||'Не удалось записаться.'); return; }
      $('m-form').style.display='none'; $('m-done').style.display='block';
      $('m-done-text').innerHTML=sel.name+'<br>'+toStr(sel.start)+' – '+toStr(sel.start+sel.dur)+' · '+Math.round(data.amount).toLocaleString('ru-RU')+' ₽'
        + (data.account_url?'<br><a href="'+data.account_url+'" style="color:var(--green);text-decoration:underline">Мои записи</a>':'');
    }catch(e){ err('Сеть недоступна, попробуйте ещё раз.'); }
    finally{ btn.disabled=false; btn.textContent=lbl; }
  });
})();
</script>
<script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
