<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Личный кабинет — Теннис Клуб НСК</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="{{ asset('js/device.js') }}"></script>
<style>
:root{
  --bg:#0a0b09;--panel:#101210;--line:rgba(255,255,255,.08);
  --txt:#f0f1ec;--muted:#8c8f86;--green:#c6e21a;--radius:14px;
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{background:var(--bg);color:var(--txt);font-family:'Manrope',system-ui,sans-serif;-webkit-font-smoothing:antialiased;}
body{padding:0 22px 40px;}
.wrap{max-width:1100px;margin:0 auto;}
a{color:inherit;text-decoration:none;}
.green{color:var(--green);}
header{display:flex;align-items:center;gap:18px;padding:18px 4px 22px;border-bottom:1px solid rgba(255,255,255,.06);}
.logo{display:flex;align-items:center;gap:12px;}
.logo-mark{width:40px;height:40px;border-radius:9px;background:rgba(198,226,26,.14);border:1px solid rgba(198,226,26,.35);display:grid;place-items:center;}
.brand{font-size:19px;font-weight:800;letter-spacing:.4px;}
nav{display:flex;gap:20px;margin-left:14px;font-size:14px;color:#cfd1ca;}
nav a.active{color:var(--green);font-weight:600;}
.spacer{flex:1;}
.user-chip{display:flex;align-items:center;gap:9px;background:rgba(255,255,255,.06);border:1px solid var(--line);border-radius:10px;padding:6px 13px 6px 7px;}
.ava{width:28px;height:28px;border-radius:50%;background:var(--green);color:#13160a;font-size:12px;font-weight:800;display:grid;place-items:center;}
h1{font-size:30px;font-weight:800;letter-spacing:-.4px;margin:26px 0 4px;}
.sub{font-size:14px;color:#9ea09a;margin-bottom:24px;}
.layout{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:20px;align-items:start;}
.tabs{display:flex;gap:5px;background:var(--panel);border:1px solid var(--line);border-radius:11px;padding:5px;margin-bottom:16px;}
.tab{flex:1;text-align:center;font-size:13.5px;font-weight:600;color:var(--muted);padding:9px 0;border-radius:8px;}
.tab.active{color:var(--txt);background:rgba(255,255,255,.07);}
.card{display:flex;align-items:center;gap:16px;background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:15px 18px;margin-bottom:11px;}
.daybox{text-align:center;flex:none;width:54px;}
.daybox .d{font-size:25px;font-weight:800;line-height:1;color:var(--green);}
.daybox .m{font-size:12px;color:var(--muted);margin-top:3px;}
.vsep{width:1px;height:46px;background:var(--line);flex:none;}
.cmain{flex:1;min-width:0;}
.cmain .t{font-size:15.5px;font-weight:700;}
.cmain .meta{font-size:13px;color:#9ea09a;margin-top:5px;display:flex;gap:14px;flex-wrap:wrap;}
.cright{text-align:right;flex:none;}
.pill{font-size:11.5px;font-weight:700;border-radius:6px;padding:4px 9px;display:inline-block;border:1px solid;}
.pill-confirmed{color:#aee8be;background:rgba(32,90,46,.45);border-color:rgba(55,132,72,.4);}
.pill-pending{color:#f0c894;background:rgba(112,70,14,.4);border-color:rgba(164,110,32,.4);}
.pill-completed{color:#b6bca6;background:rgba(52,55,50,.5);border-color:rgba(76,80,74,.5);}
.price{font-size:16px;font-weight:800;color:var(--green);margin-top:8px;}
.empty{background:var(--panel);border:1px dashed rgba(255,255,255,.14);border-radius:var(--radius);padding:34px;text-align:center;color:var(--muted);font-size:14px;}
.side-card{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:18px;text-align:center;margin-bottom:11px;}
.side-ava{width:56px;height:56px;border-radius:50%;background:var(--green);color:#13160a;font-size:20px;font-weight:800;display:grid;place-items:center;margin:0 auto 11px;}
.side-name{font-size:16px;font-weight:700;}
.side-phone{font-size:13px;color:var(--muted);margin-top:4px;}
.stats{display:flex;gap:9px;margin-bottom:11px;}
.stat{flex:1;background:var(--panel);border:1px solid var(--line);border-radius:11px;padding:13px;text-align:center;}
.stat .v{font-size:22px;font-weight:800;}
.stat .k{font-size:11.5px;color:var(--muted);margin-top:3px;}
.btn-book{display:block;background:var(--green);color:#13160a;font-size:14.5px;font-weight:800;text-align:center;border-radius:11px;padding:13px;}
.lookup{max-width:420px;margin:40px auto;background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:30px 28px;text-align:center;}
.lookup h2{font-size:21px;font-weight:800;margin-bottom:8px;}
.lookup p{font-size:13.5px;color:var(--muted);margin-bottom:22px;line-height:1.5;}
.lookup input{width:100%;background:#0c0d0b;border:1px solid var(--line);border-radius:11px;padding:14px 15px;color:var(--txt);font-size:15px;font-family:inherit;outline:none;margin-bottom:12px;}
.lookup button{width:100%;background:var(--green);color:#13160a;font-size:15px;font-weight:800;border:none;border-radius:11px;padding:14px;cursor:pointer;}
.notfound{color:#f0a090;font-size:13px;margin-bottom:14px;}
.sync{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;color:var(--muted);border:1px solid rgba(198,226,26,.22);border-radius:8px;padding:6px 11px;}
</style>
</head>
<body>
<div class="wrap">

  <header>
    <div class="logo">
      <span class="logo-mark"><svg width="22" height="22" viewBox="0 0 48 48"><g transform="rotate(28 30 22)"><ellipse cx="30" cy="17" rx="13" ry="14" fill="#c6e21a"/><rect x="27.5" y="29" width="5" height="13" rx="2.5" fill="#c6e21a"/></g></svg></span>
      <span class="brand">ТЕННИС КЛУБ <span class="green">НСК</span></span>
    </div>
    <nav>
      <a href="/">Главная</a>
      <a href="/schedule">Расписание</a>
      <a href="/coaches">Тренеры</a>
      <a href="/account" class="active">Кабинет</a>
    </nav>
    <div class="spacer"></div>
    @if($client)
      <div class="user-chip">
        <span class="ava">{{ mb_strtoupper(mb_substr($client->first_name,0,1).mb_substr($client->last_name ?? '',0,1)) }}</span>
        <span style="font-size:13px;font-weight:600;">{{ $client->first_name }}</span>
      </div>
      <button onclick="ttLogout()" title="Выйти" style="display:flex;align-items:center;gap:7px;background:rgba(255,255,255,.04);border:1px solid var(--line);border-radius:10px;padding:8px 13px;color:#b0b2ab;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Выйти
      </button>
    @endif
  </header>

  @if(!$client)
    {{-- Вход по телефону --}}
    <div class="lookup" id="lookup-step1">
      <h2>Личный кабинет</h2>
      <p>Введите номер телефона, чтобы войти.</p>
      <input type="tel" id="lk-phone" placeholder="+7 900 000-00-00" autofocus
        style="width:100%;background:#0c0d0b;border:1px solid var(--line);border-radius:11px;padding:14px 15px;color:var(--txt);font-size:15px;font-family:inherit;outline:none;margin-bottom:12px;">
      <div id="lk-error" style="display:none;color:#f0a090;font-size:13px;margin-bottom:10px;"></div>
      <button onclick="lkFindPhone()" id="lk-btn" style="width:100%;background:var(--green);color:#13160a;font-size:15px;font-weight:800;border:none;border-radius:11px;padding:14px;cursor:pointer;font-family:inherit;">
        Войти
      </button>
    </div>

    {{-- Телефон не найден --}}
    <div class="lookup" id="lookup-notfound" style="display:none;">
      <h2>Аккаунт не найден</h2>
      <p>Номер <strong id="lk-phone-display"></strong> не зарегистрирован.<br>Зарегистрируйтесь на главной странице.</p>
      <a href="/" style="display:block;background:var(--green);color:#13160a;font-size:15px;font-weight:800;text-align:center;border-radius:11px;padding:14px;margin-bottom:10px;">
        Перейти на главную
      </a>
      <button onclick="lkBackToPhone()" style="width:100%;background:transparent;border:none;color:var(--muted);font-size:13px;cursor:pointer;font-family:inherit;">
        ← Другой номер
      </button>
    </div>
  @else

    <div style="margin:26px 0 24px;">
      <h1>Личный кабинет</h1>
      <div style="display:flex;align-items:center;gap:12px;margin-top:5px;flex-wrap:wrap;">
        <div class="sub" style="margin:0;">Ваши брони, история игр и профиль</div>
        <div class="sync"><span class="green">●</span> Синхронизировано с клубом</div>
      </div>
    </div>

    <div class="layout">
      <div>
        <div class="tabs">
          <div class="tab active">Предстоящие ({{ count($upcoming) }})</div>
          <div class="tab">История ({{ count($history) }})</div>
        </div>

        @forelse($upcoming as $b)
          <div class="card">
            <div class="daybox"><div class="d">{{ $b['day'] }}</div><div class="m">{{ $b['month'] }}</div></div>
            <div class="vsep"></div>
            <div class="cmain">
              <div class="t">{{ $b['table'] }}</div>
              <div class="meta"><span>{{ $b['time'] }}</span><span>{{ $b['duration'] }}</span></div>
            </div>
            <div class="cright">
              <span class="pill pill-{{ $b['status'] }}">{{ $b['status_label'] }}</span>
              <div class="price">{{ $b['amount'] }} ₽</div>
            </div>
          </div>
        @empty
          <div class="empty">Предстоящих броней нет. <a href="/schedule" class="green">Забронировать стол →</a></div>
        @endforelse

        @if(count($history))
          <div style="font-size:13px;color:var(--muted);font-weight:600;margin:22px 0 11px;">История</div>
          @foreach($history as $b)
            <div class="card" style="opacity:.72;">
              <div class="daybox"><div class="d" style="color:var(--muted);">{{ $b['day'] }}</div><div class="m">{{ $b['month'] }}</div></div>
              <div class="vsep"></div>
              <div class="cmain">
                <div class="t">{{ $b['table'] }}</div>
                <div class="meta"><span>{{ $b['time'] }}</span><span>{{ $b['duration'] }}</span></div>
              </div>
              <div class="cright"><div class="price" style="color:var(--muted);">{{ $b['amount'] }} ₽</div></div>
            </div>
          @endforeach
        @endif
      </div>

      <div>
        <div class="side-card">
          <div class="side-ava">{{ mb_strtoupper(mb_substr($client->first_name,0,1).mb_substr($client->last_name ?? '',0,1)) }}</div>
          <div class="side-name">{{ trim($client->first_name.' '.($client->last_name ?? '')) }}</div>
          <div class="side-phone">{{ $client->phone }}</div>
        </div>
        <div class="stats">
          <div class="stat"><div class="v">{{ $stats['count'] }}</div><div class="k">броней</div></div>
          <div class="stat"><div class="v">{{ $stats['hours'] }}</div><div class="k">часов</div></div>
        </div>
        <a href="/schedule" class="btn-book">Забронировать стол</a>

        {{-- КтоКуда --}}
        <div class="side-card" id="kk-card" style="margin-top:11px;text-align:left;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:34px;height:34px;border-radius:8px;background:rgba(198,226,26,.12);border:1px solid rgba(198,226,26,.3);display:grid;place-items:center;flex:none;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c6e21a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            </div>
            <div>
              <div style="font-size:13.5px;font-weight:700;">КтоКуда</div>
              <div style="font-size:11.5px;color:var(--muted);">Групповые события</div>
            </div>
          </div>

          @if($client->ktokyda_user_id)
            <div style="font-size:12.5px;color:#aee8be;margin-bottom:12px;">✓ Аккаунт подключён</div>
            <button onclick="kkUnlink()" style="width:100%;background:transparent;border:1px solid rgba(255,255,255,.12);border-radius:9px;padding:9px;color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">Отвязать</button>
          @else
            <div style="font-size:12.5px;color:var(--muted);margin-bottom:12px;line-height:1.5;">
              Подключите аккаунт КтоКуда, чтобы записываться на групповые игры прямо с нашего сайта.
            </div>
            <button onclick="kkShowLink()" style="width:100%;background:rgba(198,226,26,.1);border:1px solid rgba(198,226,26,.3);border-radius:9px;padding:10px;color:var(--green);font-size:13.5px;font-weight:700;cursor:pointer;font-family:inherit;">
              Подключить КтоКуда
            </button>
          @endif
        </div>
      </div>
    </div>

  @endif

</div>

{{-- Модал привязки КтоКуда --}}
<div id="kk-modal" style="display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.7);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;">
  <div style="background:#101210;border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:28px 26px;width:90%;max-width:380px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <div style="font-size:17px;font-weight:800;">Подключить КтоКуда</div>
      <button onclick="kkCloseModal()" style="background:none;border:none;color:var(--muted);font-size:22px;cursor:pointer;line-height:1;">×</button>
    </div>

    {{-- Шаг 1: запрос звонка --}}
    <div id="kk-step1">
      <div style="font-size:13px;color:var(--muted);margin-bottom:16px;line-height:1.6;">
        КтоКуда позвонит на ваш номер. Смотрите на <strong style="color:var(--txt);">номер звонящего</strong> — его последние <strong style="color:var(--txt);">4 цифры</strong> и есть ваш пароль. Сам звонок можно сбросить.
      </div>
      <div id="kk-err1" style="display:none;font-size:12.5px;color:#f0a090;margin-bottom:10px;"></div>
      <button onclick="kkSendSms()" id="kk-btn-sms" style="width:100%;background:var(--green);color:#13160a;font-size:14.5px;font-weight:800;border:none;border-radius:11px;padding:13px;cursor:pointer;font-family:inherit;">
        Позвонить и сообщить пароль
      </button>
      <div style="font-size:11.5px;color:var(--muted);margin-top:10px;text-align:center;">
        Телефон: <span style="color:var(--txt);" id="kk-phone-display"></span>
      </div>
    </div>

    {{-- Шаг 2: ввод пароля --}}
    <div id="kk-step2" style="display:none;">
      <div id="kk-step2-msg" style="font-size:13px;color:var(--muted);margin-bottom:14px;line-height:1.6;">
        Введите последние 4 цифры входящего номера из звонка КтоКуда.
      </div>
      <div id="kk-err2" style="display:none;font-size:12.5px;color:#f0a090;margin-bottom:10px;"></div>
      <input id="kk-password" type="text" placeholder="4 цифры из звонка"
        style="width:100%;background:#0c0d0b;border:1px solid rgba(255,255,255,.15);border-radius:11px;padding:13px 14px;color:var(--txt);font-size:15px;font-family:inherit;outline:none;margin-bottom:11px;">
      <button onclick="kkVerify()" style="width:100%;background:var(--green);color:#13160a;font-size:14.5px;font-weight:800;border:none;border-radius:11px;padding:13px;cursor:pointer;font-family:inherit;">
        Подключить
      </button>
      <button onclick="kkReDial()" style="width:100%;background:transparent;border:none;color:var(--muted);font-size:12.5px;margin-top:10px;cursor:pointer;font-family:inherit;">
        ← Позвонить ещё раз
      </button>
    </div>
  </div>
</div>

<script>
  const CSRF = document.querySelector('meta[name=csrf-token]')?.content ?? '';
  const CLIENT_PHONE = @json($client?->phone ?? '');
  const CLIENT_NAME  = @json($client ? trim($client->first_name.' '.($client->last_name ?? '')) : '');

  // Синхронизируем «вошёл» с остальным сайтом (шапка на /schedule и т.д.).
  @if($client)
    try {
      localStorage.setItem('tt_user', JSON.stringify({
        name:  @json(trim($client->first_name.' '.($client->last_name ?? ''))),
        phone: @json($client->phone)
      }));
    } catch(e){}
  @endif
  function ttLogout(){ try { localStorage.removeItem('tt_user'); } catch(e){} location.href = '/schedule'; }

  // ---- Вход ----
  async function lkFindPhone() {
    const phone = document.getElementById('lk-phone').value.trim();
    const err   = document.getElementById('lk-error');
    const btn   = document.getElementById('lk-btn');
    err.style.display = 'none';
    if (!phone) { err.textContent = 'Введите номер телефона'; err.style.display='block'; return; }

    btn.disabled = true; btn.textContent = 'Проверяем...';
    try {
      const res = await fetch('/auth/login', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body: JSON.stringify({ phone }),
        signal: AbortSignal.timeout(15000)
      });
      const data = await res.json();
      btn.disabled = false; btn.textContent = 'Войти';
      if (data.returning) {
        location.href = data.account_url;
      } else {
        document.getElementById('lk-phone-display').textContent = phone;
        document.getElementById('lookup-step1').style.display = 'none';
        document.getElementById('lookup-notfound').style.display = 'block';
      }
    } catch(e) {
      btn.disabled = false; btn.textContent = 'Войти';
      err.textContent = 'Ошибка соединения. Попробуйте ещё раз.';
      err.style.display = 'block';
    }
  }

  function lkBackToPhone() {
    document.getElementById('lookup-notfound').style.display = 'none';
    document.getElementById('lookup-step1').style.display = 'block';
  }

  // Enter на поле телефона
  document.getElementById('lk-phone')?.addEventListener('keydown', e => { if(e.key==='Enter') lkFindPhone(); });

  // ---- КтоКуда ----
  function kkShowLink(){
    document.getElementById('kk-phone-display').textContent = CLIENT_PHONE;
    document.getElementById('kk-modal').style.display = 'flex';
    document.getElementById('kk-step1').style.display = 'block';
    document.getElementById('kk-step2').style.display = 'none';
  }
  function kkCloseModal(){ document.getElementById('kk-modal').style.display = 'none'; }
  function kkBackToStep1(){
    document.getElementById('kk-step2').style.display = 'none';
    document.getElementById('kk-err1').style.display = 'none';
    document.getElementById('kk-step1').style.display = 'block';
  }

  async function kkReDial(){
    document.getElementById('kk-step2').style.display = 'none';
    document.getElementById('kk-err1').style.display = 'none';
    document.getElementById('kk-step1').style.display = 'block';
    await kkSendSms();
  }

  async function kkSendSms(){
    const btn = document.getElementById('kk-btn-sms');
    const err = document.getElementById('kk-err1');
    btn.disabled = true; btn.textContent = 'Звоним...';
    err.style.display = 'none';

    let data;
    try {
      const nameParts = CLIENT_NAME.split(' ');
      const res = await fetch('/ktokyda/register', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body: JSON.stringify({
          phone: CLIENT_PHONE,
          firstName: nameParts[0] || 'Пользователь',
          lastName:  nameParts[1] || '',
        }),
        signal: AbortSignal.timeout(20000)
      });
      data = await res.json();
    } catch(e) {
      btn.disabled = false; btn.textContent = 'Позвонить и сообщить пароль';
      err.textContent = 'Ошибка соединения. Попробуйте ещё раз.';
      err.style.display = 'block';
      return;
    }
    btn.disabled = false; btn.textContent = 'Позвонить и сообщить пароль';

    if(data.ok){
      if(data.already_exists){
        document.getElementById('kk-step2-msg').innerHTML =
          'Ваш номер уже зарегистрирован в КтоКуда. Введите пароль — последние <strong>4 цифры</strong> из звонка, который вы получали при регистрации.';
      } else {
        document.getElementById('kk-step2-msg').innerHTML =
          'Введите последние <strong>4 цифры</strong> входящего номера из звонка КтоКуда.';
      }
      document.getElementById('kk-step1').style.display = 'none';
      document.getElementById('kk-step2').style.display = 'block';
      setTimeout(() => document.getElementById('kk-password').focus(), 100);
    } else {
      err.textContent = data.error || 'Ошибка. Попробуйте ещё раз.';
      err.style.display = 'block';
    }
  }

  async function kkVerify(){
    const pw  = document.getElementById('kk-password').value.trim();
    const err = document.getElementById('kk-err2');
    err.style.display = 'none';

    if(!pw){ err.textContent = 'Введите пароль'; err.style.display='block'; return; }

    const res = await fetch('/ktokyda/link', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
      body: JSON.stringify({ phone: CLIENT_PHONE, password: pw })
    });
    const data = await res.json();

    if(data.ok){
      kkCloseModal();
      location.reload();
    } else {
      err.textContent = data.error || 'Неверный пароль';
      err.style.display = 'block';
    }
  }

  async function kkUnlink(){
    if(!confirm('Отвязать аккаунт КтоКуда?')) return;
    await fetch('/ktokyda/unlink', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
      body: JSON.stringify({ phone: CLIENT_PHONE })
    });
    location.reload();
  }
</script>
</body>
</html>
