/* ===== Окно входа / регистрации — Теннис Клуб НСК ===== */
/* Подключи:  <script src="auth.js"></script>
   Открывается кликом по любому элементу с [data-auth-open], .btn-cabinet или .user-chip.
   Программно: window.openAuth('login'|'register')                                       */
(function(){
  'use strict';

  const css = `
  .au-overlay{position:fixed;inset:0;z-index:10000;background:rgba(6,7,5,.74);backdrop-filter:blur(5px);
    display:none;align-items:center;justify-content:center;padding:20px;font-family:'Manrope',system-ui,sans-serif;}
  .au-overlay.open{display:flex;animation:auOv .15s ease;}
  @keyframes auOv{from{opacity:0}to{opacity:1}}
  .au-modal{background:#13150f;border:1px solid rgba(255,255,255,.09);border-radius:20px;width:440px;max-width:100%;
    padding:30px 32px 30px;box-shadow:0 28px 80px rgba(0,0,0,.62);animation:auIn .2s cubic-bezier(.2,.7,.3,1);
    max-height:94vh;overflow-y:auto;}
  @keyframes auIn{from{opacity:0;transform:translateY(14px) scale(.98)}to{opacity:1;transform:none}}
  .au-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;}
  .au-logo{display:flex;align-items:center;gap:11px;}
  .au-logo .au-mark{width:38px;height:38px;flex:none;}
  .au-logo .au-brand{font-size:16px;font-weight:800;letter-spacing:.4px;color:#f0f1ec;}
  .au-logo .au-brand span{color:#c6e21a;}
  .au-x{width:34px;height:34px;border-radius:9px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.04);
    color:#8c8f86;cursor:pointer;display:grid;place-items:center;flex:none;transition:all .12s;}
  .au-x:hover{background:rgba(255,255,255,.1);color:#fff;}
  .au-title{font-size:25px;font-weight:800;margin:18px 0 6px;color:#f0f1ec;}
  .au-sub{font-size:13.5px;color:#8c8f86;margin-bottom:22px;line-height:1.45;}
  .au-tabs{display:flex;gap:4px;background:#0c0d0b;border:1px solid rgba(255,255,255,.08);border-radius:11px;padding:5px;margin-bottom:22px;}
  .au-tab{flex:1;text-align:center;padding:11px;border-radius:8px;font-size:14.5px;font-weight:700;color:#8c8f86;cursor:pointer;transition:all .14s;border:none;background:none;font-family:inherit;}
  .au-tab.active{background:#c6e21a;color:#13160a;}
  .au-field{margin-bottom:15px;}
  .au-field label{display:block;font-size:12.5px;font-weight:600;color:#8c8f86;margin-bottom:7px;}
  .au-input{display:flex;align-items:center;gap:11px;background:#0c0d0b;border:1px solid rgba(255,255,255,.09);border-radius:11px;padding:13px 15px;transition:border-color .14s;}
  .au-input:focus-within{border-color:rgba(198,226,26,.5);}
  .au-input svg{color:#6f726a;flex:none;}
  .au-input input{flex:1;background:none;border:none;outline:none;color:#f0f1ec;font-size:15px;font-family:inherit;}
  .au-input input::placeholder{color:#5e615a;}
  .au-input .au-eye{cursor:pointer;color:#6f726a;}
  .au-input .au-eye:hover{color:#b0b2ab;}
  .au-row{display:flex;align-items:center;justify-content:space-between;margin:4px 0 20px;}
  .au-check{display:flex;align-items:center;gap:9px;font-size:13px;color:#b0b2ab;cursor:pointer;user-select:none;}
  .au-check input{display:none;}
  .au-box{width:18px;height:18px;border-radius:5px;border:1.5px solid rgba(255,255,255,.2);display:grid;place-items:center;color:#13160a;transition:all .12s;flex:none;}
  .au-check input:checked + .au-box{background:#c6e21a;border-color:#c6e21a;}
  .au-box svg{opacity:0;transition:opacity .1s;}
  .au-check input:checked + .au-box svg{opacity:1;}
  .au-link{font-size:13px;color:#c6e21a;cursor:pointer;font-weight:600;}
  .au-link:hover{filter:brightness(1.15);}
  .au-submit{width:100%;background:#c6e21a;color:#13160a;font-weight:800;font-size:16px;border:none;border-radius:12px;padding:15px;cursor:pointer;transition:filter .12s;font-family:inherit;}
  .au-submit:hover{filter:brightness(1.07);}
  .au-or{display:flex;align-items:center;gap:14px;margin:20px 0;color:#6f726a;font-size:12.5px;}
  .au-or::before,.au-or::after{content:"";flex:1;height:1px;background:rgba(255,255,255,.08);}
  .au-socials{display:flex;gap:10px;}
  .au-soc{flex:1;display:flex;align-items:center;justify-content:center;gap:9px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:11px;padding:12px;cursor:pointer;font-size:13.5px;font-weight:600;color:#dcdfd6;transition:background .12s;font-family:inherit;}
  .au-soc:hover{background:rgba(255,255,255,.09);}
  .au-foot{text-align:center;font-size:13px;color:#8c8f86;margin-top:20px;}
  .au-foot span{color:#c6e21a;cursor:pointer;font-weight:600;}
  .au-pane{display:none;}
  .au-pane.active{display:block;}
  .au-done{text-align:center;padding:10px 0 4px;}
  .au-done .au-dico{width:66px;height:66px;border-radius:50%;background:rgba(198,226,26,.14);border:1.5px solid #c6e21a;display:grid;place-items:center;color:#c6e21a;margin:0 auto 18px;}
  .au-done h4{font-size:21px;font-weight:800;color:#f0f1ec;margin-bottom:8px;}
  .au-done p{font-size:14px;color:#8c8f86;line-height:1.5;margin-bottom:22px;}
  `;
  const st=document.createElement('style'); st.textContent=css; document.head.appendChild(st);

  const eye = `<svg class="au-eye" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`;
  const ic = (p)=>`<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">${p}</svg>`;
  const I_MAIL='<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>';
  const I_LOCK='<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>';
  const I_USER='<circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>';
  const I_PHONE='<path d="M6.5 4h3l1.5 4-2 1.5a11 11 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2A16 16 0 0 1 4.5 6a2 2 0 0 1 2-2Z"/>';
  const CHK='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-11"/></svg>';

  const overlay=document.createElement('div');
  overlay.className='au-overlay';
  overlay.innerHTML=`
  <div class="au-modal" role="dialog" aria-modal="true">
    <div class="au-top">
      <div class="au-logo">
        <svg class="au-mark" viewBox="0 0 48 48">
          <g transform="rotate(-32 24 24)"><ellipse cx="18" cy="17" rx="13" ry="14" fill="#1d1f1a" stroke="rgba(255,255,255,.18)"/><rect x="15.5" y="29" width="5" height="13" rx="2.5" fill="#1d1f1a"/></g>
          <g transform="rotate(28 30 22)"><ellipse cx="30" cy="17" rx="13" ry="14" fill="#c6e21a"/><rect x="27.5" y="29" width="5" height="13" rx="2.5" fill="#c6e21a"/></g>
          <circle cx="14" cy="11" r="3.4" fill="#f0f1ec"/>
        </svg>
        <div class="au-brand">ТЕННИС КЛУБ <span>НСК</span></div>
      </div>
      <button class="au-x" id="au-close">${ic('<path d="M6 6l12 12M18 6 6 18"/>')}</button>
    </div>

    <!-- LOGIN -->
    <div class="au-pane active" id="au-login">
      <div class="au-title">С возвращением</div>
      <div class="au-sub">Войдите, чтобы бронировать столы и находить партнёров</div>
      <div class="au-tabs"><button class="au-tab active" data-go="login">Вход</button><button class="au-tab" data-go="register">Регистрация</button></div>
      <div class="au-field"><label>E-mail или телефон</label><div class="au-input">${ic(I_MAIL)}<input type="text" placeholder="example@mail.ru"></div></div>
      <div class="au-field"><label>Пароль</label><div class="au-input">${ic(I_LOCK)}<input type="password" placeholder="••••••••" id="au-p1">${eye}</div></div>
      <div class="au-row">
        <label class="au-check"><input type="checkbox" checked><span class="au-box">${CHK}</span>Запомнить меня</label>
        <span class="au-link">Забыли пароль?</span>
      </div>
      <button class="au-submit" data-submit>Войти</button>
      <div class="au-or">или войдите через</div>
      <div class="au-socials">
        <button class="au-soc"><svg width="17" height="17" viewBox="0 0 24 24"><path fill="#5181b8" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>VK</button>
        <button class="au-soc"><svg width="17" height="17" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#fff"/><path fill="#ea4335" d="M12 7a5 5 0 0 1 4.5 2.8L19 8A8 8 0 0 0 4 12h3a5 5 0 0 1 5-5z"/></svg>Google</button>
      </div>
      <div class="au-foot">Нет аккаунта? <span data-go="register">Создать</span></div>
    </div>

    <!-- REGISTER -->
    <div class="au-pane" id="au-register">
      <div class="au-title">Создать аккаунт</div>
      <div class="au-sub">Регистрация займёт меньше минуты</div>
      <div class="au-tabs"><button class="au-tab" data-go="login">Вход</button><button class="au-tab active" data-go="register">Регистрация</button></div>
      <div class="au-field"><label>Имя</label><div class="au-input">${ic(I_USER)}<input type="text" placeholder="Алексей"></div></div>
      <div class="au-field"><label>E-mail</label><div class="au-input">${ic(I_MAIL)}<input type="email" placeholder="example@mail.ru"></div></div>
      <div class="au-field"><label>Телефон</label><div class="au-input">${ic(I_PHONE)}<input type="tel" placeholder="+7 (___) ___-__-__"></div></div>
      <div class="au-field"><label>Пароль</label><div class="au-input">${ic(I_LOCK)}<input type="password" placeholder="Придумайте пароль" id="au-p2">${eye}</div></div>
      <label class="au-check" style="margin:6px 0 18px">${'<input type="checkbox" checked>'}<span class="au-box">${CHK}</span><span style="line-height:1.4">Соглашаюсь с <span class="au-link">условиями</span> и политикой конфиденциальности</span></label>
      <button class="au-submit" data-submit>Зарегистрироваться</button>
      <div class="au-foot">Уже есть аккаунт? <span data-go="login">Войти</span></div>
    </div>

    <!-- SUCCESS -->
    <div class="au-pane" id="au-success">
      <div class="au-done">
        <div class="au-dico"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-11"/></svg></div>
        <h4 id="au-done-title">Вы вошли!</h4>
        <p id="au-done-text">Добро пожаловать в Теннис Клуб НСК.</p>
        <button class="au-submit" id="au-done-ok">Продолжить</button>
      </div>
    </div>
  </div>`;
  document.body.appendChild(overlay);

  function show(pane){
    overlay.querySelectorAll('.au-pane').forEach(p=>p.classList.remove('active'));
    overlay.querySelector('#au-'+pane).classList.add('active');
  }
  function open(mode){ overlay.classList.add('open'); show(mode==='register'?'register':'login'); }
  function close(){ overlay.classList.remove('open'); }
  window.openAuth = open;

  overlay.addEventListener('click', e=>{
    if(e.target===overlay) return close();
    const go=e.target.closest('[data-go]'); if(go){ show(go.dataset.go); return; }
    if(e.target.closest('#au-close')) return close();
    const eyeBtn=e.target.closest('.au-eye');
    if(eyeBtn){ const inp=eyeBtn.parentElement.querySelector('input'); inp.type=inp.type==='password'?'text':'password'; return; }
    const sub=e.target.closest('[data-submit]');
    if(sub){
      const isReg=overlay.querySelector('#au-register').classList.contains('active');
      document.getElementById('au-done-title').textContent = isReg?'Аккаунт создан!':'Вы вошли!';
      document.getElementById('au-done-text').textContent  = isReg?'Добро пожаловать в Теннис Клуб НСК. Теперь можно бронировать столы.':'Рады видеть вас снова.';
      show('success');
      return;
    }
    if(e.target.closest('#au-done-ok')) return close();
  });
  document.addEventListener('keydown', e=>{ if(e.key==='Escape') close(); });

  // wire triggers
  document.addEventListener('click', e=>{
    if(e.target.closest('[data-auth-open], .btn-cabinet, .user-chip')){ e.preventDefault(); open('login'); }
  });

})();
