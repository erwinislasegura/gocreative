(() => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  const header = document.querySelector('[data-header]');

  if (toggle && nav) {
    const closeMenu = () => {
      toggle.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
      document.body.classList.remove('nav-open');
    };

    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
      document.body.classList.toggle('nav-open', !open);
    });

    nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', (event) => event.key === 'Escape' && closeMenu());
  }

  const syncHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 20);
  syncHeader();
  window.addEventListener('scroll', syncHeader, { passive: true });

  document.querySelectorAll('[data-faq]').forEach((item) => {
    const button = item.querySelector('button');
    button?.addEventListener('click', () => {
      const open = item.classList.toggle('is-open');
      button.setAttribute('aria-expanded', String(open));
    });
  });

  const observer = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12 })
    : null;

  document.querySelectorAll('[data-reveal]').forEach((element) => {
    if (observer) observer.observe(element);
    else element.classList.add('is-visible');
  });

  const parallaxSections = Array.from(document.querySelectorAll('[data-parallax]'));
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let parallaxFrame = null;

  const updateParallax = () => {
    parallaxFrame = null;
    const enabled = !reducedMotion.matches && window.innerWidth > 880;

    parallaxSections.forEach((section) => {
      const media = section.querySelector('[data-parallax-media]');
      if (!media) return;

      if (!enabled) {
        media.style.removeProperty('--parallax-shift');
        return;
      }

      const rect = section.getBoundingClientRect();
      if (rect.bottom < -120 || rect.top > window.innerHeight + 120) return;

      const viewportCenter = window.innerHeight / 2;
      const sectionCenter = rect.top + rect.height / 2;
      const progress = (viewportCenter - sectionCenter) / (window.innerHeight + rect.height);
      const shift = Math.max(-72, Math.min(72, progress * 150));
      media.style.setProperty('--parallax-shift', `${shift.toFixed(1)}px`);
    });
  };

  const scheduleParallax = () => {
    if (parallaxFrame !== null) return;
    parallaxFrame = window.requestAnimationFrame(updateParallax);
  };

  if (parallaxSections.length > 0) {
    updateParallax();
    window.addEventListener('scroll', scheduleParallax, { passive: true });
    window.addEventListener('resize', scheduleParallax, { passive: true });
    if (typeof reducedMotion.addEventListener === 'function') {
      reducedMotion.addEventListener('change', scheduleParallax);
    }
  }

  document.querySelectorAll('[data-year]').forEach((el) => {
    el.textContent = String(new Date().getFullYear());
  });
})();
