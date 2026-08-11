(() => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  const header = document.querySelector('[data-header]');
  const progress = document.querySelector('[data-scroll-progress]');

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

  const syncScrollUi = () => {
    header?.classList.toggle('is-scrolled', window.scrollY > 20);

    if (progress) {
      const available = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
      const value = Math.max(0, Math.min(1, window.scrollY / available));
      progress.style.transform = `scaleX(${value.toFixed(4)})`;
    }
  };

  syncScrollUi();
  window.addEventListener('scroll', syncScrollUi, { passive: true });

  document.querySelectorAll('[data-faq]').forEach((item) => {
    const button = item.querySelector('button');
    button?.addEventListener('click', () => {
      const open = item.classList.toggle('is-open');
      button.setAttribute('aria-expanded', String(open));
    });
  });

  const revealSelectors = [
    '.inner-hero .breadcrumbs',
    '.inner-hero__grid > div',
    '.section-heading',
    '.content-grid__intro',
    '.content-item',
    '.service-card',
    '.feature-card',
    '.portfolio-card',
    '.plan-card',
    '.testimonial-grid blockquote',
    '.check-grid li',
    '.stat',
    '.contact-intro',
    '.contact-card',
    '.form-card',
    '.project-strip__item',
    '.photo-editorial__item',
    '.split-media__image',
    '.split-media__content',
    '.about-method__card',
    '.about-value'
  ];

  document.querySelectorAll(revealSelectors.join(',')).forEach((element, index) => {
    if (!element.hasAttribute('data-reveal')) {
      const direction = index % 3 === 0 ? 'left' : index % 3 === 1 ? 'up' : 'right';
      element.setAttribute('data-reveal', direction);
    }
  });

  document.querySelectorAll('main > section').forEach((section) => {
    section.querySelectorAll('[data-reveal]').forEach((element, index) => {
      element.style.setProperty('--reveal-delay', `${Math.min(index * 65, 260)}ms`);
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
      }, { threshold: 0.08, rootMargin: '0px 0px -7% 0px' })
    : null;

  document.querySelectorAll('[data-reveal]').forEach((element) => {
    if (observer) observer.observe(element);
    else element.classList.add('is-visible');
  });

  const sectionObserver = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) entry.target.classList.add('is-section-visible');
        });
      }, { threshold: 0.1 })
    : null;

  document.querySelectorAll('main > section').forEach((section) => {
    if (sectionObserver) sectionObserver.observe(section);
    else section.classList.add('is-section-visible');
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
