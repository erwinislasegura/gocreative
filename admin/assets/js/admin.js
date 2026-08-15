(() => {
  'use strict';

  const body = document.body;
  const sidebar = document.querySelector('[data-admin-sidebar]');
  const toggles = [...document.querySelectorAll('[data-admin-menu-toggle]')];
  const closeButton = document.querySelector('[data-admin-menu-close]');
  const backdrop = document.querySelector('[data-admin-menu-backdrop]');
  const desktop = window.matchMedia('(min-width: 992px)');

  if (sidebar && toggles.length && backdrop) {
    const setOpen = (open, restoreFocus = false) => {
      const shouldOpen = open && !desktop.matches;
      sidebar.classList.toggle('is-open', shouldOpen);
      toggles.forEach((toggle) => {
        toggle.setAttribute('aria-expanded', String(shouldOpen));
        toggle.setAttribute('aria-label', shouldOpen ? 'Cerrar menú completo' : 'Abrir menú completo');
      });
      body.classList.toggle('admin-menu-open', shouldOpen);
      backdrop.hidden = !shouldOpen;

      if (desktop.matches) sidebar.removeAttribute('aria-hidden');
      else sidebar.setAttribute('aria-hidden', String(!shouldOpen));

      if (shouldOpen) {
        window.requestAnimationFrame(() => closeButton?.focus({ preventScroll: true }));
      } else if (restoreFocus && !desktop.matches) {
        toggles[0]?.focus({ preventScroll: true });
      }
    };

    toggles.forEach((toggle) => {
      toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    });
    closeButton?.addEventListener('click', () => setOpen(false, true));
    backdrop.addEventListener('click', () => setOpen(false, true));
    sidebar.querySelectorAll('.admin-nav a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && toggles.some((toggle) => toggle.getAttribute('aria-expanded') === 'true')) {
        setOpen(false, true);
      }
    });

    const syncBreakpoint = () => setOpen(false);
    if (typeof desktop.addEventListener === 'function') desktop.addEventListener('change', syncBreakpoint);
    else desktop.addListener(syncBreakpoint);
    syncBreakpoint();
  }

  document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.dataset.confirm || '¿Confirmas esta acción?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.passwordToggle);
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      button.textContent = input.type === 'password' ? 'Ver' : 'Ocultar';
    });
  });

  document.querySelectorAll('[data-copy-target]').forEach((button) => {
    button.addEventListener('click', async () => {
      const input = document.getElementById(button.dataset.copyTarget);
      if (!input) return;

      try {
        await navigator.clipboard.writeText(input.value);
        const original = button.textContent;
        button.textContent = 'Copiado';
        window.setTimeout(() => { button.textContent = original; }, 1600);
      } catch (error) {
        input.focus();
        input.select();
        document.execCommand('copy');
      }
    });
  });

  // Convierte las tablas administrativas en fichas legibles en pantallas angostas.
  document.querySelectorAll('.table-responsive table:not(.table-no-mobile-cards)').forEach((table) => {
    const labels = [...table.querySelectorAll('thead th')].map((cell) => cell.textContent.trim());
    if (!labels.length) return;
    table.classList.add('admin-table-cards');
    table.querySelectorAll('tbody tr').forEach((row) => {
      if (row.children.length === 1 && row.children[0].colSpan > 1) row.classList.add('admin-table-empty');
      [...row.children].forEach((cell, index) => {
        if (cell.tagName === 'TD') cell.dataset.label = labels[index] || '';
      });
    });
  });

  // Indicador de navegación inmediato para redes móviles lentas.
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target === '_blank' || link.hasAttribute('download')) return;
    const target = new URL(link.href, window.location.href);
    if (target.origin !== window.location.origin || (target.pathname === window.location.pathname && target.hash)) return;
    body.classList.add('admin-is-navigating');
  });

  document.addEventListener('submit', (event) => {
    window.setTimeout(() => {
      if (!event.defaultPrevented) body.classList.add('admin-is-navigating');
    }, 0);
  });
  window.addEventListener('pageshow', () => body.classList.remove('admin-is-navigating'));

  const networkStatus = document.querySelector('[data-network-status]');
  const syncNetworkStatus = () => {
    const online = navigator.onLine;
    body.classList.toggle('admin-is-offline', !online);
    if (networkStatus) {
      networkStatus.classList.toggle('is-offline', !online);
      const label = networkStatus.querySelector('span');
      if (label) label.textContent = online ? 'En línea' : 'Sin conexión';
    }
  };
  window.addEventListener('online', syncNetworkStatus);
  window.addEventListener('offline', syncNetworkStatus);
  syncNetworkStatus();

  const installButtons = [...document.querySelectorAll('[data-pwa-install]')];
  let installPrompt = null;
  const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);

  const setInstallVisible = (visible) => {
    installButtons.forEach((button) => { button.hidden = !visible; });
  };

  const showIosInstructions = () => {
    let sheet = document.querySelector('[data-install-help]');
    if (!sheet) {
      sheet = document.createElement('div');
      sheet.className = 'admin-install-sheet';
      sheet.dataset.installHelp = '';
      sheet.innerHTML = `
        <button class="admin-install-sheet__backdrop" type="button" data-install-help-close aria-label="Cerrar"></button>
        <section role="dialog" aria-modal="true" aria-labelledby="installHelpTitle">
          <span class="admin-install-sheet__handle" aria-hidden="true"></span>
          <span class="admin-install-sheet__icon">GC</span>
          <div><small>Aplicación móvil</small><h2 id="installHelpTitle">Instala Go Creative Admin</h2></div>
          <ol><li>Toca el botón <strong>Compartir</strong> de Safari.</li><li>Selecciona <strong>Agregar a inicio</strong>.</li><li>Confirma con <strong>Agregar</strong>.</li></ol>
          <button class="btn btn-dark w-100" type="button" data-install-help-close>Entendido</button>
        </section>`;
      body.appendChild(sheet);
      sheet.querySelectorAll('[data-install-help-close]').forEach((button) => {
        button.addEventListener('click', () => sheet.classList.remove('is-visible'));
      });
    }
    sheet.classList.add('is-visible');
  };

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    if (!standalone) setInstallVisible(true);
  });

  installButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      if (installPrompt) {
        installPrompt.prompt();
        await installPrompt.userChoice;
        installPrompt = null;
        setInstallVisible(false);
      } else if (isIos && !standalone) {
        showIosInstructions();
      }
    });
  });

  window.addEventListener('appinstalled', () => {
    installPrompt = null;
    setInstallVisible(false);
  });
  if (isIos && !standalone) setInstallVisible(true);

  if ('serviceWorker' in navigator && body.dataset.adminBase) {
    const baseUrl = new URL(body.dataset.adminBase, window.location.origin);
    const swUrl = new URL('sw.js', baseUrl);
    window.addEventListener('load', async () => {
      try {
        const registration = await navigator.serviceWorker.register(swUrl, { scope: baseUrl.pathname });
        const updateBar = document.querySelector('[data-app-update]');
        const updateAction = document.querySelector('[data-app-update-action]');

        const offerUpdate = (worker) => {
          if (!navigator.serviceWorker.controller || !worker || !updateBar) return;
          updateBar.hidden = false;
          updateAction?.addEventListener('click', () => worker.postMessage({ type: 'SKIP_WAITING' }), { once: true });
        };

        if (registration.waiting) offerUpdate(registration.waiting);
        registration.addEventListener('updatefound', () => {
          const worker = registration.installing;
          worker?.addEventListener('statechange', () => {
            if (worker.state === 'installed') offerUpdate(worker);
          });
        });
        navigator.serviceWorker.addEventListener('controllerchange', () => window.location.reload());
      } catch (error) {
        // El panel sigue funcionando normalmente si el navegador bloquea PWA.
      }
    });
  }
})();
