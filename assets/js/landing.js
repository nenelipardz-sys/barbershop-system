/* Lumié Talent — client landing page behaviour */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const BASE = document.body.dataset.base || '';
  const CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Top bar shadow + hero parallax ---------- */
  const bar = $('#lpBar');
  const heroContent = $('.lp-hero-content');
  const onScroll = () => {
    const y = window.scrollY;
    bar.classList.toggle('scrolled', y > 40);
    if (heroContent && !reduceMotion && y < window.innerHeight) {
      heroContent.style.transform = `translateY(${y * 0.3}px)`;
      heroContent.style.opacity = String(Math.max(0, 1 - y / (window.innerHeight * 0.75)));
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Menu ---------- */
  const menu = $('#lpMenu');
  const setMenu = (open) => {
    menu.classList.toggle('open', open);
    menu.setAttribute('aria-hidden', open ? 'false' : 'true');
    document.body.style.overflow = open ? 'hidden' : '';
  };
  $$('[data-menu-open]').forEach((b) => b.addEventListener('click', () => setMenu(true)));
  $$('[data-menu-close]').forEach((b) => b.addEventListener('click', () => setMenu(false)));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenu(false); });

  /* ---------- Reveal on scroll ----------
     Plain scroll/resize checks (no IntersectionObserver) so content always appears. */
  const revealEls = $$(".reveal");
  const testimonials = $(".lp-testimonials");
  let ticking = false;
  const checkReveal = () => {
    ticking = false;
    const vh = window.innerHeight;
    revealEls.forEach((el) => {
      if (!el.classList.contains("in") && el.getBoundingClientRect().top < vh * 0.9) el.classList.add("in");
    });
    if (testimonials) {
      const r = testimonials.getBoundingClientRect();
      testimonials.classList.toggle("in-view", r.top < vh * 0.75 && r.bottom > vh * 0.25);
    }
  };
  const queueReveal = () => { if (!ticking) { ticking = true; requestAnimationFrame(checkReveal); } };
  window.addEventListener("scroll", queueReveal, { passive: true });
  window.addEventListener("resize", queueReveal);
  window.addEventListener("load", checkReveal);
  checkReveal();

  /* ---------- Hero video sound ---------- */
  const hero = $('#heroVideo');
  const soundBtn = $('[data-hero-sound]');
  if (hero && soundBtn) {
    soundBtn.addEventListener('click', () => {
      hero.muted = !hero.muted;
      if (!hero.muted) hero.play().catch(() => {});
      soundBtn.querySelector('span').textContent = hero.muted ? 'SOUND OFF' : 'SOUND ON';
      soundBtn.setAttribute('aria-label', hero.muted ? 'Turn sound on' : 'Turn sound off');
    });
  }

  /* ---------- Testimony videos: play while visible, tap for sound ---------- */
  const tvids = $$('[data-tvid]');
  const muteOthers = (keep) => {
    tvids.forEach((v) => {
      if (v !== keep) {
        v.muted = true;
        const b = v.parentElement.querySelector('[data-tvid-sound]');
        b.classList.remove('on');
        b.querySelector('span').textContent = 'Tap for sound';
      }
    });
    if (keep && hero && !hero.muted) soundBtn.click();
  };
  const playVisible = () => {
    tvids.forEach((v) => {
      if (v.dataset.userPaused) return;
      const r = v.getBoundingClientRect();
      const visible = r.bottom > window.innerHeight * 0.2 && r.top < window.innerHeight * 0.8;
      if (visible && v.paused) v.play().catch(() => {});
      if (!visible && !v.paused) v.pause();
    });
  };
  window.addEventListener('scroll', playVisible, { passive: true });
  window.addEventListener('load', playVisible);
  tvids.forEach((v) => {
    const btn = v.parentElement.querySelector('[data-tvid-sound]');
    const toggleSound = () => {
      const turnOn = v.muted;
      muteOthers(turnOn ? v : null);
      v.muted = !turnOn;
      if (turnOn) v.play().catch(() => {});
      btn.classList.toggle('on', turnOn);
      btn.querySelector('span').textContent = turnOn ? 'Sound on' : 'Tap for sound';
      btn.setAttribute('aria-label', turnOn ? 'Turn sound off' : 'Turn sound on');
    };
    btn.addEventListener('click', toggleSound);
    v.addEventListener('click', () => {
      if (v.paused) { delete v.dataset.userPaused; v.play().catch(() => {}); } else { v.dataset.userPaused = '1'; v.pause(); }
    });
  });
  if (hero && soundBtn) {
    soundBtn.addEventListener('click', () => { if (!hero.muted) muteOthers(null); });
  }

  /* ---------- Written reviews slider ---------- */
  const quotesBox = $('[data-quotes]');
  if (quotesBox) {
    const quotes = $$('.lp-quote', quotesBox);
    const dots = $$('[data-quote]', quotesBox);
    let idx = 0;
    let timer;
    const show = (i) => {
      idx = (i + quotes.length) % quotes.length;
      quotes.forEach((q, k) => q.classList.toggle('active', k === idx));
      dots.forEach((d, k) => d.classList.toggle('active', k === idx));
    };
    const auto = () => { clearInterval(timer); if (!reduceMotion) timer = setInterval(() => show(idx + 1), 6500); };
    dots.forEach((d) => d.addEventListener('click', () => { show(+d.dataset.quote); auto(); }));
    auto();
  }

  /* ---------- Newsletter ---------- */
  const form = $('[data-subscribe]');
  if (form) {
    const msg = $('.lp-news-msg', form);
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = $('button', form);
      btn.disabled = true;
      msg.className = 'lp-news-msg';
      msg.textContent = '';
      try {
        const body = new URLSearchParams({ name: form.name.value, email: form.email.value, csrf: CSRF });
        const res = await fetch(`${BASE}/api/subscribe.php`, { method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' } });
        const d = await res.json();
        msg.classList.add(d.ok ? 'ok' : 'err');
        msg.textContent = d.ok ? d.message : d.error;
      } catch (err) {
        msg.classList.add('err');
        msg.textContent = 'Something went wrong. Please try again.';
      }
      btn.disabled = false;
    });
  }
})();
