<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Теннис Клуб НСК — Премиальный клуб настольного тенниса</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="{{ asset('js/device.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
@php
  $months = ['','января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
  $days   = ['вс','пн','вт','ср','чт','пт','сб'];
  $fmt = function(int $offset = 0) use ($months, $days): string {
    $ts = strtotime("+{$offset} days");
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ', ' . $days[(int)date('w', $ts)];
  };
@endphp
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
      <a href="/" class="active">Главная</a>
      <a href="#">Клуб</a>
      <a href="/schedule">Расписание</a>
      <a href="#">Турниры</a>
      <a href="/coaches">Тренеры</a>
      <a href="/partners">Партнёры</a>
      <a href="#">Контакты</a>
    </nav>
    <div class="head-info">
      <span class="ico-box"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg></span>
      <div class="lbl">
        <b class="l1">Новосибирск</b>
        <div class="l2">Красный проспект, 2/1</div>
        <div class="l3">3 этаж</div>
      </div>
    </div>
    <div class="head-info head-phone">
      <span class="ico-box"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><path d="M6.5 4h3l1.5 4-2 1.5a11 11 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2A16 16 0 0 1 4.5 6a2 2 0 0 1 2-2Z"/></svg></span>
      <div class="lbl">
        <b>+7 (383) 207-86-20</b>
        <div class="l2">Ежедневно 8:00 – 23:00</div>
      </div>
    </div>
    <button onclick="ttOpenAuth()" style="display:flex;align-items:center;gap:8px;background:rgba(198,226,26,.12);border:1px solid rgba(198,226,26,.35);border-radius:10px;padding:9px 16px;color:#c6e21a;font-size:13.5px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;flex-shrink:0;align-self:center;margin-left:auto;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
      Войти
    </button>
  </header>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-bg">
      <img src="{{ asset('images/hero-player.jpg') }}" alt="Игрок в настольный теннис">
    </div>
    <div class="veil"></div>
    <div class="hero-body">
      <h1>Играйте.<br>Развивайтесь.<br>Побеждайте.</h1>
      <p class="lead">Премиальные условия для игроков любого уровня в Новосибирске</p>
      <div class="hero-feats">
        <div class="feat">
          <span class="ring"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M12 3 5 6v5c0 4 3 7 7 9 4-2 7-5 7-9V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
          <span>Премиальные<br>залы</span>
        </div>
        <div class="feat">
          <span class="ring"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="2.5"/><path d="M12 4v3M12 17v3M4 12h3M17 12h3"/></svg></span>
          <span>Профессиональное<br>оборудование</span>
        </div>
        <div class="feat">
          <span class="ring"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><circle cx="9" cy="9" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 7a3 3 0 0 1 0 5M17 19a6 6 0 0 0-3-5"/></svg></span>
          <span>Сообщество<br>единомышленников</span>
        </div>
        <div class="feat">
          <span class="ring"><svg width="18" height="18" viewBox="0 0 24 24" class="ico"><path d="M4 16l5-5 3 3 6-7"/><path d="M16 7h4v4"/></svg></span>
          <span>Турниры<br>и рейтинги</span>
        </div>
      </div>
    </div>
    <div class="dots"><i class="on"></i><i></i><i></i></div>

    @php
      // Опции времени — по реальным часам работы филиала (из БД).
      $timeOpts = [];
      for ($h = ($openHour ?? 9); $h < ($closeHour ?? 22); $h++) {
        $timeOpts[] = sprintf('%02d:00', $h);
      }
      $timeOptsStr = implode('|', $timeOpts);
      $defaultTime = $timeOpts[min(8, count($timeOpts) - 1)] ?? '18:00';

      // Реальные услуги клуба (дедуп по названию, сохраняем public_id для перехода).
      $svcSeen = [];
      foreach (($services ?? []) as $s) {
        if (!isset($svcSeen[$s->name])) { $svcSeen[$s->name] = $s->public_id; }
      }
    @endphp
    <aside class="booking">
      <h3>Бронирование стола</h3>
      @if(!empty($svcSeen))
      <div class="field" data-dd data-dd-options="{{ implode('|', array_keys($svcSeen)) }}">
        <span class="fi"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><path d="M4 7h16M4 12h16M4 17h10"/></svg></span>
        <div class="ft"><div class="k">Услуга</div><div class="v" data-dd-val>{{ array_key_first($svcSeen) }}</div></div>
        <span class="chev"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span>
      </div>
      @endif
      <div class="field" data-dd data-dd-type="calendar">
        <span class="fi"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/></svg></span>
        <div class="ft"><div class="k">Дата</div><div class="v" data-dd-val>{{ $fmt(0) }}</div></div>
        <span class="chev"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span>
      </div>
      <div class="field" data-dd data-dd-options="{{ $timeOptsStr }}">
        <span class="fi"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>
        <div class="ft"><div class="k">Время</div><div class="v" data-dd-val>{{ $defaultTime }}</div></div>
        <span class="chev"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span>
      </div>
      <div class="field" data-dd data-dd-options="30 минут|1 час|1,5 часа|2 часа|3 часа">
        <span class="fi"><svg width="20" height="20" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>
        <div class="ft"><div class="k">Длительность</div><div class="v" data-dd-val>1 час</div></div>
        <span class="chev"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="m6 9 6 6 6-6"/></svg></span>
      </div>
      <button class="btn-green" id="find-table-btn">Найти свободный стол</button>
      <div class="all"><span class="d"></span> Часы работы: {{ sprintf('%02d:00', $openHour ?? 9) }}–{{ sprintf('%02d:00', $closeHour ?? 22) }}</div>
    </aside>
  </section>

  <!-- EVENTS + OFFERS -->
  <div class="grid-2">
    <div class="block">
      <div class="block-head">
        <h2>Предстоящие события</h2>
        <a href="#" class="link-arrow">Все события <svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
      </div>
      <table>
        <thead><tr><th>Когда</th><th>Время</th><th>Событие</th><th>Стоимость</th><th>Участники</th></tr></thead>
        <tbody>
          <tr><td>{{ $fmt(0) }}</td><td>11:00</td><td><span class="ev-name">Кубок клуба. Весна 2026<span class="badge b-turnir">Турнир</span></span></td><td><span class="price">800 ₽</span></td><td><span class="parts">24 / 32</span></td></tr>
          <tr><td>{{ $fmt(1) }}</td><td>18:00</td><td><span class="ev-name">Лига выходного дня<span class="badge b-liga">Лига</span></span></td><td><span class="price">600 ₽</span></td><td><span class="parts">10 / 16</span></td></tr>
          <tr><td>{{ $fmt(3) }}</td><td>19:00</td><td><span class="ev-name">Тренировка с тренером<span class="badge b-tren">Тренировка</span></span></td><td><span class="price">1 500 ₽</span></td><td><span class="parts">6 / 8</span></td></tr>
          <tr><td>{{ $fmt(5) }}</td><td>20:00</td><td><span class="ev-name">Открытый спарринг<span class="badge b-spar">Спарринг</span></span></td><td><span class="price">500 ₽</span></td><td><span class="parts">8 / 12</span></td></tr>
          <tr><td>{{ $fmt(7) }}</td><td>12:00</td><td><span class="ev-name">Любительский турнир<span class="badge b-turnir">Турнир</span></span></td><td><span class="price">600 ₽</span></td><td><span class="parts">16 / 24</span></td></tr>
        </tbody>
      </table>
    </div>

    <div class="block">
      <div class="block-head"><h2>Специальные предложения</h2></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="offer senior">
          <img class="offer-img" src="{{ asset('images/offer-senior.jpg') }}" alt="Игрок старшего поколения с ракеткой">
          <div class="veil"></div>
          <div class="offer-body">
            <h4>Игры для<br>старшего<br>поколения</h4>
            <div class="ot">Активность, общение и здоровье!</div>
            <div class="tag"><div class="tag-disc">Скидка 20%<small>пн–пт до 12:00</small></div></div>
          </div>
        </div>
        <div class="offer kids">
          <img class="offer-img" src="{{ asset('images/offer-kids.jpg') }}" alt="Ребёнок с ракеткой">
          <div class="veil"></div>
          <div class="offer-body">
            <h4>Детская<br>секция</h4>
            <div class="ot">Учимся, играем, побеждаем!</div>
            <div class="tag"><div class="tag-free">Первое занятие<br>бесплатно</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- COACHES + PARTNERS -->
  <div class="two-col">
    <div class="block">
      <div class="block-head">
        <h2>Наши тренеры</h2>
        <a href="/coaches" class="link-arrow">Просмотреть всех <svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
      </div>
      <div class="coaches">
        @forelse($coaches as $coach)
        <div class="coach">
          <div class="coach-top">
            <div class="avatar">{{ $coach->initials() }}</div>
            <div class="ci">
              <div class="cname">{{ $coach->name }}</div>
              @if($coach->rank)<span class="ttw">{{ $coach->rank }}</span>@endif
            </div>
          </div>
          <ul>
            @if($coach->rank)
            <li><span class="ck"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg></span>{{ $coach->rank }}</li>
            @endif
            @if($coach->experience_years)
            <li><span class="ck"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg></span>Опыт {{ $coach->experience_years }} лет</li>
            @endif
            @if($coach->specialization)
            <li><span class="ck"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="m5 12 5 5 9-11"/></svg></span>{{ $coach->specialization }}</li>
            @endif
          </ul>
          <div class="coach-foot">
            <div class="cp"><b>{{ number_format($coach->hourly_rate_rub, 0, '.', "\u{00A0}") }} ₽</b> <span>/ 60 мин</span></div>
            <button class="btn-sm" onclick="location.href='/coaches'">Заказать</button>
          </div>
        </div>
        @empty
        <div class="coach" style="opacity:.5;align-items:center;justify-content:center;min-height:160px;">
          <div style="color:var(--muted);font-size:14px;">Тренеры появятся скоро</div>
        </div>
        @endforelse
      </div>
    </div>

    <div class="block">
      <div class="block-head">
        <h2>Найти партнёра для игры</h2>
        <div style="display:flex;align-items:center;gap:30px;">
          <a href="#" class="link-arrow"><svg width="16" height="16" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg> Разместить объявление</a>
          <a href="#" class="link-arrow">Просмотреть всех <svg width="16" height="16" viewBox="0 0 24 24" class="ico"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </div>
      </div>
      <div class="partners">
        <div class="partner">
          <div class="ph">
            <div class="avatar">СК</div>
            <div class="pname">Сергей К.</div>
            <span class="ttw-sm" style="color:var(--blue);border-color:rgba(74,144,226,.5);background:rgba(74,144,226,.1);">TTW 1750</span>
          </div>
          <ul>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M12 21s6-5.5 6-10a6 6 0 0 0-12 0c0 4.5 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg></span>м. Гагаринская</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>Вечером</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M4 16l5-5 3 3 6-7"/><path d="M16 7h4v4"/></svg></span>Спарринг, турниры</li>
          </ul>
          <div class="chat"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></div>
        </div>
        <div class="partner">
          <div class="ph">
            <div class="avatar">АП</div>
            <div class="pname">Алексей П.</div>
            <span class="ttw-sm" style="color:var(--blue);border-color:rgba(74,144,226,.5);background:rgba(74,144,226,.1);">TTW 1680</span>
          </div>
          <ul>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M12 21s6-5.5 6-10a6 6 0 0 0-12 0c0 4.5 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg></span>м. Заельцовская</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>Пн, Ср, Пт</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M4 16l5-5 3 3 6-7"/><path d="M16 7h4v4"/></svg></span>Игра на рейтинг</li>
          </ul>
          <div class="chat"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></div>
        </div>
        <div class="partner">
          <div class="ph">
            <div class="avatar">ЕЛ</div>
            <div class="pname">Екатерина Л.</div>
            <span class="ttw-sm" style="color:var(--purple);border-color:rgba(154,124,226,.5);background:rgba(154,124,226,.1);">TTW 1620</span>
          </div>
          <ul>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M12 21s6-5.5 6-10a6 6 0 0 0-12 0c0 4.5 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg></span>м. Студенческая</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>Днём</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M4 16l5-5 3 3 6-7"/><path d="M16 7h4v4"/></svg></span>Техника, спарринг</li>
          </ul>
          <div class="chat"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></div>
        </div>
        <div class="partner">
          <div class="ph">
            <div class="avatar">ИМ</div>
            <div class="pname">Иван М.</div>
            <span class="ttw-sm" style="color:var(--orange);border-color:rgba(226,149,74,.5);background:rgba(226,149,74,.1);">TTW 1600</span>
          </div>
          <ul>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M12 21s6-5.5 6-10a6 6 0 0 0-12 0c0 4.5 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg></span>м. Площадь Ленина</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>Выходные</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M4 16l5-5 3 3 6-7"/><path d="M16 7h4v4"/></svg></span>Любительская игра</li>
          </ul>
          <div class="chat"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></div>
        </div>
        <div class="partner">
          <div class="ph">
            <div class="avatar">ДТ</div>
            <div class="pname">Дмитрий Т.</div>
            <span class="ttw-sm" style="color:var(--cyan);border-color:rgba(74,208,226,.5);background:rgba(74,208,226,.1);">TTW 1580</span>
          </div>
          <ul>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M12 21s6-5.5 6-10a6 6 0 0 0-12 0c0 4.5 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg></span>м. Октябрьская</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg></span>Вечером</li>
            <li><span class="pi"><svg width="14" height="14" viewBox="0 0 24 24" class="ico"><path d="M4 16l5-5 3 3 6-7"/><path d="M16 7h4v4"/></svg></span>Спарринг</li>
          </ul>
          <div class="chat"><svg width="15" height="15" viewBox="0 0 24 24" class="ico"><path d="M4 5h16v11H9l-4 3v-3H4Z"/></svg></div>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <footer class="foot">
    <div class="fitem"><span class="fi"><svg width="26" height="26" viewBox="0 0 24 24" class="ico"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><div><b>Ежедневно 8:00 – 23:00</b><span>Без выходных</span></div></div>
    <div class="fitem"><span class="fi"><svg width="26" height="26" viewBox="0 0 24 24" class="ico"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg></span><div><b>Новосибирск, Красный пр-т, 2/1, 3 этаж</b><span>Удобное расположение</span></div></div>
    <div class="fitem"><span class="fi"><svg width="26" height="26" viewBox="0 0 24 24" class="ico"><circle cx="9" cy="9" r="5"/><path d="M12.5 12.5 19 19"/></svg></span><div><b>Профессиональные столы</b><span>Butterfly, Stiga, Donic</span></div></div>
    <div class="fitem"><span class="fi"><svg width="26" height="26" viewBox="0 0 24 24" class="ico"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/><path d="m9 14 2 2 4-4"/></svg></span><div><b>Онлайн-бронирование</b><span>Быстро и удобно</span></div></div>
    <div class="fitem"><span class="fi"><svg width="26" height="26" viewBox="0 0 24 24" class="ico"><circle cx="9" cy="8" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><path d="M16 6a3 3 0 0 1 0 5M17 19a6 6 0 0 0-3-5"/></svg></span><div><b>Турниры и рейтинги</b><span>Для всех уровней</span></div></div>
    <div class="fitem"><span class="fi"><svg width="26" height="26" viewBox="0 0 24 24" class="ico"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 17V8h3.5a2.5 2.5 0 0 1 0 5H9"/></svg></span><div><b>Парковка</b><span>Для клиентов клуба</span></div></div>
  </footer>

</div>
{{-- Модал авторизации / регистрации --}}
<div id="auth-modal" style="display:none;position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);align-items:center;justify-content:center;">
  <div style="background:#101210;border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:30px 26px;width:92%;max-width:380px;position:relative;">
    <button onclick="ttCloseAuth()" style="position:absolute;top:14px;right:16px;background:none;border:none;color:#8c8f86;font-size:24px;cursor:pointer;line-height:1;">×</button>

    {{-- Шаг 1: ввод телефона --}}
    <div id="am-step1">
      <div style="font-size:19px;font-weight:800;margin-bottom:8px;">Личный кабинет</div>
      <div style="font-size:13.5px;color:#8c8f86;margin-bottom:22px;line-height:1.5;">Введите номер телефона, чтобы войти или зарегистрироваться.</div>
      <input type="tel" id="am-phone" placeholder="+7 900 000-00-00"
        style="width:100%;background:#0c0d0b;border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:14px 15px;color:#f0f1ec;font-size:15px;font-family:inherit;outline:none;margin-bottom:12px;">
      <div id="am-err1" style="display:none;color:#f0a090;font-size:13px;margin-bottom:10px;"></div>
      <button onclick="amFindPhone()" id="am-btn1" style="width:100%;background:#c6e21a;color:#13160a;font-size:15px;font-weight:800;border:none;border-radius:11px;padding:14px;cursor:pointer;font-family:inherit;">Продолжить</button>
    </div>

    {{-- Шаг 2: регистрация --}}
    <div id="am-step2" style="display:none;">
      <div style="font-size:19px;font-weight:800;margin-bottom:8px;">Регистрация</div>
      <div style="font-size:13.5px;color:#8c8f86;margin-bottom:18px;line-height:1.5;">Номер <strong id="am-phone-display" style="color:#f0f1ec;"></strong> не найден. Заполните данные — создадим аккаунт.</div>
      <div id="am-err2" style="display:none;color:#f0a090;font-size:13px;margin-bottom:10px;"></div>
      <input type="text" id="am-fname" placeholder="Имя *"
        style="width:100%;background:#0c0d0b;border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:14px 15px;color:#f0f1ec;font-size:15px;font-family:inherit;outline:none;margin-bottom:10px;">
      <input type="text" id="am-lname" placeholder="Фамилия"
        style="width:100%;background:#0c0d0b;border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:14px 15px;color:#f0f1ec;font-size:15px;font-family:inherit;outline:none;margin-bottom:12px;">
      <button onclick="amRegister()" id="am-btn2" style="width:100%;background:#c6e21a;color:#13160a;font-size:15px;font-weight:800;border:none;border-radius:11px;padding:14px;cursor:pointer;font-family:inherit;">Зарегистрироваться</button>
      <button onclick="amBackToPhone()" style="width:100%;background:transparent;border:none;color:#8c8f86;font-size:13px;margin-top:10px;cursor:pointer;font-family:inherit;">← Другой номер</button>
    </div>

    {{-- Шаг 3: КтоКуда звонок --}}
    <div id="am-step3" style="display:none;">
      <div style="font-size:19px;font-weight:800;margin-bottom:8px;">Подключить КтоКуда</div>
      <div style="font-size:13.5px;color:#8c8f86;margin-bottom:18px;line-height:1.5;">Аккаунт создан! КтоКуда позвонит на <strong id="am-kk-phone" style="color:#f0f1ec;"></strong>.<br>Смотрите на <strong style="color:#f0f1ec;">номер звонящего</strong> — его последние <strong style="color:#f0f1ec;">4 цифры</strong> это ваш пароль. Звонок можно сбросить.</div>
      <div id="am-err3" style="display:none;color:#f0a090;font-size:13px;margin-bottom:10px;"></div>
      <input type="text" id="am-kk-pw" placeholder="4 цифры из звонка"
        style="width:100%;background:#0c0d0b;border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:14px 15px;color:#f0f1ec;font-size:15px;font-family:inherit;outline:none;margin-bottom:12px;">
      <button onclick="amKkVerify()" style="width:100%;background:#c6e21a;color:#13160a;font-size:15px;font-weight:800;border:none;border-radius:11px;padding:14px;cursor:pointer;font-family:inherit;">Подключить и войти</button>
      <button onclick="amSkipKk()" style="width:100%;background:transparent;border:none;color:#8c8f86;font-size:13px;margin-top:10px;cursor:pointer;font-family:inherit;">Пропустить, войти без КтоКуда</button>
    </div>
  </div>
</div>

<script src="{{ asset('js/dropdowns.js') }}"></script>
<script>
  // «Найти свободный стол» → переход на расписание с выбранными параметрами.
  (function () {
    var btn = document.getElementById('find-table-btn');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var fields = document.querySelectorAll('.booking .field');
      var vals = {};
      fields.forEach(function (f) {
        var k = f.querySelector('.k') ? f.querySelector('.k').textContent.trim() : '';
        var v = f.querySelector('[data-dd-val]') ? f.querySelector('[data-dd-val]').textContent.trim() : '';
        vals[k] = v;
      });
      var qs = new URLSearchParams();
      if (vals['Дата']) qs.set('date', vals['Дата']);
      if (vals['Время']) qs.set('time', vals['Время']);
      if (vals['Длительность']) qs.set('duration', vals['Длительность']);
      if (vals['Услуга']) qs.set('service', vals['Услуга']);
      window.location.href = '/schedule?' + qs.toString();
    });
  })();
