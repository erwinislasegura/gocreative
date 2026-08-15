(() => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  const header = document.querySelector('[data-header]');
  const progress = document.querySelector('[data-scroll-progress]');

  if (toggle && nav) {
    const mobileNavigation = window.matchMedia('(max-width: 1100px)');
    const toggleLabel = toggle.querySelector('[data-nav-toggle-label]');

    const setMenuState = (open, restoreFocus = false) => {
      const shouldOpen = open && mobileNavigation.matches;
      toggle.setAttribute('aria-expanded', String(shouldOpen));
      toggle.setAttribute('aria-label', shouldOpen ? 'Cerrar menú' : 'Abrir menú');
      if (toggleLabel) toggleLabel.textContent = shouldOpen ? 'Cerrar menú' : 'Abrir menú';
      nav.classList.toggle('is-open', shouldOpen);
      document.body.classList.toggle('nav-open', shouldOpen);

      if (mobileNavigation.matches) nav.setAttribute('aria-hidden', String(!shouldOpen));
      else nav.removeAttribute('aria-hidden');

      if (!shouldOpen && restoreFocus && mobileNavigation.matches) toggle.focus({ preventScroll: true });
    };

    toggle.addEventListener('click', () => {
      setMenuState(toggle.getAttribute('aria-expanded') !== 'true');
    });

    nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenuState(false)));
    document.addEventListener('click', (event) => {
      if (toggle.getAttribute('aria-expanded') === 'true' && !nav.contains(event.target) && !toggle.contains(event.target)) {
        setMenuState(false);
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') setMenuState(false, true);
    });

    const syncNavigationMode = () => setMenuState(false);
    if (typeof mobileNavigation.addEventListener === 'function') mobileNavigation.addEventListener('change', syncNavigationMode);
    else mobileNavigation.addListener(syncNavigationMode);
    syncNavigationMode();
  }

  const syncScrollUi = () => {
    header?.classList.toggle('is-scrolled', window.scrollY > 20);
    document.body.classList.toggle('has-passed-hero', window.scrollY > window.innerHeight * .72);

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

  const motionHero = document.querySelector('[data-hero-motion]');
  let heroMotionFrame = null;

  if (motionHero) {
    const updateHeroMotion = (event) => {
      if (reducedMotion.matches || window.innerWidth <= 880) return;

      const rect = motionHero.getBoundingClientRect();
      const relativeX = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
      const relativeY = Math.max(0, Math.min(1, (event.clientY - rect.top) / rect.height));
      const centeredX = relativeX - .5;
      const centeredY = relativeY - .5;

      motionHero.style.setProperty('--hero-pointer-x', `${(relativeX * 100).toFixed(1)}%`);
      motionHero.style.setProperty('--hero-pointer-y', `${(relativeY * 100).toFixed(1)}%`);
      motionHero.style.setProperty('--hero-image-x', `${(centeredX * -13).toFixed(1)}px`);
      motionHero.style.setProperty('--hero-image-y', `${(centeredY * -9).toFixed(1)}px`);
      motionHero.style.setProperty('--hero-ui-x', `${(centeredX * 11).toFixed(1)}px`);
      motionHero.style.setProperty('--hero-ui-y', `${(centeredY * 8).toFixed(1)}px`);
      motionHero.style.setProperty('--hero-ui-reverse-x', `${(centeredX * -8.8).toFixed(1)}px`);
      motionHero.style.setProperty('--hero-ui-reverse-y', `${(centeredY * -6.4).toFixed(1)}px`);
      motionHero.style.setProperty('--hero-ui-soft-x', `${(centeredX * 6.1).toFixed(1)}px`);
      motionHero.style.setProperty('--hero-ui-soft-y', `${(centeredY * 4.4).toFixed(1)}px`);
    };

    motionHero.addEventListener('pointermove', (event) => {
      if (heroMotionFrame !== null) return;
      heroMotionFrame = window.requestAnimationFrame(() => {
        heroMotionFrame = null;
        updateHeroMotion(event);
      });
    }, { passive: true });

    motionHero.addEventListener('pointerleave', () => {
      motionHero.style.removeProperty('--hero-image-x');
      motionHero.style.removeProperty('--hero-image-y');
      motionHero.style.removeProperty('--hero-ui-x');
      motionHero.style.removeProperty('--hero-ui-y');
      motionHero.style.removeProperty('--hero-ui-reverse-x');
      motionHero.style.removeProperty('--hero-ui-reverse-y');
      motionHero.style.removeProperty('--hero-ui-soft-x');
      motionHero.style.removeProperty('--hero-ui-soft-y');
    });
  }

  document.querySelectorAll('[data-year]').forEach((el) => {
    el.textContent = String(new Date().getFullYear());
  });
})();
