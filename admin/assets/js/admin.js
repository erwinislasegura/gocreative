(() => {
  const sidebar = document.querySelector('[data-admin-sidebar]');
  const toggle = document.querySelector('[data-admin-menu-toggle]');
  const closeButton = document.querySelector('[data-admin-menu-close]');
  const backdrop = document.querySelector('[data-admin-menu-backdrop]');

  if (!sidebar || !toggle || !backdrop) return;

  const desktop = window.matchMedia('(min-width: 992px)');

  const setOpen = (open, restoreFocus = false) => {
    const shouldOpen = open && !desktop.matches;
    sidebar.classList.toggle('is-open', shouldOpen);
    toggle.setAttribute('aria-expanded', String(shouldOpen));
    toggle.setAttribute('aria-label', shouldOpen ? 'Cerrar navegación' : 'Abrir navegación');
    document.body.classList.toggle('admin-menu-open', shouldOpen);
    backdrop.hidden = !shouldOpen;

    if (desktop.matches) sidebar.removeAttribute('aria-hidden');
    else sidebar.setAttribute('aria-hidden', String(!shouldOpen));

    if (shouldOpen) {
      window.requestAnimationFrame(() => closeButton?.focus({ preventScroll: true }));
    } else if (restoreFocus && !desktop.matches) {
      toggle.focus({ preventScroll: true });
    }
  };

  toggle.addEventListener('click', () => {
    setOpen(toggle.getAttribute('aria-expanded') !== 'true');
  });
  closeButton?.addEventListener('click', () => setOpen(false, true));
  backdrop.addEventListener('click', () => setOpen(false, true));
  sidebar.querySelectorAll('.admin-nav a').forEach((link) => {
    link.addEventListener('click', () => setOpen(false));
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') setOpen(false, true);
  });

  const syncBreakpoint = () => setOpen(false);
  if (typeof desktop.addEventListener === 'function') desktop.addEventListener('change', syncBreakpoint);
  else desktop.addListener(syncBreakpoint);
  syncBreakpoint();
})();

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