</script>

<script>
  // ---- Auth modal ----
  const AM_CSRF = document.querySelector('meta[name=csrf-token]')?.content ?? '';
  let amPhone = '';

  function ttOpenAuth() {
    document.getElementById('auth-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('am-phone').focus(), 100);
  }
  function ttCloseAuth() {
    document.getElementById('auth-modal').style.display = 'none';
    amReset();
  }
  function amReset() {
    ['am-step2','am-step3'].forEach(id => document.getElementById(id).style.display='none');
    document.getElementById('am-step1').style.display = 'block';
    ['am-err1','am-err2','am-err3'].forEach(id => document.getElementById(id).style.display='none');
    document.getElementById('am-phone').value = '';
    document.getElementById('am-fname').value = '';
    document.getElementById('am-lname').value = '';
    document.getElementById('am-kk-pw').value = '';
  }
  function amBackToPhone() {
    document.getElementById('am-step2').style.display = 'none';
    document.getElementById('am-step1').style.display = 'block';
    document.getElementById('am-err2').style.display = 'none';
  }

  async function amFindPhone() {
    const phone = document.getElementById('am-phone').value.trim();
    const err   = document.getElementById('am-err1');
    const btn   = document.getElementById('am-btn1');
    err.style.display = 'none';
    if (!phone) { err.textContent = 'Введите номер телефона'; err.style.display='block'; return; }
    amPhone = phone;
    btn.disabled = true; btn.textContent = 'Проверяем...';
    try {
      const res = await fetch('/auth/login', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':AM_CSRF},
        body: JSON.stringify({ phone }),
        signal: AbortSignal.timeout(15000)
      });
      const data = await res.json();
      btn.disabled = false; btn.textContent = 'Продолжить';
      if (data.returning) {
        try { localStorage.setItem('tt_user', JSON.stringify({ name: data.name, phone })); } catch(e){}
        location.href = data.account_url;
      } else {
        document.getElementById('am-phone-display').textContent = phone;
        document.getElementById('am-step1').style.display = 'none';
        document.getElementById('am-step2').style.display = 'block';
        setTimeout(() => document.getElementById('am-fname').focus(), 100);
      }
    } catch(e) {
      btn.disabled = false; btn.textContent = 'Продолжить';
      err.textContent = 'Ошибка соединения. Попробуйте ещё раз.';
      err.style.display = 'block';
    }
  }

  async function amRegister() {
    const fname = document.getElementById('am-fname').value.trim();
    const lname = document.getElementById('am-lname').value.trim();
    const btn   = document.getElementById('am-btn2');
    const err   = document.getElementById('am-err2');
    err.style.display = 'none';
    if (!fname) { err.textContent = 'Введите имя'; err.style.display='block'; return; }
    btn.disabled = true; btn.textContent = 'Создаём аккаунт...';
    let data;
    try {
      const res = await fetch('/auth/register', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':AM_CSRF},
        body: JSON.stringify({ phone: amPhone, first_name: fname, last_name: lname }),
        signal: AbortSignal.timeout(30000)
      });
      data = await res.json();
    } catch(e) {
      btn.disabled = false; btn.textContent = 'Зарегистрироваться';
      err.textContent = 'Ошибка соединения. Попробуйте ещё раз.';
      err.style.display = 'block';
      return;
    }
    btn.disabled = false; btn.textContent = 'Зарегистрироваться';
    if (!data.ok) {
      err.textContent = data.message || 'Ошибка регистрации';
      err.style.display = 'block';
      return;
    }
    try { localStorage.setItem('tt_user', JSON.stringify({ name: fname + (lname ? ' '+lname : ''), phone: amPhone })); } catch(e){}
    if (data.kk_sent) {
      document.getElementById('am-kk-phone').textContent = amPhone;
      document.getElementById('am-step2').style.display = 'none';
      document.getElementById('am-step3').style.display = 'block';
      setTimeout(() => document.getElementById('am-kk-pw').focus(), 100);
    } else {
      location.href = data.account_url;
    }
  }

  async function amKkVerify() {
    const pw  = document.getElementById('am-kk-pw').value.trim();
    const err = document.getElementById('am-err3');
    err.style.display = 'none';
    if (!pw) { err.textContent = 'Введите пароль из звонка'; err.style.display='block'; return; }
    try {
      const res = await fetch('/ktokyda/link', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':AM_CSRF},
        body: JSON.stringify({ phone: amPhone, password: pw }),
        signal: AbortSignal.timeout(15000)
      });
      const data = await res.json();
      if (data.ok) {
        location.href = '/account?phone=' + encodeURIComponent(amPhone);
      } else {
        err.textContent = data.error || 'Неверный пароль';
        err.style.display = 'block';
      }
    } catch(e) {
      err.textContent = 'Ошибка соединения. Попробуйте ещё раз.';
      err.style.display = 'block';
    }
  }

  function amSkipKk() { location.href = '/account?phone=' + encodeURIComponent(amPhone); }

  // Enter в поле телефона
  document.getElementById('am-phone').addEventListener('keydown', e => { if(e.key==='Enter') amFindPhone(); });
  // Закрыть по клику на фон
  document.getElementById('auth-modal').addEventListener('click', e => { if(e.target===e.currentTarget) ttCloseAuth(); });
</script>
</body>
</html>
