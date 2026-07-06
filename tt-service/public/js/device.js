/* ===== Авто-определение версии (устройство + разрешение) — Теннис Клуб НСК =====
   Замеряет реальную ширину окна, кладёт в куку tt_vw (её читает сервер),
   и если показанная версия не соответствует ширине/устройству — перезагружает
   страницу в правильной версии. Телефон всегда остаётся мобильным.        */
(function () {
  var BP = 860; // синхронно с App\Support\Device::BREAKPOINT

  function isPhone() {
    return /Mobile|Android|iPhone|iPod|Windows Phone|BlackBerry|webOS|Opera Mini|IEMobile/i
      .test(navigator.userAgent || '');
  }

  function run() {
    var w = window.innerWidth || document.documentElement.clientWidth || 0;
    if (!w) return;

    // Сообщаем серверу ширину окна
    document.cookie = 'tt_vw=' + w + ';path=/;max-age=31536000;samesite=lax';

    // Ручной режим (?m=1/?m=0) — не вмешиваемся
    if (/[?&]m=/.test(location.search)) return;

    var phone = isPhone();
    var servedMobile = !!document.querySelector('.tabbar'); // маркер мобильной версии
    var wantMobile = phone || w < BP;

    if (servedMobile === wantMobile) return;       // всё совпадает
    if (!wantMobile && phone) return;              // телефон не переводим на десктоп

    // Анти-цикл: не перезагружаем повторно в ту же сторону
    var dir = wantMobile ? 'm' : 'd';
    if (sessionStorage.getItem('tt_switch') === dir) return;
    sessionStorage.setItem('tt_switch', dir);
    // Снимаем принудительный режим (tt_view), чтобы сервер опирался на tt_vw
    document.cookie = 'tt_view=;path=/;max-age=0;samesite=lax';
    location.reload();
  }

  if (document.readyState !== 'loading') run();
  else document.addEventListener('DOMContentLoaded', run);

  // Поменяли размер окна (десктоп ↔ мобилка) — пересчитать
  var t;
  window.addEventListener('resize', function () {
    clearTimeout(t);
    t = setTimeout(run, 400);
  });
})();
