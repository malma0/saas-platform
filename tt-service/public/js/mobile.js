/* ===== Мобильное меню (гамбургер) — Теннис Клуб НСК ===== */
/* Подключи последним: <script src="mobile.js"></script>
   Создаёт кнопку-гамбургер в шапке и выезжающую панель навигации.
   Работает на всех трёх страницах без правок разметки. */
(function () {
  function build() {
    var header = document.querySelector('header');
    if (!header || header.querySelector('.m-burger')) return;
    var nav = header.querySelector('nav');

    // ── кнопка-гамбургер ──
    var burger = document.createElement('button');
    burger.className = 'm-burger';
    burger.setAttribute('aria-label', 'Меню');
    burger.innerHTML = '<span></span><span></span><span></span>';
    header.appendChild(burger);

    // ── выезжающая панель ──
    var panel = document.createElement('div');
    panel.className = 'm-navpanel';

    var inner = document.createElement('div');
    inner.className = 'm-navpanel-inner';
    panel.appendChild(inner);

    // навигация (копия)
    if (nav) {
      var navClone = nav.cloneNode(true);
      navClone.removeAttribute('data-screen-label');
      inner.appendChild(navClone);
    }

    // контакты
    var contacts = document.createElement('div');
    contacts.className = 'm-contacts';
    contacts.innerHTML =
      '<a class="m-contact" href="tel:+73832078620">' +
        '<span class="m-ci"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 4h3l1.5 4-2 1.5a11 11 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2A16 16 0 0 1 4.5 6a2 2 0 0 1 2-2Z"/></svg></span>' +
        '<span><b>+7 (383) 207-86-20</b><i>Ежедневно 8:00 – 23:00</i></span></a>' +
      '<div class="m-contact">' +
        '<span class="m-ci"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.5 7-11a7 7 0 0 0-14 0c0 4.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/></svg></span>' +
        '<span><b>Красный проспект, 2/1</b><i>Новосибирск, 3 этаж</i></span></div>';
    inner.appendChild(contacts);

    // кнопка «Войти» (если на странице есть авторизация)
    if (document.querySelector('[data-auth-open]') || document.querySelector('.user-chip') || document.querySelector('.btn-cabinet')) {
      var login = document.createElement('button');
      login.className = 'm-login';
      login.setAttribute('data-auth-open', '');
      login.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg> Личный кабинет';
      inner.appendChild(login);
    }

    document.body.appendChild(panel);

    function toggle(open) {
      document.body.classList.toggle('m-nav-open', open);
    }
    burger.addEventListener('click', function () {
      toggle(!document.body.classList.contains('m-nav-open'));
    });
    panel.addEventListener('click', function (e) {
      if (e.target === panel || e.target.closest('a') || e.target.closest('.m-login')) toggle(false);
    });
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') toggle(false);
    });
  }

  if (document.readyState !== 'loading') build();
  else document.addEventListener('DOMContentLoaded', build);
})();
